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

namespace Tests\Conjoon\Core;

use Conjoon\Core\ParameterBag;
use BadMethodCallException;
use Conjoon\Core\ResourceQuery;
use Conjoon\Util\Jsonable;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

/**
 * Class ResourceQueryTest
 * @package Tests\Conjoon\Core
 */
class ResourceQueryTest extends TestCase
{

    /**
     * Class functionality
     * @noinspection PhpUndefinedFieldInspection
     * @noinspection PhpUndefinedMethodInspection
     */
    public function testDelegates()
    {
        $bag = $this
                ->getMockBuilder(ParameterBag::class)
                ->setConstructorArgs([[
                    "foo" => "bar",
                    "bar" => "snafu"
                ]])
                ->getMock();

        $bag->expects($this->exactly(2))
            ->method("has")
            ->withConsecutive(["bar"], ["foo"])
            ->willReturnOnConsecutiveCalls(true, true);

        $bag->expects($this->exactly(1))
            ->method("toJson")
            ->willReturn([]);

        $bag->expects($this->exactly(3))
            ->method("__call")
            ->withConsecutive(
                ["getInt", ["bar"]],
                ["getString", ["foo"]],
                ["getBool", ["some"]]
            )->willReturnOnConsecutiveCalls(1, 2, null);

        $bag->expects($this->exactly(3))
            ->method("__get")
            ->withConsecutive(
                ["bar"],
                ["foo"],
                ["some" ]
            )->willReturnOnConsecutiveCalls(1, 2, null);


        $resourceQuery = $this->getResourceQuery($bag);

        $this->assertInstanceOf(Jsonable::class, $resourceQuery);

        $this->assertTrue($resourceQuery->has("bar"));
        $this->assertTrue($resourceQuery->has("foo"));
        $this->assertSame([], $resourceQuery->toJson());

        $this->assertSame(1, $resourceQuery->getInt("bar"));
        $this->assertSame(2, $resourceQuery->getString("foo"));
        $this->assertNull($resourceQuery->getBool("some"));

        $this->assertSame(1, $resourceQuery->bar);
        $this->assertSame(2, $resourceQuery->foo);
        $this->assertNull($resourceQuery->some);
    }


    /**
     * No method available
     * @noinspection PhpUndefinedMethodInspection
     */
    public function testDelegateCallException()
    {
        $this->expectException(BadMethodCallException::class);
        $this->getResourceQuery(new ParameterBag())->getSomeThing("d");
    }


    /**
     * @param ParameterBag $bag
     * @return ResourceQuery|MockObject
     */
    protected function getResourceQuery(ParameterBag $bag)
    {
        return $this->getMockForAbstractClass(
            ResourceQuery::class,
            [$bag]
        );
    }
}
