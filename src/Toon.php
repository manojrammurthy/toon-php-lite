<?php

namespace ToonLite;

use ToonLite\Encoder;
use ToonLite\Decoder;

class Toon
{
    public static function encode(mixed $value, ?EncodeOptions $options = null): string
    {
        $encoder = new Encoder($options);
        return $encoder->encode($value);
    }

    public static function decode(string $text): mixed
    {
        $decoder = new Decoder();
        return $decoder->decode($text);
    }
}
