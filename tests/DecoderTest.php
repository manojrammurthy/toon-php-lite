<?php

namespace ToonLite\Tests;

use PHPUnit\Framework\TestCase;
use ToonLite\Decoder;

class DecoderTest extends TestCase
{
    public function testDecodeSimpleObject(): void
    {
        $decoder = new Decoder();

        $toon = <<<TOON
id: 1
name: Manoj
active: true
TOON;

        $data = $decoder->decode($toon);

        $this->assertSame(1, $data['id']);
        $this->assertSame('Manoj', $data['name']);
        $this->assertTrue($data['active']);
    }

    public function testDecodePrimitiveArray(): void
    {
        $decoder = new Decoder();

        $toon = "tags[3]: php,ai,iot";

        $data = $decoder->decode($toon);

        $this->assertSame(['php', 'ai', 'iot'], $data['tags']);
    }

    public function testDecodeTabularArray(): void
    {
        $decoder = new Decoder();

        $toon = <<<TOON
items[2]{sku,qty,price}:
  A1,2,9.99
  B2,1,14.5
TOON;

        $data = $decoder->decode($toon);

        $this->assertCount(2, $data['items']);

        $this->assertSame('A1', $data['items'][0]['sku']);
        $this->assertSame(2, $data['items'][0]['qty']);
        $this->assertSame(9.99, $data['items'][0]['price']);

        $this->assertSame('B2', $data['items'][1]['sku']);
        $this->assertSame(1, $data['items'][1]['qty']);
        $this->assertSame(14.5, $data['items'][1]['price']);
    }
}
