<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2021 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

namespace Conjoon\Core;

use Conjoon\Util\ArrayUtil;
use BadMethodCallException;
use Conjoon\Util\Jsonable;

/**
 * A ParameterBag providing object syntax for accessing array arguments and
 * type conversion via magic methods.
 *
 * @example
 *
 *   $bag = new ParameterBag(["foo" => 1, "bar" => ["type" => "value"]]);
 *
 *   $bag->has("foo") // true
 *   $bag->foo; // 1
 *   $bag->getString("foo") // "1"
 *   $bag->snafu = "foobar"; // adds a new property "snafu"
 *
 *
 * Class ParameterBag
 * @package Conjoon\Core
 *
 * @method string|null getString(string)
 * @method int|null getInt(string)
 * @method int|null getBool(string)
 */
class ParameterBag implements Jsonable
{

    /**
     * @var array
     */
    private array $data;


    /**
     * ParameterBag constructor.
     *
     * @param array $data
     * @param array $defaults
     */
    public function __construct(array $data = [], array $defaults = [])
    {
        $this->data = ArrayUtil::mergeIf($data, $defaults);
    }


    /**
     * Provides access to getString(), getInt() and getBool().
     * Returns null if the property is not existing.
     *
     * @param string $method
     * @param $arguments
     * @return bool|int|string|null
     */
    public function __call(string $method, $arguments)
    {
        if (strpos($method, 'get') === 0 && count($arguments)) {
            $property = $arguments[0];

            $type = strtolower(substr($method, 3));

            if (in_array($type, ["int", "string", "bool"])) {
                if (!$this->has($property)) {
                    return null;
                }

                switch ($type) {
                    case "int":
                        return (int)$this->{$property};
                    case "string":
                        return (string)$this->{$property};
                    case "bool":
                        return (bool)$this->{$property};
                }
            }
        }

        throw new BadMethodCallException("no method named \"$method\" found.");
    }


    /**
     * Returns the property if existing, otherwise null.
     *
     * @param $key
     * @return mixed|null
     */
    public function __get($key)
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : null;
    }


    /**
     * Sets the property. Allows setting properties that do not exist yet.
     *
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }


    /**
     * Unsets the property.
     *
     * @param $key
     */
    public function __unset($key)
    {
        unset($this->data[$key]);
    }


    /**
     * Returns true if the property exists in this ParameterBag, otherwise false.
     *
     * @param $key
     * @return bool
     */
    public function has($key): bool
    {
        return array_key_exists($key, $this->data);
    }


    /**
     * Returns the property names this ParameterBag maintains.+
     *
     * @return int[]|string[]
     */
    public function keys()
    {
        return array_keys($this->data);
    }


    /**
     * Returns a json-array representative of this ParameterBag.
     *
     * @return array
     */
    public function toJson(): array
    {
        return json_decode(json_encode($this->data), true);
    }
}
