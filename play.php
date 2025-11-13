<?php

require __DIR__ . '/vendor/autoload.php';

use ToonLite\Toon;

$data = [
    'id' => 1,
    'name' => 'Manoj',
    'tags' => ['php', 'ai', 'iot'],
    'items' => [
        ['sku' => 'A1', 'qty' => 2, 'price' => 9.99],
        ['sku' => 'B2', 'qty' => 1, 'price' => 14.5],
    ],
];

$toon = Toon::encode($data);
echo "TOON:\n$toon\n";

$back = Toon::decode($toon);
var_dump($back);
