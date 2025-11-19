<?php

namespace ToonLite;

/**
 * Configuration options for TOON encoding.
 */
final class EncodeOptions
{
    private int $indentSize;
    private bool $trailingNewline;
    private bool $minify;

    public function __construct(
        int $indentSize = 2,
        bool $trailingNewline = true,
        bool $minify = false
    ) {
        if ($indentSize < 0) {
            throw new \InvalidArgumentException('indentSize must be >= 0');
        }

        $this->indentSize      = $indentSize;
        $this->trailingNewline = $trailingNewline;
        $this->minify          = $minify;
    }

    public static function defaults(): self
    {
        return new self();
    }

    // ----- Getters -----

    public function getIndentSize(): int
    {
        return $this->indentSize;
    }

    public function getTrailingNewline(): bool
    {
        return $this->trailingNewline;
    }

    public function isMinify(): bool
    {
        return $this->minify;
    }

    public function hasTrailingNewline(): bool
    {
        return $this->trailingNewline;
    }

    // ----- Mutable setters -----

    public function setIndentSize(int $indentSize): self
    {
        if ($indentSize < 0) {
            throw new \InvalidArgumentException('indentSize must be >= 0');
        }

        $this->indentSize = $indentSize;
        return $this;
    }

    public function setTrailingNewline(bool $trailingNewline): self
    {
        $this->trailingNewline = $trailingNewline;
        return $this;
    }

    public function setMinify(bool $minify): self
    {
        $this->minify = $minify;
        return $this;
    }

    // ----- Immutable-style "with" helpers -----

    public function withIndentSize(int $indentSize): self
    {
        return new self($indentSize, $this->trailingNewline, $this->minify);
    }

    public function withTrailingNewline(bool $trailingNewline): self
    {
        return new self($this->indentSize, $trailingNewline, $this->minify);
    }

    public function withMinify(bool $minify): self
    {
        return new self($this->indentSize, $this->trailingNewline, $minify);
    }
}
