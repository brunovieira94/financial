<?php

namespace App\Helpers;

use Exception;

class Tools
{
    /** Get a value deep inside a group of arrays, $compositeKey should be a group of keys chained by a dot `.`
     *
     * ### Example:
     * ```php
     * $arr = [
     *    'in' => [
     *        'side' => [
     *              'value' => 5
     *        ]
     *    ]
     * ];
     *
     * $value = Tools::getOrNull($arr, 'in.side.value');
     *
     * assert($value == 5);
     * ```
     */
    public static function getOrNull(mixed $arrayLike, string $compositeKey): mixed
    {
        if (is_null($arrayLike))
            return null;

        $value = $arrayLike;

        try {
            foreach (explode('.', $compositeKey) as $key) {
                if (is_null($value[$key]))
                    return null;
                $value = $value[$key];
            }
        } catch (Exception $e) {
            return null;
        }

        return $value;
    }

    /** Tries to use getOrNull with different composite keys, and returns the result
     * of the first the returned a non-null value, else null.
     *
     * ### Example:
     * ```php
     * $arr = [
     *    'in' => [
     *        'side' => [
     *              'value' => 5
     *        ]
     *    ]
     * ];
     *
     * $value = Tools::getOrNullMultiple($arr, ['in.out.side', 'in.side.value']);
     *
     * assert($value == 5);
     * ```
     * */
    public static function getOrNullMultiple(mixed $arrayLike, array $compositeKeys): mixed
    {
        if (is_null($arrayLike))
            return null;

        $value = null;

        foreach ($compositeKeys as $compositeKey) {
            $value = self::getOrNull($arrayLike, $compositeKey);

            if (isset($value))
                break;
        }

        return $value;
    }

    public static function getOrElse(mixed $arrayLike, string $compositeKey, mixed $else): mixed
    {
        return self::getOrNull($arrayLike, $compositeKey) ?? $else;
    }
}
