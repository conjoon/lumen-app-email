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

namespace Tests\Conjoon\Mail\Client\Folder;

use Conjoon\Mail\Client\Data\CompoundKey\FolderKey;
use Conjoon\Mail\Client\Folder\AbstractMailFolder;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Class AbstractMailFolderTest
 * @package Tests\Conjoon\Mail\Client\Folder
 */
class AbstractMailFolderTest extends TestCase
{


// ---------------------
//    Tests
// ---------------------

    /**
     * Tests constructor
     */
    public function testConstructor()
    {

        $name = "INBOX.Some Folder";
        $unreadCount = 23;

        $folderKey = new FolderKey("dev", $name);
        $abstractMailFolder = $this->createMailFolder(
            $folderKey,
            [
            "unreadCount" => $unreadCount,
            "name" => $name
            ]
        );

        $this->assertInstanceOf(AbstractMailFolder::class, $abstractMailFolder);

        $this->assertSame($folderKey, $abstractMailFolder->getFolderKey());
        $this->assertSame($name, $abstractMailFolder->getName());
        $this->assertSame($unreadCount, $abstractMailFolder->getUnreadCount());
    }


    /**
     * Tests constructor with exception for missing unreadCount
     */
    public function testConstructorExceptionUnreadCount()
    {

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("unreadCount");

        $folderKey = new FolderKey("dev", "TEST");
        $this->createMailFolder(
            $folderKey,
            [
            "name" => "TEST"
            ]
        );
    }


    /**
     * Tests constructor with exception for missing name
     */
    public function testConstructorExceptionName()
    {

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("name");


        $folderKey = new FolderKey("dev", "TEST");
        $this->createMailFolder(
            $folderKey,
            [
            "unreadCount" => 0
            ]
        );
    }


// ---------------------
//    Helper Functions
// ---------------------


    /**
     * Returns an anonymous class extending AbstractMailFolder.
     * @param FodlerKey $key
     * @param array|null $data
     * @return AbstractMailFolder
     */
    protected function createMailFolder(FolderKey $key, array $data = null): AbstractMailFolder
    {
        // Create a new instance from the Abstract Class
        return new class ($key, $data) extends AbstractMailFolder {

        };
    }
}
