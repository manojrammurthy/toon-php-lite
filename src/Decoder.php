<?php

namespace ToonLite;

use ToonLite\Exceptions\DecodeException;

class Decoder
{
    private const INDENT = 2;

    public function decode(string $text): mixed
    {
        // preprocess lines (strip comments & blanks, keep leading spaces)
        $lines = $this->preprocessLines($text);

        $index  = 0;
        $result = $this->parseBlock($lines, $index, 0);

        return $result;
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
     * @return array<int,string> Normalized non-empty lines (leading spaces kept)
     */
    private function preprocessLines(string $text): array
    {
        $rawLines = preg_split('/\R/', $text);
        if ($rawLines === false) {
            throw new DecodeException('Failed to split TOON text');
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
     * Parse a block of TOON lines at a given indentation level.
     *
     * @param array<int,string> $lines
     * @return array<string,mixed>
     */
    private function parseBlock(array $lines, int &$index, int $minIndent): array
    {
        $obj = [];

        /** @var null|'list'|'tabular' $mode */
        $mode          = null;
        /** @var string|null $currentKey */
        $currentKey    = null;
        /** @var array<int,string> $currentCols */
        $currentCols   = [];
        /** @var int|null $expectedCount */
        $expectedCount = null;
        /** @var int $seenCount */
        $seenCount     = 0;
        /** @var int|null $headerLine */
        $headerLine    = null;

        $finishBlock = function () use (
            &$mode,
            &$expectedCount,
            &$seenCount,
            &$headerLine,
            &$currentKey
        ): void {
            if ($mode === 'list' || $mode === 'tabular') {
                if ($expectedCount !== null && $seenCount !== $expectedCount) {
                    throw new DecodeException(
                        sprintf(
                            "Row count mismatch for '%s' (header at line %d): expected %d, got %d",
                            (string) $currentKey,
                            $headerLine ?? 0,
                            $expectedCount,
                            $seenCount
                        )
                    );
                }
            }

            $mode          = null;
            $currentKey    = null;
            $expectedCount = null;
            $seenCount     = 0;
            $headerLine    = null;
        };

        $numLines = count($lines);

        while ($index < $numLines) {
            $raw        = $lines[$index];
            $lineNumber = $index + 1;

            if (trim($raw) === '') {
                $index++;
                continue;
            }

            $indent = strspn($raw, ' ');

            // If indentation decreases, this block is done
            if ($indent < $minIndent) {
                $finishBlock();
                break;
            }

            $content = ltrim($raw);

            // Nested object header: "key:" with no value
            if (preg_match('/^([A-Za-z0-9_]+):$/', $content, $m)) {
                // close any open list/tabular
                $finishBlock();

                $key = $m[1];

                // Recurse into nested block, which starts on the next line
                $index++;
                $child = $this->parseBlock($lines, $index, $indent + self::INDENT);
                $obj[$key] = $child;

                continue;
            }

            // key: value
            if (preg_match('/^([A-Za-z0-9_]+): (.+)$/', $content, $m)) {
                $finishBlock();

                $obj[$m[1]] = $this->parseValue($m[2]);
                $index++;
                continue;
            }

            // primitive array: key[N]: a,b,c
            if (preg_match('/^([A-Za-z0-9_]+)\[(\d+)\]: (.+)$/', $content, $m)) {
                $finishBlock();

                $expected = (int) $m[2];
                $values   = array_map('trim', explode(',', $m[3]));

                if (count($values) !== $expected) {
                    throw new DecodeException(
                        "Value count mismatch for '{$m[1]}' at line {$lineNumber}: " .
                        "expected {$expected}, got " . count($values)
                    );
                }

                $obj[$m[1]] = array_map([$this, 'parseValue'], $values);
                $index++;
                continue;
            }

            // tabular header: key[N]{a,b,c}:
            if (preg_match('/^([A-Za-z0-9_]+)\[(\d+)\]\{(.+)\}:$/', $content, $m)) {
                $finishBlock();

                $currentKey       = $m[1];
                $currentCols      = array_map('trim', explode(',', $m[3]));
                $obj[$currentKey] = [];
                $mode             = 'tabular';
                $expectedCount    = (int) $m[2];
                $seenCount        = 0;
                $headerLine       = $lineNumber;

                $index++;
                continue;
            }

            // list header: key[N]:
            if (preg_match('/^([A-Za-z0-9_]+)\[(\d+)\]:$/', $content, $m)) {
                $finishBlock();

                $currentKey       = $m[1];
                $obj[$currentKey] = [];
                $currentCols      = [];
                $mode             = 'list';
                $expectedCount    = (int) $m[2];
                $seenCount        = 0;
                $headerLine       = $lineNumber;

                $index++;
                continue;
            }

            // list item: - value
            if ($mode === 'list' && preg_match('/^- (.+)$/', $content, $m)) {
                $obj[$currentKey][] = $this->parseValue($m[1]);
                $seenCount++;
                $index++;
                continue;
            }

            // tabular row: values row aligned with currentCols
            if ($mode === 'tabular') {
                $vals = array_map('trim', explode(',', $content));
                if (count($vals) !== count($currentCols)) {
                    throw new DecodeException(
                        "Tabular row mismatch at line {$lineNumber}: {$content}"
                    );
                }

                $row = [];
                foreach ($currentCols as $iCol => $col) {
                    $row[$col] = $this->parseValue($vals[$iCol]);
                }
                $obj[$currentKey][] = $row;
                $seenCount++;
                $index++;
                continue;
            }

            throw new DecodeException("Cannot parse line {$lineNumber}: {$content}");
        }

        // End of this block: check any open list/tabular
        $finishBlock();

        return $obj;
    }

    private function parseValue(string $v): mixed
    {
        $v = trim($v);

        if ($v === 'null') {
            return null;
        }
        if ($v === 'true') {
            return true;
        }
        if ($v === 'false') {
            return false;
        }

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
