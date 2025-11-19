<?php

namespace ToonLite;

use ToonLite\Exceptions\EncodeException;

class Encoder
{
    private EncodeOptions $options;

    public function __construct(?EncodeOptions $options = null)
    {
        // Defaults: indent 2 spaces, trailing newline on
        $this->options = $options ?? EncodeOptions::defaults();
    }

    public function encode(mixed $value): string
    {
        // Encode the value starting at indent level 0
        $body = $this->encodeValue($value, 0);

        // Trim any accidental trailing newlines first
        $body = rtrim($body, "\r\n");

        if ($this->options->getTrailingNewline()) {
            return $body . "\n";
        }

        return $body;
    }

    /**
     * Compute indentation string for a given level using EncodeOptions.
     */
    private function indent(int $level): string
    {
        // When minify is on, strip indentation completely
    if ($this->options->isMinify()) {
        return '';
    }

    $size = $this->options->getIndentSize();
    if ($size <= 0 || $level <= 0) {
        return '';
    }

    return str_repeat(' ', $level * $size);
    }

    private function encodeValue(mixed $value, int $level): string
    {
        if ($value === null) {
            return 'null';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (is_string($value)) {
            return $this->encodeString($value);
        }

        if (is_array($value)) {
            if ($this->isAssoc($value)) {
                return $this->encodeObject($value, $level);
            }

            return $this->encodeArray($value, $level);
        }

        throw new EncodeException('Unsupported type: ' . gettype($value));
    }

    /**
     * @param array<string,mixed> $obj
     */
    private function encodeObject(array $obj, int $level): string
    {
        $indent = $this->indent($level);
        $out    = [];

        foreach ($obj as $key => $val) {
            // Multiline scalar support at any nesting level
            if (is_string($val) && str_contains($val, "\n")) {
                $out[] = $this->encodeMultilineProperty($key, $val, $level);
                continue;
            }

            if (is_array($val)) {
                if ($this->isAssoc($val)) {
                    // Nested object
                    $out[] = "{$indent}{$key}:";
                    $out[] = $this->encodeObject($val, $level + 1);
                } else {
                    // List / tabular / primitive array
                    $out[] = $this->encodeArrayProp($key, $val, $level);
                }
            } else {
                $encoded = $this->encodeValue($val, $level);
                $out[]   = "{$indent}{$key}: {$encoded}";
            }
        }

        return implode("\n", $out);
    }

    /**
     * @param array<int,mixed> $arr
     */
    private function encodeArrayProp(string $key, array $arr, int $level): string
    {
        $count  = count($arr);
        $indent = $this->indent($level);

        if ($count === 0) {
            return "{$indent}{$key}[0]:";
        }

        if ($this->isPrimitiveArray($arr)) {
            $values = array_map(fn ($v) => $this->encodeValue($v, $level), $arr);
            return "{$indent}{$key}[{$count}]: " . implode(',', $values);
        }

        if ($this->isUniformObjectArray($arr)) {
            return $this->encodeTabular($key, $arr, $level);
        }

        return $this->encodeListArray($key, $arr, $level);
    }

    /**
     * @param array<int,mixed> $arr
     */
    private function encodeArray(array $arr, int $level): string
    {
        $count  = count($arr);
        $indent = $this->indent($level);

        if ($this->isPrimitiveArray($arr)) {
            $values = array_map(fn ($v) => $this->encodeValue($v, $level), $arr);
            return "{$indent}[{$count}]: " . implode(',', $values);
        }

        $out = ["{$indent}[{$count}]:"];

        foreach ($arr as $item) {
            $subIndent = $this->indent($level + 1);

            if (is_array($item) && $this->isAssoc($item)) {
                $first = $this->encodeObject($item, $level + 1);
                $lines = explode("\n", $first);

                $out[] = "{$subIndent}- " . array_shift($lines);
                foreach ($lines as $l) {
                    $out[] = "{$subIndent}  {$l}";
                }
            } else {
                $out[] = "{$subIndent}- " . $this->encodeValue($item, $level + 1);
            }
        }

        return implode("\n", $out);
    }

    /**
     * @param array<int,array<string,scalar|null>> $rows
     */
    private function encodeTabular(string $key, array $rows, int $level): string
    {
        $indent = $this->indent($level);
        $count  = count($rows);
        $keys   = array_keys($rows[0]);

        $header = "{$indent}{$key}[{$count}]{" . implode(',', $keys) . "}:";
        $out    = [$header];

        foreach ($rows as $row) {
            $subIndent = $this->indent($level + 1);
            $vals      = [];

            foreach ($keys as $k) {
                $vals[] = $this->encodeValue($row[$k], $level + 1);
            }

            $out[] = $subIndent . implode(',', $vals);
        }

        return implode("\n", $out);
    }

    /**
     * @param array<int,mixed> $arr
     */
    private function encodeListArray(string $key, array $arr, int $level): string
    {
        $count  = count($arr);
        $indent = $this->indent($level);

        $out = ["{$indent}{$key}[{$count}]:"];

        foreach ($arr as $item) {
            $subIndent = $this->indent($level + 1);
            $out[]     = "{$subIndent}- " . $this->encodeValue($item, $level + 1);
        }

        return implode("\n", $out);
    }

    private function encodeString(string $s): string
    {
        // simple rule: quote if contains whitespace, comma or colon
        if (preg_match('/[\s,:]/', $s)) {
            return '"' . addcslashes($s, '"\\') . '"';
        }

        return $s;
    }

    /**
     * @param array<mixed> $arr
     */
    private function isAssoc(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * @param array<int,mixed> $arr
     */
    private function isPrimitiveArray(array $arr): bool
    {
        foreach ($arr as $v) {
            if (is_array($v)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int,mixed> $arr
     */
    private function isUniformObjectArray(array $arr): bool
    {
        if (!$arr || !is_array($arr[0]) || !$this->isAssoc($arr[0])) {
            return false;
        }

        $keys = array_keys($arr[0]);
        sort($keys);

        foreach ($arr as $r) {
            if (!is_array($r) || !$this->isAssoc($r)) {
                return false;
            }

            $rKeys = array_keys($r);
            sort($rKeys);

            if ($rKeys !== $keys) {
                return false;
            }

            // require primitive for now in tabular rows
            foreach ($r as $v) {
                if (is_array($v)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Encode a multiline string as a TOON triple-quoted block:
     *
     *   bio: """
     *   line1
     *   line2
     *   """
     *
     * Indentation:
     * - key + """ are indented according to $level
     * - inner lines are not indented, so decoded content is exact.
     */
    private function encodeMultilineProperty(string $key, string $value, int $level): string
    {
        $indent = $this->indent($level);
        $lines  = explode("\n", $value);

        $out   = [];
        $out[] = "{$indent}{$key}: \"\"\"";

        foreach ($lines as $line) {
            // no extra indent, content stays exact
            $out[] = $line;
        }

        $out[] = "{$indent}\"\"\"";

        return implode("\n", $out);
    }
}
