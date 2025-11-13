<?php

namespace ToonLite;

use ToonLite\Exceptions\DecodeException;

class Decoder
{
    public function decode(string $text): mixed
    {
        $lines = preg_split('/\R/', trim($text));
        if ($lines === false) {
            throw new DecodeException("Failed to split TOON text");
        }

        return $this->parseLines($lines);
    }

    private function parseLines(array $lines): array
    {
        $obj = [];

        $mode = null;          // null | 'list' | 'tabular'
        $currentKey = null;
        $currentCols = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // key: value
            if (preg_match('/^([A-Za-z0-9_]+): (.+)$/', $line, $m)) {
                $mode = null;
                $currentKey = null;
                $currentCols = [];

                $obj[$m[1]] = $this->parseValue($m[2]);
                continue;
            }

            // primitive array: key[N]: a,b,c
            if (preg_match('/^([A-Za-z0-9_]+)\[(\d+)\]: (.+)$/', $line, $m)) {
                $mode = null;
                $currentKey = null;
                $currentCols = [];

                $values = array_map('trim', explode(",", $m[3]));
                $obj[$m[1]] = array_map([$this, 'parseValue'], $values);
                continue;
            }

            // tabular header: key[N]{a,b,c}:
            if (preg_match('/^([A-Za-z0-9_]+)\[(\d+)\]\{(.+)\}:$/', $line, $m)) {
                $currentKey = $m[1];
                $currentCols = array_map('trim', explode(",", $m[3]));
                $obj[$currentKey] = [];
                $mode = 'tabular';
                continue;
            }

            // list header: key[N]:
            if (preg_match('/^([A-Za-z0-9_]+)\[(\d+)\]:$/', $line, $m)) {
                $currentKey = $m[1];
                $obj[$currentKey] = [];
                $currentCols = [];
                $mode = 'list';
                continue;
            }

            // list item: - value
            if ($mode === 'list' && preg_match('/^- (.+)$/', $line, $m)) {
                $obj[$currentKey][] = $this->parseValue($m[1]);
                continue;
            }

            // tabular row: values row aligned with currentCols
            if ($mode === 'tabular') {
                $vals = array_map('trim', explode(",", $line));
                if (count($vals) !== count($currentCols)) {
                    throw new DecodeException("Tabular row mismatch at: $line");
                }

                $row = [];
                foreach ($currentCols as $i => $col) {
                    $row[$col] = $this->parseValue($vals[$i]);
                }
                $obj[$currentKey][] = $row;
                continue;
            }

            throw new DecodeException("Cannot parse line: $line");
        }

        return $obj;
    }

    private function parseValue(string $v): mixed
    {
        $v = trim($v);

        if ($v === 'null') return null;
        if ($v === 'true') return true;
        if ($v === 'false') return false;

        if (is_numeric($v)) {
            return $v + 0;
        }

        // quoted string
        if (preg_match('/^"(.*)"$/', $v, $m)) {
            return stripcslashes($m[1]);
        }

        // bareword string
        return $v;
    }
}
