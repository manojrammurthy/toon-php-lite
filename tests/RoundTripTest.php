<?php

declare(strict_types=1);

namespace ToonLite\Tests;

use PHPUnit\Framework\TestCase;
use ToonLite\Toon;

final class RoundTripTest extends TestCase
{
    public function testBasicObjectRoundTrip(): void
    {
        $data = [
            'id'      => 1,
            'name'    => 'Manoj',
            'active'  => true,
            'balance' => 1234.50,
            'note'    => 'hello world', // has space → quoted
        ];

        $encoded = Toon::encode($data);
        $decoded = Toon::decode($encoded);

        $this->assertSame($data, $decoded);
    }

    public function testPrimitiveArraysRoundTrip(): void
    {
        $data = [
            'tags'   => ['php', 'ai', 'iot'],
            'scores' => [1, 2, 3.5],
            'flags'  => [true, false, true],
        ];

        $encoded = Toon::encode($data);
        $decoded = Toon::decode($encoded);

        $this->assertSame($data, $decoded);
    }

    public function testTabularArrayRoundTrip(): void
    {
        $data = [
            'items' => [
                ['sku' => 'A1', 'qty' => 2, 'price' => 9.99],
                ['sku' => 'B2', 'qty' => 1, 'price' => 14.5],
            ],
        ];

        $encoded = Toon::encode($data);
        $decoded = Toon::decode($encoded);

        $this->assertSame($data, $decoded);
    }

    public function testMixedObjectWithTabularAndPrimitivesRoundTrip(): void
    {
        $data = [
            'id'      => 42,
            'name'    => 'Manoj',
            'tags'    => ['php', 'ai', 'iot'],
            'items'   => [
                ['sku' => 'A1', 'qty' => 2, 'price' => 9.99],
                ['sku' => 'B2', 'qty' => 1, 'price' => 14.5],
            ],
            'comment' => 'Contains spaces,commas,and:colons',
        ];

        $encoded = Toon::encode($data);
        $decoded = Toon::decode($encoded);

        $this->assertSame($data, $decoded);
    }

    public function testStringsWithSpacesCommasAndColonsRoundTrip(): void
    {
        $data = [
            'simple'       => 'hello',
            'with_space'   => 'hello world',
            'with_comma'   => 'a,b,c',
            'with_colon'   => 'key:value',
            'with_both'    => 'value with,comma and:colon',
            'with_numbers' => 'ref: 123,456',
        ];

        $encoded = Toon::encode($data);
        $decoded = Toon::decode($encoded);

        $this->assertSame($data, $decoded);
    }

    public function testDecodeEncodeDecodeIsStableForHandwrittenToon(): void
    {
        $toon = <<<TOON
id: 1
name: Manoj
tags[3]: php,ai,iot
items[2]{sku,qty,price}:
  A1,2,9.99
  B2,1,14.5
TOON;

        $once = Toon::decode($toon);
        $again = Toon::decode(Toon::encode($once));

        $this->assertSame($once, $again);
    }
}
