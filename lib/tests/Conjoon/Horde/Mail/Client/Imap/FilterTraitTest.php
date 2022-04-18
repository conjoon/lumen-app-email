<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2021-2022 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

namespace Tests\Conjoon\Horde\Mail\Client\Imap;

use Conjoon\Horde\Mail\Client\Imap\FilterTrait;
use Tests\TestCase;

/**
 * Class FilterTraitTest
 * @package Tests\Conjoon\Horde\Mail\Client\Imap
 */
class FilterTraitTest extends TestCase
{

    /**
     * Tests getSearchQueryFromFilter
     */
    public function testGetSearchQueryFromFilter()
    {
        $translator = $this->getMockedTrait();

        $tests = [
            [
                "input" => [],
                "output" => "ALL"
            ], [
                "input" => [
                    ["property" => "recent", "value" => true, "operator" => "="],
                    ["property" => "id", "value" => 1000, "operator" => ">="]
                ],
                "output" => "OR (UID 1000:*) (RECENT)"
            ], [
                "input" => [
                    ["property" => "recent", "value" => true, "operator" => "="]
                ],
                "output" => "RECENT"
            ], [
                "input" => [
                    ["property" => "id", "value" => 1000, "operator" => ">="]
                ],
                "output" => "UID 1000:*"
            ], [
                "input" => [
                    ["property" => "recent", "value" => true, "operator" => "="],
                    ["property" => "id", "value" => [1000, 1001], "operator" => "IN"]
                ],
                "output" => "OR (UID 1000:1001) (RECENT)"
            ]
        ];


        foreach ($tests as $test) {
            $this->assertSame(
                $test["output"],
                $translator->getSearchQueryFromFilter($test["input"])->__toString()
            );
        }
    }


    /**
     * @return __anonymous@1559
     */
    public function getMockedTrait()
    {
        return new class (){
            use FilterTrait;
        };
    }
}
