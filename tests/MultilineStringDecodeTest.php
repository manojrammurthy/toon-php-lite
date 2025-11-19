<?php

declare(strict_types=1);

namespace ToonLite\Tests;

use PHPUnit\Framework\TestCase;
use ToonLite\Toon;
use ToonLite\Exceptions\DecodeException;

class MultilineStringDecodeTest extends TestCase
{
    public function testSimpleMultilineString(): void
    {
        $toon = <<<TOON
description: """
First line
Second line
Third line
"""
TOON;

        $decoded = Toon::decode($toon);

        $this->assertArrayHasKey('description', $decoded);
        $this->assertSame(
            "First line\nSecond line\nThird line",
            $decoded['description']
        );
    }

    public function testNestedMultilineString(): void
    {
        $toon = <<<TOON
user:
  id: 1
  bio: """
  Line A
  Line B
  """
TOON;

        $decoded = Toon::decode($toon);

        $this->assertArrayHasKey('user', $decoded);
        $this->assertIsArray($decoded['user']);

        // We keep indentation exactly as in the TOON block for now.
        $this->assertSame(
            "  Line A\n  Line B",
            $decoded['user']['bio']
        );
    }

    public function testUnterminatedMultilineThrows(): void
    {
        $this->expectException(DecodeException::class);

        $toon = <<<TOON
description: """
Line 1
Line 2
TOON;

        Toon::decode($toon);
    }
}
