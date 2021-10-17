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
use Conjoon\Mail\Client\Folder\MailFolder;
use Conjoon\Mail\Client\Folder\MailFolderChildList;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Class MailFolderTest
 * @package Tests\Conjoon\Mail\Client\Folder
 */
class MailFolderTest extends TestCase
{


// ---------------------
//    Tests
// ---------------------

    /**
     * Tests constructor
     */
    public function testConstructor()
    {

        $folderKey = $this->createKey();

        $this->assertTrue(MailFolder::TYPE_INBOX != "");
        $this->assertTrue(MailFolder::TYPE_DRAFT != "");
        $this->assertTrue(MailFolder::TYPE_JUNK != "");
        $this->assertTrue(MailFolder::TYPE_TRASH != "");
        $this->assertTrue(MailFolder::TYPE_SENT != "");
        $this->assertTrue(MailFolder::TYPE_FOLDER != "");

        $mailFolder = new MailFolder(
            $folderKey,
            [
            "name" => "Folder",
            "unreadCount" => 0,
            "folderType" => MailFolder::TYPE_INBOX
            ]
        );

        $this->assertInstanceOf(AbstractMailFolder::class, $mailFolder);
        $this->assertSame(MailFolder::TYPE_INBOX, $mailFolder->getFolderType());


        $children = $mailFolder->getData();
        $this->assertInstanceOf(MailFolderChildList::class, $mailFolder->getData());
        $this->assertSame(0, count($children));


        $mf = new MailFolder($this->createKey(), [
            "name" => "Folder2",
            "unreadCount" => 32,
            "folderType" => MailFolder::TYPE_INBOX
        ]);
        $mailFolder->addMailFolder($mf);

        $this->assertSame(1, count($children));
        $this->assertSame($mf, $children[0]);
    }


    /**
     * Tests constructor with exception for missing folderType
     */
    public function testConstructor_exceptionFolderType()
    {

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("folderType");

        $folderKey = $this->createKey();
        new MailFolder(
            $folderKey,
            [
            ]
        );
    }


    /**
     * Test for exception if invalid folder type gets submitted to setFolderType
     */
    public function testSetFolderType_exception()
    {
        $this->expectException(InvalidArgumentException::class);

        new MailFolder($this->createKey(), ["folderType" => "foo"]);
    }


    /**
     * Test for toJson
     */
    public function testToJson()
    {

        $data = [
            "folderType" => MailFolder::TYPE_INBOX,
            "name" => "INBOX",
            "unreadCount" => 5
        ];

        $key = $this->createKey("dev", "INBOX");

        $mf = new MailFolder($key, $data);

        $mf->addMailFolder(
            new MailFolder(
                $this->createKey("dev", "INBOX.SubFolder"),
                [
                    "name" => "A",
                    "unreadCount" => 4,
                    "folderType" => MailFolder::TYPE_INBOX
                ]
            )
        );

        $json = $mf->toJson();

        $expected = array_merge($key->toJson(), $data);
        $expected["data"] = [[
            "mailAccountId" => "dev",
            "id" => "INBOX.SubFolder",
            "name" => "A",
            "unreadCount" => 4,
            "folderType" => MailFolder::TYPE_INBOX,
            "data" => []
        ]];


        $this->assertEquals($expected, $json);
    }



// ---------------------
//    Helper
// ---------------------


    /**
     * @return FolderKey
     */
    public function createKey($account = "dev", $name = "INBOX.Some Folder")
    {

        return new FolderKey($account, $name);
    }
}
