<?php

namespace ToonLite;

use ToonLite\Exceptions\DecodeException;

class Decoder
{
    public function decode(string $text): mixed
    {
        /** @var array<int,string> $lines */
        $lines = $this->preprocessLines($text);
        $index = 0;

        return $this->parseBlock($lines, $index, 0);
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
     * @return array<int,string> Normalized non-empty lines (indent preserved)
     */
    private function preprocessLines(string $text): array
    {
        $rawLines = preg_split('/\R/', $text);
        if ($rawLines === false) {
            throw new DecodeException('Failed to split TOON text');
        }

        /** @var array<int,string> $lines */
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
                if ($before === '' || preg_match('/\s$/', $before) === 1) {
                    $cut = $before;
                }
            }

            // Inline '//' comments
            $slashPos = strpos($cut, '//');
            if ($slashPos !== false) {
                $before = substr($cut, 0, $slashPos);
                // Only treat as comment if preceded by whitespace or start
                if ($before === '' || preg_match('/\s$/', $before) === 1) {
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
     * Parse a block of TOON at a given indentation level.
     *
     * Handles:
     * - key: value
     * - key:            (object header, nested block)
     * - key[N]: a,b,c   (primitive array)
     * - key[N]:         (list header, then "- value" items)
     * - key[N]{a,b}:    (tabular header, then rows)
     * - multiline strings with """ ... """
     *
     * @param array<int,string> $lines
     * @param int               $index      current index in $lines (by ref)
     * @param int               $baseIndent indentation level for this block
     *
     * @return array<string,mixed>
     */
    private function parseBlock(array $lines, int &$index, int $baseIndent): array
    {
        $result    = [];
        $lineCount = count($lines);

        while ($index < $lineCount) {
            $raw    = $lines[$index];
            $indent = strspn($raw, ' ');

            // Block ends when indentation decreases
            if ($indent < $baseIndent) {
                break;
            }

            // We only expect lines exactly at this block's indent here.
            // Deeper indent is consumed by recursive calls or list/tabular parsing.
            if ($indent > $baseIndent) {
                throw new DecodeException(
                    sprintf('Unexpected indentation at line %d: %s', $index + 1, $raw)
                );
            }

            $content = ltrim($raw);

            // -----------------------------------------------------------------
            // 1) Object header: "key:"
            // -----------------------------------------------------------------
            if (preg_match('/^([A-Za-z0-9_]+):$/', $content, $m) === 1) {
                $key = $m[1];
                $index++; // move to first child line

                $result[$key] = $this->parseBlock($lines, $index, $baseIndent + 2);
                continue;
            }

            // -----------------------------------------------------------------
            // 2) key: value  (including multiline """...""" support)
            // -----------------------------------------------------------------
            if (preg_match('/^([A-Za-z0-9_]+): (.+)$/', $content, $m) === 1) {
                $key      = $m[1];
                $valueRaw = $m[2];

                // Multiline string: key: """
                if ($valueRaw === '"""') {
                    /** @var array<int,string> $buffer */
                    $buffer  = [];
                    $closed  = false;
                    $index++; // move to first line inside block

                    while ($index < $lineCount) {
                        $lineInner = $lines[$index];

                        // Closing delimiter if trimmed line is just """
                        if (ltrim($lineInner) === '"""') {
                            $closed = true;
                            $index++; // consume closing line
                            break;
                        }

                        $buffer[] = rtrim($lineInner);
                        $index++;
                    }

                    if (!$closed) {
                        throw new DecodeException(
                            sprintf('Unterminated multiline string for key "%s"', $key)
                        );
                    }

                    $result[$key] = implode("\n", $buffer);
                    continue;
                }

                // Normal single-line value
                $result[$key] = $this->parseValue($valueRaw);
                $index++;
                continue;
            }

            // -----------------------------------------------------------------
            // 3) Primitive array: key[N]: a,b,c
            // -----------------------------------------------------------------
            if (preg_match('/^([A-Za-z0-9_]+)\[(\d+)\]: (.+)$/', $content, $m) === 1) {
                $key      = $m[1];
                $expected = (int) $m[2];
                $values   = array_map('trim', explode(',', $m[3]));

                if (count($values) !== $expected) {
                    throw new DecodeException(
                        sprintf(
                            "Value count mismatch for '%s' at line %d: expected %d, got %d",
                            $key,
                            $index + 1,
                            $expected,
                            count($values)
                        )
                    );
                }

                $result[$key] = array_map([$this, 'parseValue'], $values);
                $index++;
                continue;
            }

            // -----------------------------------------------------------------
            // 4) List header: key[N]:
            // -----------------------------------------------------------------
            if (preg_match('/^([A-Za-z0-9_]+)\[(\d+)\]:$/', $content, $m) === 1) {
                $key      = $m[1];
                $expected = (int) $m[2];
                $index++; // move to first item

                /** @var array<int,mixed> $items */
                $items = [];

                while ($index < $lineCount) {
                    $raw2    = $lines[$index];
                    $indent2 = strspn($raw2, ' ');

                    // End of this list block when indentation goes back
                    if ($indent2 <= $baseIndent) {
                        break;
                    }

                    $content2 = ltrim($raw2);

                    if (preg_match('/^- (.+)$/', $content2, $mm) !== 1) {
                        throw new DecodeException(
                            sprintf('Expected list item at line %d: %s', $index + 1, $raw2)
                        );
                    }

                    $items[] = $this->parseValue($mm[1]);
                    $index++;
                }

                if (count($items) !== $expected) {
                    throw new DecodeException(
                        sprintf(
                            "Row count mismatch for '%s': expected %d, got %d",
                            $key,
                            $expected,
                            count($items)
                        )
                    );
                }

                $result[$key] = $items;
                continue;
            }

            // -----------------------------------------------------------------
            // 5) Tabular header: key[N]{a,b,c}:
            // -----------------------------------------------------------------
            if (preg_match('/^([A-Za-z0-9_]+)\[(\d+)\]\{(.+)\}:$/', $content, $m) === 1) {
                $key      = $m[1];
                $expected = (int) $m[2];
                $cols     = array_map('trim', explode(',', $m[3]));
                $index++; // move to first row

                /** @var array<int,array<string,mixed>> $rows */
                $rows = [];

                while ($index < $lineCount) {
                    $raw2    = $lines[$index];
                    $indent2 = strspn($raw2, ' ');

                    // End of this tabular block when indentation goes back
                    if ($indent2 <= $baseIndent) {
                        break;
                    }

                    $line2 = trim($raw2);
                    $vals  = array_map('trim', explode(',', $line2));

                    if (count($vals) !== count($cols)) {
                        throw new DecodeException(
                            sprintf(
                                'Tabular row mismatch at line %d: %s',
                                $index + 1,
                                $raw2
                            )
                        );
                    }

                    /** @var array<string,mixed> $row */
                    $row = [];
                    foreach ($cols as $i => $col) {
                        $row[$col] = $this->parseValue($vals[$i]);
                    }

                    $rows[] = $row;
                    $index++;
                }

                if (count($rows) !== $expected) {
                    throw new DecodeException(
                        sprintf(
                            "Row count mismatch for '%s': expected %d, got %d",
                            $key,
                            $expected,
                            count($rows)
                        )
                    );
                }

                $result[$key] = $rows;
                continue;
            }

            // -----------------------------------------------------------------
            // 6) Nothing matched
            // -----------------------------------------------------------------
            throw new DecodeException(
                sprintf('Cannot parse line %d: %s', $index + 1, $content)
            );
        }

        return $result;
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
        if (preg_match('/^"(.*)"$/', $v, $m) === 1) {
            return stripcslashes($m[1]);
        }

        // bareword string
        return $v;
    }
}
