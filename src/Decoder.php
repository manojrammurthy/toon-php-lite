<?php

namespace ToonLite;

use ToonLite\Exceptions\DecodeException;

class Decoder
{
    public function decode(string $text): mixed
    {
        // 🔹 preprocess lines (strip comments & blanks)
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
     *
     * @return array<int,string>  Normalized non-empty lines
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

       /**
     * Parse normalized TOON lines into a PHP array.
     *
     * @param array<int,string> $lines
     * @return array<string,mixed>
     */
    private function parseLines(array $lines): array
    {
        $obj = [];

        $mode        = null;          // null | 'list' | 'tabular'
        $currentKey  = null;
        $currentCols = [];

        $expectedCount = null;        // for list/tabular blocks
        $seenCount     = 0;
        $headerLine    = null;

        foreach ($lines as $index => $lineRaw) {
            $lineNumber = $index + 1;
            $line       = trim($lineRaw);
            if ($line === '') {
                continue;
            }

            // If we are starting a new header, close previous block if open
            $finishBlockIfNeeded = function () use (&$mode, &$expectedCount, &$seenCount, &$headerLine, &$currentKey) {
                if ($mode === 'list' || $mode === 'tabular') {
                    if ($expectedCount !== null && $seenCount !== $expectedCount) {
                        throw new DecodeException(
                            "Row count mismatch for '{$currentKey}' (header at line {$headerLine}): "
                            . "expected {$expectedCount}, got {$seenCount}"
                        );
                    }
                }
            };

            // key: value
            if (preg_match('/^([A-Za-z0-9_]+): (.+)$/', $line, $m)) {
                $finishBlockIfNeeded();

                $mode         = null;
                $currentKey   = null;
                $currentCols  = [];
                $expectedCount = null;
                $seenCount     = 0;
                $headerLine    = null;

                $obj[$m[1]] = $this->parseValue($m[2]);
                continue;
            }

            // primitive array: key[N]: a,b,c
            if (preg_match('/^([A-Za-z0-9_]+)\[(\d+)\]: (.+)$/', $line, $m)) {
                $finishBlockIfNeeded();

                $mode         = null;
                $currentKey   = null;
                $currentCols  = [];
                $expectedCount = null;
                $seenCount     = 0;
                $headerLine    = null;

                $expected = (int) $m[2];
                $values   = array_map('trim', explode(",", $m[3]));

                if (count($values) !== $expected) {
                    throw new DecodeException(
                        "Value count mismatch for '{$m[1]}' at line {$lineNumber}: "
                        . "expected {$expected}, got " . count($values)
                    );
                }

                $obj[$m[1]] = array_map([$this, 'parseValue'], $values);
                continue;
            }

            // tabular header: key[N]{a,b,c}:
            if (preg_match('/^([A-Za-z0-9_]+)\[(\d+)\]\{(.+)\}:$/', $line, $m)) {
                $finishBlockIfNeeded();

                $currentKey  = $m[1];
                $currentCols = array_map('trim', explode(",", $m[3]));
                $obj[$currentKey] = [];
                $mode = 'tabular';

                $expectedCount = (int) $m[2];
                $seenCount     = 0;
                $headerLine    = $lineNumber;

                continue;
            }

            // list header: key[N]:
            if (preg_match('/^([A-Za-z0-9_]+)\[(\d+)\]:$/', $line, $m)) {
                $finishBlockIfNeeded();

                $currentKey  = $m[1];
                $obj[$currentKey] = [];
                $currentCols = [];
                $mode = 'list';

                $expectedCount = (int) $m[2];
                $seenCount     = 0;
                $headerLine    = $lineNumber;

                continue;
            }

            // list item: - value
            if ($mode === 'list' && preg_match('/^- (.+)$/', $line, $m)) {
                $obj[$currentKey][] = $this->parseValue($m[1]);
                $seenCount++;
                continue;
            }

            // tabular row: values row aligned with currentCols
            if ($mode === 'tabular') {
                $vals = array_map('trim', explode(",", $line));
                if (count($vals) !== count($currentCols)) {
                    throw new DecodeException(
                        "Tabular row mismatch at line {$lineNumber}: {$line}"
                    );
                }

                $row = [];
                foreach ($currentCols as $i => $col) {
                    $row[$col] = $this->parseValue($vals[$i]);
                }
                $obj[$currentKey][] = $row;
                $seenCount++;
                continue;
            }

            throw new DecodeException("Cannot parse line {$lineNumber}: {$line}");
        }

        // End of file: ensure last block's count matches
        if ($mode === 'list' || $mode === 'tabular') {
            if ($expectedCount !== null && $seenCount !== $expectedCount) {
                throw new DecodeException(
                    "Row count mismatch for '{$currentKey}' (header at line {$headerLine}): "
                    . "expected {$expectedCount}, got {$seenCount}"
                );
            }
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
