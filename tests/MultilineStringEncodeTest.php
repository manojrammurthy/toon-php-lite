<?php

declare(strict_types=1);

namespace ToonLite\Tests;

use PHPUnit\Framework\TestCase;
use ToonLite\Toon;

class MultilineStringEncodeTest extends TestCase
{
    public function testEncodeSimpleMultilineString(): void
    {
        $data = [
            'bio' => "line1\nline2",
        ];

        $toon = Toon::encode($data);

        $expected = <<<TOON
bio: """
line1
line2
"""

TOON;

        // Encoder always appends a trailing newline
        $this->assertSame(rtrim($expected) . "\n", $toon);

        $decoded = Toon::decode($toon);

        $this->assertSame($data, $decoded);
    }

    public function testEncodeNestedMultilineString(): void
    {
        $data = [
            'user' => [
                'bio' => "hello\nworld",
            ],
        ];

        $toon = Toon::encode($data);

        $expected = <<<TOON
user:
  bio: """
hello
world
  """
TOON;

        $this->assertSame(rtrim($expected) . "\n", $toon);

        $decoded = Toon::decode($toon);

        $this->assertSame($data, $decoded);
    }
}
