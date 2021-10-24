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

namespace Conjoon\Http\Query;

use Conjoon\Core\ParameterBag;
use BadMethodCallException;
use Conjoon\Util\Jsonable;

/**
 * A ResourceQuery provides an interface for a validated and certified collection
 * of parameters that can safely be used in the low level API.
 * The query parameters available with Requests sent by the client should be translated
 * by the QueryTranslator, which then validates the parameters and returns a Resource
 * Query for further use.
 * A resource query  wraps a ParameterBag and delegates all method calls involving getters
 * to the Parameter Bag using __call, including querying the properties using __get.
 *
 * Class ResourceQuery
 * @package Conjoon\Http\Query
 */
abstract class ResourceQuery implements Jsonable
{

    protected ParameterBag $parameters;

    /**
     * ResourceQuery constructor.
     * @param ParameterBag $parameters
     */
    public function __construct(ParameterBag $parameters)
    {
        $this->parameters = $parameters;
    }


    /**
     * Delegates to the ParameterBag's __call.
     *
     * @param string $method
     * @param $arguments
     *
     * @return mixed
     *
     * @throws BadMethodCallException
     */
    public function __call(string $method, $arguments)
    {
        return $this->parameters->{$method}(...$arguments);
    }


    /**
     * Delegates to the ParameterBag's __get.
     *
     * @param $key
     * @return mixed|null
     */
    public function __get($key)
    {
        return $this->parameters->{$key};
    }


    /**
     * Delegates to the ParameterBag's has().
     *
     * @param $key
     *
     * @return bool
     */
    public function has($key): bool
    {
        return $this->parameters->has($key);
    }


    /**
     * Delegates to the ParameterBag's toJson().
     *
     * @return array
     */
    public function toJson(): array
    {
        return $this->parameters->toJson();
    }
}
