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
use Conjoon\Mail\Client\Folder\ListMailFolder;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Class ListMailFolderTest
 * @package Tests\Conjoon\Mail\Client\Folder
 */
class ListMailFolderTest extends TestCase
{


// ---------------------
//    Tests
// ---------------------

    /**
     * Tests constructor
     */
    public function testConstructor()
    {

        $delimiter = ".";
        $name = "INBOX.Some Folder";

        $folderKey = new FolderKey("dev", $name);
        $listMailFolder = new ListMailFolder(
            $folderKey,
            [
            "delimiter" => $delimiter,
            "name" => $name,
            "unreadCount" => 0
            ]
        );

        $this->assertInstanceOf(AbstractMailFolder::class, $listMailFolder);

        $this->assertSame($delimiter, $listMailFolder->getDelimiter());
        $this->assertSame([], $listMailFolder->getAttributes());

        $attributes = ["\nonexistent", "\noselect"];
        $listMailFolder = new ListMailFolder(
            $folderKey,
            [
            "delimiter" => $delimiter,
            "name" => $name,
            "unreadCount" => 0,
            "attributes" => $attributes
            ]
        );

        $this->assertInstanceOf(AbstractMailFolder::class, $listMailFolder);

        $this->assertSame($delimiter, $listMailFolder->getDelimiter());
        $this->assertSame($attributes, $listMailFolder->getAttributes());
    }


    /**
     * Tests constructor with exception for missing delimiter
     */
    public function testConstructorExceptionDelimiter()
    {

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("delimiter");

        $name = "INBOX.Some Folder";

        $folderKey = new FolderKey("dev", $name);
        new ListMailFolder(
            $folderKey,
            [
            "name" => $name,
            "unreadCount" => 0
            ]
        );
    }
}
