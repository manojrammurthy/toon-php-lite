<?php

namespace ToonLite;

use ToonLite\Exceptions\DecodeException;

class Decoder
{
    public function decode(string $text): mixed
    {
        // 🔹 NEW: preprocess lines (strip comments & blanks)
        $lines = $this->preprocessLines($text);

        return $this->parseLines($lines);
    }

    /**
     * Split text into logical lines and strip comments / blank lines.
     *
     * Supports:
     * - full-line comments starting with "#" or "//"
     * - inline comments after a value:
     *     name: Manoj  # this is ignored
     *     name: Manoj  // this is ignored
     *
     * We try not to break things like "http://example.com" by only treating
     * "#" or "//" as a comment marker when it is at the start of the line
     * or preceded by whitespace.
     */
    private function preprocessLines(string $text): array
    {
        $rawLines = preg_split('/\R/', $text);
        if ($rawLines === false) {
            throw new DecodeException("Failed to split TOON text");
        }

        $lines = [];

        foreach ($rawLines as $line) {
            $original = $line;
            $trimmed  = ltrim($line);

            // Full-line comments or empty lines
            if (
                $trimmed === '' ||
                str_starts_with($trimmed, '#') ||
                str_starts_with($trimmed, '//')
            ) {
                continue;
            }

            $cut = $original;

            // Inline '#' comments
            $hashPos = strpos($cut, '#');
            if ($hashPos !== false) {
                $before = substr($cut, 0, $hashPos);
                // Only treat as comment if preceded by whitespace or start
                if ($before === '' || preg_match('/\s$/', $before)) {
                    $cut = $before;
                }
            }

            // Inline '//' comments
            $slashPos = strpos($cut, '//');
            if ($slashPos !== false) {
                $before = substr($cut, 0, $slashPos);
                // Only treat as comment if preceded by whitespace or start
                if ($before === '' || preg_match('/\s$/', $before)) {
                    $cut = $before;
                }
            }

            $cut = rtrim($cut);

            if ($cut === '') {
                continue;
            }

            $lines[] = $cut;
        }

        return $lines;
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
