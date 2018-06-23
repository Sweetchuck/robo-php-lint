<?php

namespace Sweetchuck\Robo\PhpLint;

class Utils
{
    public static function buildKeyValueStrings(iterable $pairs): array
    {
        $strings = [];
        foreach ($pairs as $key => $value) {
            $strings[] = static::buildKeyValueString($key, $value);
        }

        return $strings;
    }

    public static function buildKeyValueString($key, $value): string
    {
        return "$key=$value";
    }
}
