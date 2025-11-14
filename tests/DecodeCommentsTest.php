<?php

declare(strict_types=1);

namespace ToonLite\Tests;

use PHPUnit\Framework\TestCase;
use ToonLite\Toon;

final class DecodeCommentsTest extends TestCase
{
    public function testIgnoresFullLineComments(): void
    {
        $toon = <<<TOON
# This is a comment
// Another comment line
id: 1
name: Manoj
TOON;

        $decoded = Toon::decode($toon);

        $this->assertSame(1, $decoded['id']);
        $this->assertSame('Manoj', $decoded['name']);
        $this->assertCount(2, $decoded);
    }

    public function testIgnoresInlineCommentsWithHash(): void
    {
        $toon = <<<TOON
id: 1  # user id
name: Manoj   # inline comment
TOON;

        $decoded = Toon::decode($toon);

        $this->assertSame(1, $decoded['id']);
        $this->assertSame('Manoj', $decoded['name']);
    }

    public function testIgnoresInlineCommentsWithDoubleSlash(): void
    {
        $toon = <<<TOON
id: 1  // user id
name: Manoj // inline comment
TOON;

        $decoded = Toon::decode($toon);

        $this->assertSame(1, $decoded['id']);
        $this->assertSame('Manoj', $decoded['name']);
    }

    public function testDoesNotBreakHttpUrl(): void
    {
        $toon = <<<TOON
url: http://example.com/path
TOON;

        $decoded = Toon::decode($toon);

        $this->assertSame('http://example.com/path', $decoded['url']);
    }
}
