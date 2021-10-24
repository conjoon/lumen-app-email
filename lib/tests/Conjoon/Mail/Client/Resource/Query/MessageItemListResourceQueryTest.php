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

namespace Tests\Conjoon\Mail\Client\Resource\Query;

use Conjoon\Core\ParameterBag;
use Conjoon\Http\Query\ResourceQuery;
use Conjoon\Mail\Client\Resource\Query\MessageItemListResourceQuery;
use Tests\TestCase;

/**
 * Class MessageItemListResourceQueryTest
 * @package Tests\Conjoon\Mail\Client\Resource\Query
 */
class MessageItemListResourceQueryTest extends TestCase
{


    /**
     * Test type
     */
    public function testClass()
    {

        $resourceQuery = new MessageItemListResourceQuery(new ParameterBag());
        $this->assertInstanceOf(ResourceQuery::class, $resourceQuery);
    }
}
