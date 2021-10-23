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

namespace Tests\Conjoon\Util;

use Conjoon\Util\ArrayUtil;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Class ArrayUtilTest
 * @package Tests\Conjoon\Util
 */
class ArrayUtilTest extends TestCase
{


// ---------------------
//    Tests
// ---------------------

    /**
     * Tests intersect()
     */
    public function testIntersect()
    {

        $data = [
            1, 2, 3, 4
        ];

        $keys = [1, 3];

        $this->assertEquals([1 => 2, 3 => 4], ArrayUtil::intersect($data, $keys));

        $data = [
            "foo" => "bar", "bar" => "snafu", 3 => 4
        ];

        $keys = ["foo", "bar"];

        $this->assertEquals(["foo" => "bar", "bar" => "snafu"], ArrayUtil::intersect($data, $keys));


        $data = [
            "foo" => "bar", "bar" => "snafu", 3 => 4
        ];

        $keys = ["foo", "bar"];

        $this->assertEquals(["foo" => "bar", "bar" => "snafu"], ArrayUtil::intersect($data, $keys));
    }


    /**
     * Tests mergeIf()
     */
    public function testMergeIfExceptionTarget()
    {
        $this->expectException(InvalidArgumentException::class);
        $target = ["foo" => "bar", 1 => "snafu", "3i" => 4];
        $data   = ["foo" => "snafu", "foobar" => "foo", "3i" => "6"];
        ArrayUtil::mergeIf($target, $data);
    }


    /**
     * Tests mergeIf()
     */
    public function testMergeIfExceptionData()
    {
        $this->expectException(InvalidArgumentException::class);
        $target = ["foo" => "bar", "ds" => "snafu", "3i" => 4];
        $data   = ["foo" => "snafu", "foobar" => "foo", "3" => "6"];
        ArrayUtil::mergeIf($target, $data);
    }


    /**
     * Tests mergeIf()
     */
    public function testMergeIf()
    {

        $target = [
            "foo" => "bar", "bar" => "snafu", "3i" => 4
        ];

        $data = [
            "foo" => "snafu", "foobar" => "foo", "3i" => "6"
        ];

        $this->assertEquals(
            ["foo" => "bar", "bar" => "snafu", "3i" => 4, "foobar" => "foo"],
            ArrayUtil::mergeIf($target, $data)
        );
    }
}
