<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\PhpLint;

class Utils
{

    /**
     * @param iterable<string, string> $pairs
     *
     * @return array<string>
     */
    public static function buildKeyValueStrings(iterable $pairs): array
    {
        $strings = [];
        foreach ($pairs as $key => $value) {
            $strings[] = static::buildKeyValueString($key, $value);
        }

        return $strings;
    }

    public static function buildKeyValueString(string $key, string $value): string
    {
        return "$key=$value";
    }
}
