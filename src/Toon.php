<?php

namespace ToonLite;

use ToonLite\Encoder;
use ToonLite\Decoder;

final class Toon
{
    public static function encode(mixed $data): string
    {
        return (new Encoder())->encode($data);
    }

    public static function decode(string $toon): mixed
    {
        return (new Decoder())->decode($toon);
    }
}
