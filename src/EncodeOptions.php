<?php

namespace ToonLite;

/**
 * Configuration options for TOON encoding.
 *
 * - indentSize: number of spaces per indent level
 * - trailingNewline: whether to ensure the output ends with "\n"
 */
final class EncodeOptions
{
    private int $indentSize;
    private bool $trailingNewline;

    public function __construct(
        int $indentSize = 2,
        bool $trailingNewline = true
    ) {
        if ($indentSize < 0) {
            throw new \InvalidArgumentException('indentSize must be >= 0');
        }

        $this->indentSize = $indentSize;
        $this->trailingNewline = $trailingNewline;
    }

    public static function defaults(): self
    {
        return new self();
    }

    public function getIndentSize(): int
    {
        return $this->indentSize;
    }

    public function getTrailingNewline(): bool
    {
        return $this->trailingNewline;
    }

    public function withIndentSize(int $indentSize): self
    {
        return new self($indentSize, $this->trailingNewline);
    }

    public function withTrailingNewline(bool $trailingNewline): self
    {
        return new self($this->indentSize, $trailingNewline);
    }
}
