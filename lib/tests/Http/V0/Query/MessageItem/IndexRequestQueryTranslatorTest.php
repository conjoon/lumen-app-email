<?php

/**
 * conjoon
 * lumen-app-email
 * Copyright (C) 2021-2022 Thorsten Suckow-Homberg https://github.com/conjoon/lumen-app-email
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
use App\Http\V0\Query\MessageItem\IndexRequestQueryTranslator;
use Conjoon\Core\ParameterBag;
use Conjoon\Http\Query\InvalidParameterResourceException;
use Conjoon\Http\Query\InvalidQueryException;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionException;
use Tests\TestCase;

/**
 * Class IndexRequestQueryTranslatorTest
 * @package Tests\App\Http\V0\Query\MessageItem
 */
class IndexRequestQueryTranslatorTest extends TestCase
{
    /**
     *
     */
    public function testClass()
    {
        $inst = new IndexRequestQueryTranslator();

        $this->assertInstanceOf(AbstractMessageItemQueryTranslator::class, $inst);
    }


    /**
     * @throws ReflectionException
     */
    public function testGetExpectedParameters()
    {
        $translator = new IndexRequestQueryTranslator();
        $reflection = new ReflectionClass($translator);

        $getExpectedParametersReflection = $reflection->getMethod("getExpectedParameters");
        $getExpectedParametersReflection->setAccessible(true);

        $expected = $getExpectedParametersReflection->invokeArgs($translator, []);

        $this->assertEquals([
            "limit",
            "start",
            "sort",
            "attributes",
            "options",
            "filter"
        ], $expected);
    }


    /**
     * Extract parameters not Request
     * @throws ReflectionException
     */
    public function testExtractParameters()
    {
        $translator = new IndexRequestQueryTranslator();
        $reflection = new ReflectionClass($translator);

        $extractParametersReflection = $reflection->getMethod("extractParameters");
        $extractParametersReflection->setAccessible(true);

        $request = new Request([
            "limit" => 0,
            "filter" => json_encode([["property" => "id", "operator" => "in", "value" => ["1", "2", "3"]]]),
            "start" => 3,
            "foo" => "bar"]);

        $extracted = $extractParametersReflection->invokeArgs($translator, [$request]);

        $this->assertEquals([
            "limit" => 0,
            "filter" => json_encode([["property" => "id", "operator" => "in", "value" => ["1", "2", "3"]]]),
            "start" => 3
        ], $extracted);
    }


    /**
     * getDefaultSort()
     * @throws ReflectionException
     */
    public function testGetDefaultSort()
    {
        $translator = new IndexRequestQueryTranslator();
        $reflection = new ReflectionClass($translator);

        $getDefaultSortReflection = $reflection->getMethod("getDefaultSort");
        $getDefaultSortReflection->setAccessible(true);

        $sort = $getDefaultSortReflection->invokeArgs($translator, []);

        $this->assertEquals($this->getDefaultSort(), $sort);
    }


    /**
     * @throws ReflectionException
     */
    public function testTranslateParameters()
    {
        $translator = new IndexRequestQueryTranslator();
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
                "input" => ["limit" => "-1"],
                "output" => [
                    "start" => 0,
                    "limit" => -1,
                    "sort" => $this->getDefaultSort(),
                    "attributes" => $getExpectedAttributes(
                        ["html", "plain"],
                        ["html" => $this->getDefaultAttributes()["html"],
                        "plain" => $this->getDefaultAttributes()["plain"]]
                    )
                ]
            ],
            [
                "input" => ["start" => 2, "limit" => "5", "attributes" => "*"],
                "output" => [
                    "start" => 2,
                    "limit" => 5,
                    "sort" => $this->getDefaultSort(),
                    "attributes" => $getExpectedAttributes(
                        [],
                        ["html" => $this->getDefaultAttributes()["html"],
                        "plain" => $this->getDefaultAttributes()["plain"]]
                    )
                ]
            ],
            [
                "input" => ["start" => 0, "limit" => "10", "attributes" => "*,previewText"],
                "output" => [
                    "start" => 0,
                    "limit" => 10,
                    "sort" => $this->getDefaultSort(),
                    "attributes" => $getExpectedAttributes(["html", "plain"])
                ]
            ],
            [
                "input" => ["start" => 0, "limit" => "10", "attributes" => "*,cc,bcc"],
                "output" => [
                    "start" => 0,
                    "limit" => 10,
                    "sort" => $this->getDefaultSort(),
                    "attributes" => $getExpectedAttributes(["cc", "bcc"])
                ]
            ],
            [
                "input" => [
                    "start" => 0,
                    "limit" => "20",
                    "sort"  => json_encode($this->getDefaultSort()),
                    "options" => json_encode([
                        "previewText" => [
                            "length" => 20,
                            "trimApi" => false
                        ]
                    ])
                ],
                "output" => [
                    "start" => 0,
                    "limit" => 20,
                    "sort" => $this->getDefaultSort(),
                    "attributes" => $getExpectedAttributes(
                        [],
                        ["plain" => ["length" => 20, "trimApi" => false],
                        "html"   => ["length" => 20, "trimApi" => false]
                        ]
                    )
                    ]
            ],
            [
                "input" => [
                    "start" => 0,
                    "limit" => "20",
                    "options" => json_encode([
                        "previewText" => [
                            "html" => true,
                            "plain" => [
                                "length" => 200,
                                "trimApi" => true
                            ]

                        ]
                    ])
                ],
                "output" => [
                    "start" => 0,
                    "limit" => 20,
                    "sort" => $this->getDefaultSort(),
                    "attributes" => $getExpectedAttributes(
                        [],
                        ["plain" => ["length" => 200, "trimApi" => true],
                        "html" => true]
                    )
                ]
            ],
            [
                "input" => [
                    "filter" => json_encode([["property" => "id", "operator" => "in", "value" => ["1", "2", "3"]]]),
                ],
                "output" => [
                    "filter" => [["property" => "id", "operator" => "in", "value" => ["1", "2", "3"]]],
                    "sort" => $this->getDefaultSort(),
                    "attributes" => $getExpectedAttributes(
                        [],
                        ["html" => $this->getDefaultAttributes()["html"],
                            "plain" => $this->getDefaultAttributes()["plain"]]
                    )
                ]
            ],
            [
                "input" => [
                    "filter" => json_encode([["property" => "id", "operator" => "in", "value" => ["1", "2", "3"]]]),
                    "attributes" => "previewText"
                ],
                "output" => [
                    "filter" => [["property" => "id", "operator" => "in", "value" => ["1", "2", "3"]]],
                    "sort" => $this->getDefaultSort(),
                    "attributes" => [
                        "html" => $this->getDefaultAttributes()["html"],
                        "plain" => $this->getDefaultAttributes()["plain"]
                    ]
                ]
            ],
            [
                "input" => [
                    "filter" => json_encode([["property" => "id", "value" => 2, "operator" => "="]]),
                    "attributes" => "recent",
                    "limit" => -1
                ],
                "output" => [
                    "filter" => [["property" => "id", "value" => 2, "operator" => "="]],
                    "sort" => $this->getDefaultSort(),
                    "start" => 0,
                    "limit" => -1,
                     "attributes" => [
                        "recent" => true
                    ]
                ]
            ],
            [
                "input" => [
                    "filter" => json_encode([["property" => "id", "operator" => "in", "value" => ["1", "2", "3"]]]),
                    "attributes" => "previewText",
                    "target" => "messageItem",
                    "options" => json_encode(
                        ["previewText" => [
                            "plain" => [
                                "length" => 200,
                                "precedence" => true
                            ],
                            "html" => [
                                "length" => 200
                            ]
                        ]]
                    ),
                    "limit" => -1
                ],
                "output" => [
                    "filter" => [["property" => "id", "operator" => "in", "value" => ["1", "2", "3"]]],
                    "target" => "messageItem",
                    "attributes" => [
                        "html" => ["length" => 200],
                        "plain" => ["length" => 200, "precedence" => true]
                    ],
                    "sort" => $this->getDefaultSort(),
                    "limit" => -1
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
     * @throws ReflectionException
     */
    public function testTranslateParametersExceptionAttr()
    {
        $this->expectException(InvalidQueryException::class);

        $translator = new IndexRequestQueryTranslator();
        $reflection = new ReflectionClass($translator);

        $translateParametersReflection = $reflection->getMethod("translateParameters");
        $translateParametersReflection->setAccessible(true);

        $translateParametersReflection->invokeArgs($translator, [
            new ParameterBag(["limit" => 1, "attributes" => "id"])
        ])->toJson();
    }


    /**
     * @throws ReflectionException
     */
    public function testExceptionFilterNotDecodable()
    {
        $this->expectExceptionMessageMatches("/must be JSON decodable/");
        $translator = new IndexRequestQueryTranslator();
        $reflection = new ReflectionClass($translator);

        $translateParametersReflection = $reflection->getMethod("translateParameters");
        $translateParametersReflection->setAccessible(true);

        $translateParametersReflection->invokeArgs($translator, [
            new ParameterBag(["limit" => 1, "filter" => "id"])
        ]);
    }


    protected function getDefaultSort(): array
    {
        return [["property" => "date", "direction" => "DESC"]];
    }


    /**
     * @return mixed
     * @throws ReflectionException
     */
    protected function getDefaultAttributes()
    {
        $translator = new IndexRequestQueryTranslator();
        $reflection = new ReflectionClass($translator);

        $translateParametersReflection = $reflection->getMethod("getDefaultAttributes");
        $translateParametersReflection->setAccessible(true);

        return $translateParametersReflection->invokeArgs($translator, []);
    }
}
