<?php

declare(strict_types=1);

namespace ToonLite\Tests;

use PHPUnit\Framework\TestCase;
use ToonLite\Exceptions\DecodeException;
use ToonLite\Toon;

final class DecodeRowCountMismatchTest extends TestCase
{
    public function testPrimitiveArrayCountMismatch(): void
    {
        $toon = <<<TOON
tags[3]: php,ai
TOON;

        $this->expectException(DecodeException::class);
        $this->expectExceptionMessage('Value count mismatch for \'tags\'');
        Toon::decode($toon);
    }

    public function testListRowCountMismatch(): void
    {
        $toon = <<<TOON
tags[2]:
  - php
TOON;

        try {
            Toon::decode($toon);
            $this->fail('Expected DecodeException was not thrown');
        } catch (DecodeException $e) {
            $this->assertStringContainsString("Row count mismatch for 'tags'", $e->getMessage());
            $this->assertStringContainsString('expected 2', $e->getMessage());
            $this->assertStringContainsString('got 1', $e->getMessage());
        }
    }

    public function testTabularRowCountMismatch(): void
    {
        $toon = <<<TOON
items[3]{sku,qty,price}:
  A1,2,9.99
  B2,1,14.5
TOON;

        try {
            Toon::decode($toon);
            $this->fail('Expected DecodeException was not thrown');
        } catch (DecodeException $e) {
            $this->assertStringContainsString("Row count mismatch for 'items'", $e->getMessage());
            $this->assertStringContainsString('expected 3', $e->getMessage());
            $this->assertStringContainsString('got 2', $e->getMessage());
        }
    }
}
