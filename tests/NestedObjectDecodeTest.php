<?php

declare(strict_types=1);

namespace ToonLite\Tests;

use PHPUnit\Framework\TestCase;
use ToonLite\Toon;

final class NestedObjectDecodeTest extends TestCase
{
    public function testSimpleNestedObject(): void
    {
        $toon = <<<TOON
user:
  id: 1
  name: Manoj
TOON;

        $decoded = Toon::decode($toon);

        $this->assertSame(
            [
                'user' => [
                    'id'   => 1,
                    'name' => 'Manoj',
                ],
            ],
            $decoded
        );
    }

    public function testNestedObjectWithArrays(): void
    {
        $toon = <<<TOON
user:
  id: 1
  name: Manoj
  tags[3]: php,ai,iot
TOON;

        $decoded = Toon::decode($toon);

        $this->assertSame(
            [
                'user' => [
                    'id'   => 1,
                    'name' => 'Manoj',
                    'tags' => ['php', 'ai', 'iot'],
                ],
            ],
            $decoded
        );
    }

    public function testRoundTripNestedObject(): void
    {
        $data = [
            'user' => [
                'id'    => 1,
                'name'  => 'Manoj',
                'tags'  => ['php', 'ai', 'iot'],
                'items' => [
                    ['sku' => 'A1', 'qty' => 2, 'price' => 9.99],
                    ['sku' => 'B2', 'qty' => 1, 'price' => 14.5],
                ],
            ],
        ];

        $encoded = Toon::encode($data);
        $decoded = Toon::decode($encoded);

        $this->assertSame($data, $decoded);
    }
}
