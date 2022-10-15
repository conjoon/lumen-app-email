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

namespace Tests\App\Http\V0\JsonApi\Resource;

use Conjoon\Core\Data\ParameterBag;
use App\Http\V0\JsonApi\Resource\MessageItemListQuery;
use Conjoon\Core\Data\SortInfoList;
use App\Http\V0\JsonApi\Resource\MessageItem;
use Conjoon\MailClient\Data\Resource\MessageItemListQuery as BaseMessageItemListQuery;
use Tests\TestCase;

/**
 * Tests MessageItemListResourceQuery.
 */
class MessageItemListQueryTest extends TestCase
{
    /**
     * test class
     */
    public function testClass()
    {
        $inst = new MessageItemListQuery(new ParameterBag([]));
        $this->assertInstanceOf(BaseMessageItemListQuery::class, $inst);
    }


    /**
     * Tests getResourceTarget()
     * @return void
     */
    public function testGetResourceTarget()
    {
        $parameterBag = new ParameterBag();
        $query = new MessageItemListQuery($parameterBag);
        $this->assertSame(MessageItem::class, get_class($query->getResourceTarget()));
    }


    /**
     * Tests getStart()
     * @return void
     */
    public function testGetStart(): void
    {
        // 0
        $parameterBag = new ParameterBag();
        $query = new MessageItemListQuery($parameterBag);
        $this->assertSame(0, $query->getStart());

        // 500
        $parameterBag = new ParameterBag([
            "page[start]" => "500"
        ]);
        $query = new MessageItemListQuery($parameterBag);
        $this->assertSame(500, $query->getStart());
    }


    /**
     * Tests getLimit()
     * @return void
     */
    public function testGetLimit(): void
    {

        // null
        $parameterBag = new ParameterBag();
        $query = new MessageItemListQuery($parameterBag);
        $this->assertNull($query->getLimit());

        // int
        $parameterBag = new ParameterBag(["page[limit]" => 50]);
        $query = new MessageItemListQuery($parameterBag);
        $this->assertSame(50, $query->getLimit());
    }


    /**
     * Tests getFields()
     * @return void
     */
    public function testGetFields(): void
    {
        // fields[MessageItem]=subject,date,size
        $parameterBag = new ParameterBag();
        $resourceTarget = $this->createMockForAbstract(MessageItem::class, ["getDefaultFields"]);
        $resourceTarget->expects($this->once())->method("getDefaultFields")->willReturn(
            ["date", "size", "hasAttachments"]
        );
        $query = $this->createMockForAbstract(
            MessageItemListQuery::class,
            ["getResourceTarget"],
            [$parameterBag]
        );
        $query->expects($this->once())->method("getResourceTarget")->willReturn($resourceTarget);
        $this->assertSame(["date", "size", "hasAttachments"], $query->getFields());

        // fields[MessageItem]=subject,date,size
        $parameterBag = new ParameterBag(["fields[MessageItem]" => "subject,date,size"]);
        $query = new MessageItemListQuery($parameterBag);
        $this->assertEqualsCanonicalizing(["subject", "date", "size"], $query->getFields());

        // relfield:fields[MessageItem]=+subject,-date,-size
        $parameterBag = new ParameterBag(["relfield:fields[MessageItem]" => "+subject,-date,-size"]);
        $resourceTarget = $this->createMockForAbstract(MessageItem::class, ["getDefaultFields"]);
        $resourceTarget->expects($this->once())->method("getDefaultFields")->willReturn(
            ["date", "size", "hasAttachments"]
        );
        $query = $this->createMockForAbstract(
            MessageItemListQuery::class,
            ["getResourceTarget"],
            [$parameterBag]
        );
        $query->expects($this->once())->method("getResourceTarget")->willReturn($resourceTarget);
        $this->assertEqualsCanonicalizing(["subject", "hasAttachments"], $query->getFields());
    }


    /**
     * Tests getSort()
     * @return void
     */
    public function testGetSort(): void
    {
        // empty sort
        $parameterBag = new ParameterBag();
        $query = new MessageItemListQuery($parameterBag);
        $sortInfoList = $query->getSort();
        $this->assertInstanceOf(SortInfoList::class, $sortInfoList);
        $this->assertSame(0, $sortInfoList->count());

        // empty sort
        $parameterBag = new ParameterBag(["sort" => "subject,-date"]);
        $query = new MessageItemListQuery($parameterBag);
        $sortInfoList = $query->getSort();
        $this->assertEquals([
            ["field" => "subject", "direction" => "ascending"],
            ["field" => "date", "direction" => "descending"],
        ], $sortInfoList->toArray());
    }
}
