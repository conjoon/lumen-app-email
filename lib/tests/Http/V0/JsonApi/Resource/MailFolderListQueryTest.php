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

use App\Http\V0\JsonApi\Resource\MailFolderListQuery;
use App\Http\V0\JsonApi\Resource\MailFolder;
use Conjoon\Data\ParameterBag;
use Conjoon\MailClient\Data\Resource\MailFolderListQuery as BaseMailFolderListQuery;
use Tests\TestCase;

/**
 * Tests MailFolderListQuery.
 */
class MailFolderListQueryTest extends TestCase
{
    /**
     * test class
     */
    public function testClass()
    {
        $inst = new MailFolderListQuery(new ParameterBag([]));
        $this->assertInstanceOf(BaseMailFolderListQuery::class, $inst);
    }


    /**
     * Tests getResourceTarget()
     * @return void
     */
    public function testGetResourceTarget(): void
    {
        $parameterBag = new ParameterBag();
        $query = new MailFolderListQuery($parameterBag);
        $this->assertSame(MailFolder::class, get_class($query->getResourceTarget()));
    }


    /**
     * Tests getFields()
     * @return void
     */
    public function testGetFields(): void
    {
        // fields[MailFolder]=name,data,folderType
        $parameterBag = new ParameterBag();
        $resourceTarget = $this->createMockForAbstract(MailFolder::class, ["getDefaultFields"]);
        $resourceTarget->expects($this->once())->method("getDefaultFields")->willReturn(
            ["name", "data", "folderType"]
        );
        $query = $this->createMockForAbstract(
            MailFolderListQuery::class,
            ["getResourceTarget"],
            [$parameterBag]
        );
        $query->expects($this->once())->method("getResourceTarget")->willReturn($resourceTarget);
        $this->assertSame(["name", "data", "folderType"], $query->getFields());

        // fields[MailFolder]=name,data,folderType
        $parameterBag = new ParameterBag(["fields[MailFolder]" => "name,data,folderType"]);
        $query = new MailFolderListQuery($parameterBag);
        $this->assertEqualsCanonicalizing(["name", "data", "folderType"], $query->getFields());

        // relfield:fields[MailFolder]=+unreadMessages,-data,-folderType
        $parameterBag = new ParameterBag(["relfield:fields[MailFolder]" => "+unreadMessages,-data,-folderType"]);
        $resourceTarget = $this->createMockForAbstract(MailFolder::class, ["getDefaultFields"]);
        $resourceTarget->expects($this->once())->method("getDefaultFields")->willReturn(
            ["name", "data", "folderType"]
        );
        $query = $this->createMockForAbstract(
            MailFolderListQuery::class,
            ["getResourceTarget"],
            [$parameterBag]
        );
        $query->expects($this->once())->method("getResourceTarget")->willReturn($resourceTarget);
        $this->assertEqualsCanonicalizing(["name", "unreadMessages"], $query->getFields());
    }
}
