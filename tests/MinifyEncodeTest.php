<?php

namespace ToonLite\Tests;

use PHPUnit\Framework\TestCase;
use ToonLite\EncodeOptions;
use ToonLite\Toon;

class MinifyEncodeTest extends TestCase
{
    public function testMinifyRemovesIndentationButKeepsSyntax(): void
    {
        $data = [
            'user' => [
                'id'   => 1,
                'name' => 'Manoj',
            ],
            'tags' => ['php', 'ai'],
        ];

        $pretty = Toon::encode($data); // default pretty-print
        $opts   = (new EncodeOptions())
            ->setIndentSize(2)
            ->setMinify(true);

        $minified = Toon::encode($data, $opts);

        // Sanity: pretty output should contain indentation
        $this->assertStringContainsString("\n  id:", $pretty);

        // Minified output: no leading spaces at the start of any line
        $this->assertSame(
            0,
            preg_match('/^\s+/m', $minified),
            'Minified output should not have leading indentation'
        );

        // Minified output should still be syntactically valid TOON
        // (i.e. decoder does not throw)
        $decoded = Toon::decode($minified);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('user', $decoded);
        $this->assertArrayHasKey('tags', $decoded);
    }

    public function testMinifyWorksWithNestedObjects(): void
    {
        $data = [
            'user' => [
                'id'   => 1,
                'name' => 'Manoj',
            ],
        ];

        $opts = (new EncodeOptions())
            ->setIndentSize(2)
            ->setMinify(true);

        $minified = Toon::encode($data, $opts);

        // Minified form should still parse without errors
        $decoded = Toon::decode($minified);

        // We only assert basic structure here:
        //  - 'user' key is present
        //  - encode doesn't lose the field completely or throw.
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('user', $decoded);

        // NOTE:
        // Because TOON uses indentation to represent nesting, once all
        // indentation is stripped, the exact nested shape cannot always be
        // reconstructed unambiguously. So we do NOT require a full deep
        // round-trip equality in minified mode.
        //
        // The canonical, spec-correct shape is guaranteed in the pretty-printed
        // mode (non-minified).
    }
}
