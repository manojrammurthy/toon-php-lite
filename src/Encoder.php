<?php

namespace ToonLite;

use ToonLite\Exceptions\EncodeException;

class Encoder
{
    private int $indent = 2;

    public function encode(mixed $value): string
    {
        return rtrim($this->encodeValue($value, 0)) . "\n";
    }

    private function encodeValue(mixed $value, int $level): string
    {
        if ($value === null) return "null";
        if (is_bool($value)) return $value ? "true" : "false";
        if (is_int($value) || is_float($value)) return (string)$value;
        if (is_string($value)) return $this->encodeString($value);

        if (is_array($value)) {
            if ($this->isAssoc($value)) {
                return $this->encodeObject($value, $level);
            }
            return $this->encodeArray($value, $level);
        }

        throw new EncodeException("Unsupported type: " . gettype($value));
    }

    private function encodeObject(array $obj, int $level): string
    {
        $indent = str_repeat(" ", $level * $this->indent);
        $out = [];

        foreach ($obj as $key => $val) {
            if (is_array($val)) {
                if ($this->isAssoc($val)) {
                    $out[] = "{$indent}{$key}:";
                    $out[] = $this->encodeObject($val, $level + 1);
                } else {
                    $out[] = $this->encodeArrayProp($key, $val, $level);
                }
            } else {
                $encoded = $this->encodeValue($val, $level);
                $out[] = "{$indent}{$key}: {$encoded}";
            }
        }

        return implode("\n", $out);
    }

    private function encodeArrayProp(string $key, array $arr, int $level): string
    {
        $count = count($arr);
        $indent = str_repeat(" ", $level * $this->indent);

        if ($count === 0) return "{$indent}{$key}[0]:";

        if ($this->isPrimitiveArray($arr)) {
            $values = array_map(fn($v) => $this->encodeValue($v, $level), $arr);
            return "{$indent}{$key}[{$count}]: " . implode(",", $values);
        }

        if ($this->isUniformObjectArray($arr)) {
            return $this->encodeTabular($key, $arr, $level);
        }

        return $this->encodeListArray($key, $arr, $level);
    }

    private function encodeArray(array $arr, int $level): string
    {
        $count = count($arr);
        $indent = str_repeat(" ", $level * $this->indent);

        if ($this->isPrimitiveArray($arr)) {
            $values = array_map(fn($v) => $this->encodeValue($v, $level), $arr);
            return "{$indent}[{$count}]: " . implode(",", $values);
        }

        $out = ["{$indent}[{$count}]:"];

        foreach ($arr as $item) {
            $subIndent = str_repeat(" ", ($level + 1) * $this->indent);

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

    private function encodeTabular(string $key, array $rows, int $level): string
    {
        $indent = str_repeat(" ", $level * $this->indent);
        $count = count($rows);
        $keys = array_keys($rows[0]);

        $header = "{$indent}{$key}[{$count}]{" . implode(",", $keys) . "}:";
        $out = [$header];

        foreach ($rows as $row) {
            $subIndent = str_repeat(" ", ($level + 1) * $this->indent);
            $vals = [];
            foreach ($keys as $k) {
                $vals[] = $this->encodeValue($row[$k], $level + 1);
            }
            $out[] = $subIndent . implode(",", $vals);
        }

        return implode("\n", $out);
    }

    private function encodeListArray(string $key, array $arr, int $level): string
    {
        $count = count($arr);
        $indent = str_repeat(" ", $level * $this->indent);

        $out = ["{$indent}{$key}[{$count}]:"];

        foreach ($arr as $item) {
            $subIndent = str_repeat(" ", ($level + 1) * $this->indent);
            $out[] = "{$subIndent}- " . $this->encodeValue($item, $level + 1);
        }

        return implode("\n", $out);
    }

    private function encodeString(string $s): string
    {
        if (preg_match('/[\s,:]/', $s)) {
            return '"' . addcslashes($s, '"\\') . '"';
        }
        return $s;
    }

    private function isAssoc(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    private function isPrimitiveArray(array $arr): bool
    {
        foreach ($arr as $v) {
            if (is_array($v)) return false;
        }
        return true;
    }

    private function isUniformObjectArray(array $arr): bool
    {
        if (!$arr || !is_array($arr[0]) || !$this->isAssoc($arr[0])) return false;

        $keys = array_keys($arr[0]);
        sort($keys);

        foreach ($arr as $r) {
            if (!is_array($r) || !$this->isAssoc($r)) return false;

            $rKeys = array_keys($r);
            sort($rKeys);
            if ($rKeys !== $keys) return false;

            // require primitive for now in tabular rows
            foreach ($r as $v) {
                if (is_array($v)) return false;
            }
        }

        return true;
    }
}
