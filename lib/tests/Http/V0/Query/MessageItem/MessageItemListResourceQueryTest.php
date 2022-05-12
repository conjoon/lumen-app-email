<?php

/**
 * conjoon
 * lumen-app-email
 * Copyright (c) 2019-2022 Thorsten Suckow-Homberg https://github.com/conjoon/lumen-app-email
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

use Conjoon\Core\ParameterBag;
use App\Http\V0\Query\MessageItem\MessageItemListResourceQuery;
use Conjoon\Mail\Client\Query\MessageItemListResourceQuery as BaseMessageItemListResourceQuery;
use Tests\TestCase;

/**
 * Class MessageItemListResourceQueryTest
 * @package Tests\App\Http\V0\Query\MessageItem
 */
class MessageItemListResourceQueryTest extends TestCase
{
    /**
     * test class
     */
    public function testClass()
    {
        $inst = new MessageItemListResourceQuery(new ParameterBag([]));
        $this->assertInstanceOf(BaseMessageItemListResourceQuery::class, $inst);
    }
}
