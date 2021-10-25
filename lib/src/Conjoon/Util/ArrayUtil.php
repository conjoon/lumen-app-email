<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2019-2021 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without restriction,
 * including without limitation the rights to use, copy, modify, merge,
 * publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE
 * USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

declare(strict_types=1);

namespace Conjoon\Util;

use InvalidArgumentException;

/**
 * Class ArrayUtil
 *
 * @package Conjoon\Util
 */
class ArrayUtil
{

    /**
     * Returns an array that contains only the keys specified in $keys
     *
     * @example
     *   $data = [
     *     "foo" => "bar", "bar" => "snafu", 3 => 4
     *  ];
     *  $keys = ["foo", "bar"];
     *
     *  ArrayUtil::intersect($data, $keys)); // returns ["foo" => "bar", "bar" => "snafu"]
     *
     * @param array $data
     * @param array $keys
     *
     * @return array
     */
    public static function intersect(array $data, array $keys): array
    {
        return array_intersect_key($data, array_flip($keys));
    }


    /**
     * Returns a merged assoc array.
     * Keys that exist in $source and $target will not get overwritten.
     *
     * @param array $target
     * @param array $source
     * @return array
     * @example
     *   $target = [
     *     "foo" => "bar", "bar" => "snafu", "0_3" => 4
     *  ];
     *  $source = ["foo" => "nono!", "anotherBar" => "yap"];
     *
     *  ArrayUtil::assign($target, $source));
     *             // returns ["foo" => "bar",
     *             //          "bar" => "snafu",
     *             //          "anotherBar" => "yap",
     *             //          "0_3" => 4
     *             //         ]
     *
     * @throws InvalidArgumentException if $target or $source contain numeric keys
     */
    public static function mergeIf(array $target, array $source): array
    {
        $chk = function ($value, $key) {
            if (is_int($key)) {
                throw new InvalidArgumentException("argument must not contain numeric keys");
            }
        };

        array_walk($source, $chk);
        array_walk($target, $chk);

        $new = array_merge([], $target);
        foreach ($source as $key => $item) {
            if (!array_key_exists($key, $new)) {
                $new[$key] = $item;
            }
        }
        return $new;
    }


    /**
     * String replacement for optional chaining operator.
     *
     * @param string $chain
     * @param array $target
     * @param null $default
     *
     * @example
     *
     *    $source = [
     *        "foo" => ["bar" => 34]
     *    ];
     *    ArrayUtil::unchain("foo.bar", $source); // 34
     *
     *    $source = [
     *        "foo" => ["snafu" => ["bar" => 34]]
     *    ];
     *    ArrayUtil::unchain("foo.bar", $source); // null
     *
     *    $source = [
     *        "foo" => ["snafu" => ["bar" => 34]]
     *    ];
     *    ArrayUtil::unchain("foo.bar", $source, true); // true
     *
     *
     *
     * @return mixed|null
     */
    public static function unchain(string $chain, array $target, $default = null)
    {
        if (!is_array($target)) {
            return $default;
        }

        $parts = explode(".", $chain);

        while (is_array($target) && count($parts)) {
            $target = $target[array_shift($parts)] ?? null;
        }

        if (!$target) {
            return $default;
        }

        return $target;
    }
}
