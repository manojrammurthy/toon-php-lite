<?php

namespace ToonLite;

final class Toon
{
    /**
     * Encode PHP data into TOON.
     *
     * @param mixed $data
     * @param EncodeOptions|int|null $optionsOrIndent
     *        - null => default options
     *        - int  => treated as indentSize (BC helper)
     *        - EncodeOptions => full config
     */
    public static function encode(mixed $data, $optionsOrIndent = null): string
    {
        if ($optionsOrIndent instanceof EncodeOptions) {
            $options = $optionsOrIndent;
        } elseif (is_int($optionsOrIndent)) {
            // BC: Toon::encode($data, 4) => indentSize=4
            $options = (new EncodeOptions())->setIndentSize($optionsOrIndent);
        } else {
            $options = EncodeOptions::defaults();
        }

        // If minify is requested, force indentSize to 0
        if ($options->isMinify()) {
            $options = $options->withIndentSize(0);
        }

        $encoder = new Encoder($options);

        return $encoder->encode($data);
    }

    /**
     * Decode TOON string back into PHP data.
     *
     * @param string $toon
     * @return mixed
     */
    public static function decode(string $toon): mixed
    {
        $decoder = new Decoder();

        return $decoder->decode($toon);
    }
}
