<?php

namespace ToonLite\Tests;

use PHPUnit\Framework\TestCase;
use ToonLite\Toon;

class RoundTripTest extends TestCase
{
    public function testEncodeDecodeRoundTrip(): void
    {
        $original = [
            'id' => 1,
            'name' => 'Manoj',
            'tags' => ['php', 'ai', 'iot'],
            'items' => [
                ['sku' => 'A1', 'qty' => 2, 'price' => 9.99],
                ['sku' => 'B2', 'qty' => 1, 'price' => 14.5],
            ],
        ];

        $toon = Toon::encode($original);
        $decoded = Toon::decode($toon);

        $this->assertSame($original['id'], $decoded['id']);
        $this->assertSame($original['name'], $decoded['name']);
        $this->assertSame($original['tags'], $decoded['tags']);
        $this->assertSame($original['items'], $decoded['items']);
    }
}
