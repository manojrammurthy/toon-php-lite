<?php

declare(strict_types=1);

namespace ToonLite\Tests;

use PHPUnit\Framework\TestCase;
use ToonLite\EncodeOptions;
use ToonLite\Toon;

final class EncodeOptionsTest extends TestCase
{
    /**
     * @var array<string,mixed>
     */
    private array $sample = [
        'id'    => 1,
        'name'  => 'Manoj',
        'items' => [
            ['sku' => 'A1', 'qty' => 2, 'price' => 9.99],
            ['sku' => 'B2', 'qty' => 1, 'price' => 14.5],
        ],
    ];

    public function testDefaults(): void
    {
        $opts = new EncodeOptions();

        $this->assertSame(2, $opts->getIndentSize(), 'Default indentSize should be 2');
        $this->assertTrue($opts->getTrailingNewline(), 'Default trailingNewline should be true');
        $this->assertFalse($opts->isMinify(), 'Default minify should be false');
        $this->assertTrue($opts->hasTrailingNewline(), 'BC alias should reflect trailingNewline');
    }

    public function testConstructorOverridesDefaults(): void
    {
        $opts = new EncodeOptions(
            indentSize: 4,
            trailingNewline: false,
            minify: true
        );

        $this->assertSame(4, $opts->getIndentSize());
        $this->assertFalse($opts->getTrailingNewline());
        $this->assertTrue($opts->isMinify());
    }

    public function testSetIndentSizeIsFluentAndUpdatesValue(): void
    {
        $opts = new EncodeOptions();
        $ret  = $opts->setIndentSize(8);

        $this->assertSame($opts, $ret, 'setIndentSize should be fluent');
        $this->assertSame(8, $opts->getIndentSize());
    }

    public function testSetIndentSizeRejectsNegativeValue(): void
    {
        $opts = new EncodeOptions();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('indentSize must be >= 0');

        $opts->setIndentSize(-1);
    }

    public function testSetTrailingNewlineIsFluentAndUpdatesValue(): void
    {
        $opts = new EncodeOptions();
        $ret  = $opts->setTrailingNewline(false);

        $this->assertSame($opts, $ret, 'setTrailingNewline should be fluent');
        $this->assertFalse($opts->getTrailingNewline());
        $this->assertFalse($opts->hasTrailingNewline());
    }

    public function testSetMinifyIsFluentAndUpdatesValue(): void
    {
        $opts = new EncodeOptions();
        $ret  = $opts->setMinify(true);

        $this->assertSame($opts, $ret, 'setMinify should be fluent');
        $this->assertTrue($opts->isMinify());
    }

    public function testWithIndentSizeReturnsNewInstance(): void
    {
        $opts  = new EncodeOptions(indentSize: 2, trailingNewline: true, minify: false);
        $clone = $opts->withIndentSize(6);

        $this->assertNotSame($opts, $clone, 'withIndentSize should return a new instance');
        $this->assertSame(2, $opts->getIndentSize(), 'Original should keep indentSize=2');
        $this->assertSame(6, $clone->getIndentSize(), 'New instance should have indentSize=6');

        $this->assertSame($opts->getTrailingNewline(), $clone->getTrailingNewline());
        $this->assertSame($opts->isMinify(), $clone->isMinify());
    }

    public function testWithTrailingNewlineReturnsNewInstance(): void
    {
        $opts  = new EncodeOptions(indentSize: 2, trailingNewline: true, minify: false);
        $clone = $opts->withTrailingNewline(false);

        $this->assertNotSame($opts, $clone);
        $this->assertTrue($opts->getTrailingNewline(), 'Original should keep trailingNewline=true');
        $this->assertFalse($clone->getTrailingNewline(), 'New should have trailingNewline=false');

        $this->assertSame($opts->getIndentSize(), $clone->getIndentSize());
        $this->assertSame($opts->isMinify(), $clone->isMinify());
    }

    public function testWithMinifyReturnsNewInstance(): void
    {
        $opts  = new EncodeOptions(indentSize: 2, trailingNewline: true, minify: false);
        $clone = $opts->withMinify(true);

        $this->assertNotSame($opts, $clone);
        $this->assertFalse($opts->isMinify(), 'Original should keep minify=false');
        $this->assertTrue($clone->isMinify(), 'New should have minify=true');

        $this->assertSame($opts->getIndentSize(), $clone->getIndentSize());
        $this->assertSame($opts->getTrailingNewline(), $clone->getTrailingNewline());
    }

    /**
     * Legacy behaviour: default options vs legacy encode() call.
     */
    public function testDefaultOptionsMatchLegacyEncode(): void
    {
        // Baseline: with explicit defaults
        $withDefaults = Toon::encode($this->sample, EncodeOptions::defaults());
        // Without passing options (legacy API)
        $legacy = Toon::encode($this->sample);

        $this->assertSame(
            $legacy,
            $withDefaults,
            'Explicit defaults should match legacy encode() behaviour'
        );
    }

    /**
     * Integration: trailingNewline flag actually changes the encoded output.
     */
    public function testTrailingNewlineCanBeDisabled(): void
    {
        $withNewline = Toon::encode(
            $this->sample,
            new EncodeOptions(trailingNewline: true)
        );
        $noNewline = Toon::encode(
            $this->sample,
            new EncodeOptions(trailingNewline: false)
        );

        $this->assertTrue(str_ends_with($withNewline, "\n"));
        $this->assertFalse(str_ends_with($noNewline, "\n"));
    }
}
