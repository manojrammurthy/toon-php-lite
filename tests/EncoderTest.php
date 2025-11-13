<?php

namespace ToonLite\Tests;

use PHPUnit\Framework\TestCase;
use ToonLite\Encoder;

class EncoderTest extends TestCase
{
    public function testEncodeSimpleObject(): void
    {
        $encoder = new Encoder();

        $data = [
            'id' => 1,
            'name' => 'Manoj',
            'active' => true,
        ];

        $toon = $encoder->encode($data);

        $this->assertStringContainsString('id: 1', $toon);
        $this->assertStringContainsString('name: Manoj', $toon);
        $this->assertStringContainsString('active: true', $toon);
    }

    public function testEncodePrimitiveArray(): void
    {
        $encoder = new Encoder();

        $data = [
            'tags' => ['php', 'ai', 'iot'],
        ];

        $toon = $encoder->encode($data);

        $this->assertSame("tags[3]: php,ai,iot\n", $toon);
    }

    public function testEncodeTabularArray(): void
    {
        $encoder = new Encoder();

        $data = [
            'items' => [
                ['sku' => 'A1', 'qty' => 2, 'price' => 9.99],
                ['sku' => 'B2', 'qty' => 1, 'price' => 14.5],
            ],
        ];

        $toon = $encoder->encode($data);

        $expectedHeader = "items[2]{sku,qty,price}:";
        $this->assertStringContainsString($expectedHeader, $toon);
        $this->assertStringContainsString("A1,2,9.99", $toon);
        $this->assertStringContainsString("B2,1,14.5", $toon);
    }
}
