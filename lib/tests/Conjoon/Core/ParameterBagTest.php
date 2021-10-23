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
use Conjoon\Util\Jsonable;
use BadMethodCallException;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Class ParameterBagTest
 * @package Tests\Conjoon\Core
 */
class ParameterBagTest extends TestCase
{

    /**
     * Class functionality
     * @noinspection PhpUndefinedFieldInspection
     */
    public function testParameterBag()
    {
        $bag = new ParameterBag();
        $this->assertInstanceOf(Jsonable::class, $bag);

        $bag->foo = 1;

        $this->assertSame(1, $bag->foo);
        $this->assertSame("1", $bag->getString("foo"));
        $this->assertSame(true, $bag->getBool("foo"));
        $bag->foo = "1";
        $this->assertSame("1", $bag->foo);
        $this->assertSame(1, $bag->getInt("foo"));

        $bag->bar = "snafu";
        $this->assertTrue($bag->has("bar"));
        $this->assertEquals(["foo", "bar"], $bag->keys());

        $this->assertNull($bag->notThere);
        $this->assertNull($bag->getInt("notThere"));
        $this->assertNull($bag->getString("notThere"));
        $this->assertNull($bag->getBool("notThere"));

        $this->assertEquals(["bar" => "snafu", "foo" => "1"], $bag->toJson());

        $bag = new ParameterBag([
            "foo" => "bar",
            "bar" => "snafu"
        ], [
            "someKey" => "1",
            "foo" => "wontOverwrite"
        ]);

        $this->assertEquals([
            "foo" => "bar",
            "bar" => "snafu",
            "someKey" => "1"
        ], $bag->toJson());
    }

    /**
     * No method available
     * @noinspection PhpUndefinedMethodInspection
     */
    public function testParameterBagCallException()
    {
        $this->expectException(BadMethodCallException::class);
        (new ParameterBag())->getSomeThing("d");
    }

    /**
     * No method available
     */
    public function testParameterBagCallExceptionNoArgs()
    {
        $this->expectException(BadMethodCallException::class);
        (new ParameterBag())->getInt();
    }


    /**
     * No method available
     * @noinspection PhpUndefinedMethodInspection
     */
    public function testParameterBagCallExceptionNoArgsNoMethod()
    {
        $this->expectException(BadMethodCallException::class);
        (new ParameterBag())->getTint("d");
    }



    /**
     * No numeric array allowed.
     */
    public function testParameterBagConstructorException()
    {
        $this->expectException(InvalidArgumentException::class);
        new ParameterBag(["foO"]);
    }
}
