<?php

declare(strict_types=1);

namespace ToonLite\Tests;

use PHPUnit\Framework\TestCase;
use ToonLite\EncodeOptions;
use ToonLite\Toon;

final class EncodeOptionsTest extends TestCase
{
    private array $sample = [
        'id'    => 1,
        'name'  => 'Manoj',
        'items' => [
            ['sku' => 'A1', 'qty' => 2, 'price' => 9.99],
            ['sku' => 'B2', 'qty' => 1, 'price' => 14.5],
        ],
    ];

    public function testDefaultOptionsMatchLegacyEncode(): void
    {
        // Baseline: with defaults
        $withDefaults = Toon::encode($this->sample, EncodeOptions::defaults());
        // Without passing options (legacy API)
        $legacy = Toon::encode($this->sample);

        $this->assertSame($legacy, $withDefaults);
    }

    public function testTrailingNewlineCanBeDisabled(): void
    {
        $withNewline = Toon::encode($this->sample, new EncodeOptions(trailingNewline: true));
        $noNewline   = Toon::encode($this->sample, new EncodeOptions(trailingNewline: false));

        $this->assertTrue(str_ends_with($withNewline, "\n"));
        $this->assertFalse(str_ends_with($noNewline, "\n"));
    }
}
