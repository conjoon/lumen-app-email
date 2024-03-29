<?php

/**
 * conjoon
 * lumen-app-email
 * Copyright (C) 2022 Thorsten Suckow-Homberg https://github.com/conjoon/lumen-app-email
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

namespace Tests\App\Http\V0\Query\MessageItem;

use App\Http\V0\Query\MessageItem\AbstractMessageItemQueryTranslator;
use App\Http\V0\Query\MessageItem\GetRequestQueryTranslator;
use App\Http\V0\Query\MessageItem\IndexRequestQueryTranslator;
use Conjoon\Core\ParameterBag;
use Conjoon\Http\Query\InvalidParameterResourceException;
use Conjoon\Http\Query\InvalidQueryException;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionException;
use Tests\TestCase;

/**
 * Class GetRequestQueryTranslatorTest
 * @package Tests\App\Http\V0\Query\MessageItem
 */
class GetRequestQueryTranslatorTest extends TestCase
{
    /**
     *
     */
    public function testClass()
    {
        $inst = new GetRequestQueryTranslator();

        $this->assertInstanceOf(AbstractMessageItemQueryTranslator::class, $inst);
    }


    /**
     * @throws ReflectionException
     */
    public function testGetExpectedParameters()
    {
        $translator = new GetRequestQueryTranslator();
        $reflection = new ReflectionClass($translator);

        $getExpectedParametersReflection = $reflection->getMethod("getExpectedParameters");
        $getExpectedParametersReflection->setAccessible(true);

        $expected = $getExpectedParametersReflection->invokeArgs($translator, []);

        $this->assertEquals([
            "attributes",
            "messageItemId"
        ], $expected);
    }


    /**
     * Extract parameters not Request
     * @throws ReflectionException
     */
    public function testExtractParameters()
    {
        $translator = new GetRequestQueryTranslator();
        $reflection = new ReflectionClass($translator);

        $extractParametersReflection = $reflection->getMethod("extractParameters");
        $extractParametersReflection->setAccessible(true);

        $request = new Request([
            "attributes" => "*,size",
            "foo" => "bar"]);

        $request->setRouteResolver(function () {
            return new class {
                public function parameter()
                {
                    return "744";
                }
            };
        });

        $extracted = $extractParametersReflection->invokeArgs($translator, [$request]);

        $this->assertEquals([
            "messageItemId" => "744",
            "attributes" => "*,size",
        ], $extracted);
    }


    /**
     * @throws ReflectionException
     */
    public function testTranslateParameters()
    {
        $translator = new GetRequestQueryTranslator();
        $reflection = new ReflectionClass($translator);

        $translateParametersReflection = $reflection->getMethod("translateParameters");
        $translateParametersReflection->setAccessible(true);


        $getExpectedAttributes = function ($exclude = [], $add = []) {

            $parameters = $this->getDefaultAttributes();
            array_walk(
                $parameters,
                fn(&$item, $key) => in_array($key, $exclude) ? $item = false : null
            );

            $parameters = array_filter($parameters, fn ($item) => $item !== false);
            foreach ($add as $addKey => $addValue) {
                $parameters[$addKey] = $addValue;
            }

            return $parameters;
        };


        $queries = [
            [
                "input" => ["limit" => "-1", "messageItemId" => "744"],
                "output" => [
                    "attributes" => $getExpectedAttributes(
                        ["html", "plain"],
                        ["html" => $this->getDefaultAttributes()["html"],
                        "plain" => $this->getDefaultAttributes()["plain"]]
                    ),
                    "filter" => [[
                        "property" => "id",
                        "value" => ["744"],
                        "operator" => "in",
                    ]],
                    "limit" => "-1"
                ]
            ],
            [
                "input" => ["attributes" => "*,previewText", "messageItemId" => "744"],
                "output" => [
                    "attributes" => $getExpectedAttributes(["html", "plain"]),
                    "filter" => [[
                        "property" => "id",
                        "value" => ["744"],
                        "operator" => "in"
                    ]]
                ]
            ]
        ];

        foreach ($queries as $query) {
            $source = new ParameterBag($query["input"]);
            $result = $translateParametersReflection->invokeArgs($translator, [
                $source
            ]);

            $json = $result->toJson();

            $this->assertNotSame($source, $result);

            $output = $query["output"];
            ksort($output);
            ksort($json);

            $this->assertSame($output, $json);
        }
    }


    /**
     * @return mixed
     * @throws ReflectionException
     */
    protected function getDefaultAttributes()
    {
        $translator = new GetRequestQueryTranslator();
        $reflection = new ReflectionClass($translator);

        $translateParametersReflection = $reflection->getMethod("getDefaultAttributes");
        $translateParametersReflection->setAccessible(true);

        return $translateParametersReflection->invokeArgs($translator, []);
    }
}
