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

namespace Tests\Conjoon\Mail\Client\Imap\Util;

use Conjoon\Mail\Client\Data\CompoundKey\FolderKey;
use Conjoon\Mail\Client\Folder\FolderIdToTypeMapper;
use Conjoon\Mail\Client\Folder\ListMailFolder;
use Conjoon\Mail\Client\Folder\MailFolder;
use Conjoon\Mail\Client\Imap\Util\DefaultFolderIdToTypeMapper;
use Tests\TestCase;

/**
 * Class DefaultFolderIdToTypeMapperTest
 */
class DefaultFolderIdToTypeMapperTest extends TestCase
{

    /**
     * Tests constructor and base class.
     */
    public function testInstance()
    {

        $mapper = $this->createMapper();
        $this->assertInstanceOf(FolderIdToTypeMapper::class, $mapper);
    }


    /**
     * Tests getFolderType
     */
    public function testGetFolderType()
    {

        $mapper = $this->createMapper();

        $this->assertSame(MailFolder::TYPE_DRAFT, $mapper->getFolderType(
            $this->createListMailFolder("Entwürfe", "/")
        ));

        $this->assertSame(
            $mapper->getFolderType(
                $this->createListMailFolder("SomeRandomFolder/Draft", "/")
            ),
            MailFolder::TYPE_FOLDER
        );
        $this->assertSame(
            $mapper->getFolderType(
                $this->createListMailFolder("SomeRandomFolder/Draft/Test", "/")
            ),
            MailFolder::TYPE_FOLDER
        );
        $this->assertSame(
            $mapper->getFolderType(
                $this->createListMailFolder("SomeRandom", ".")
            ),
            MailFolder::TYPE_FOLDER
        );
        $this->assertSame(
            $mapper->getFolderType(
                $this->createListMailFolder("INBOX", ".")
            ),
            MailFolder::TYPE_INBOX
        );
        $this->assertSame(
            $mapper->getFolderType(
                $this->createListMailFolder("INBOX/Somefolder/Deep/Drafts", "/")
            ),
            MailFolder::TYPE_FOLDER
        );
        $this->assertSame(
            $mapper->getFolderType(
                $this->createListMailFolder("INBOX.Drafts", ".")
            ),
            MailFolder::TYPE_DRAFT
        );
        $this->assertSame(
            $mapper->getFolderType(
                $this->createListMailFolder("INBOX.Trash.Deep.Deeper.Folder", ".")
            ),
            MailFolder::TYPE_FOLDER
        );

        $this->assertSame(
            $mapper->getFolderType(
                $this->createListMailFolder("Junk/Draft", "/")
            ),
            MailFolder::TYPE_FOLDER
        );
        $this->assertSame(
            $mapper->getFolderType(
                $this->createListMailFolder("TRASH.Draft.folder", ".")
            ),
            MailFolder::TYPE_FOLDER
        );
        $this->assertSame(
            $mapper->getFolderType(
                $this->createListMailFolder("TRASH", "/")
            ),
            MailFolder::TYPE_TRASH
        );

        $this->assertSame(
            $mapper->getFolderType(
                $this->createListMailFolder("INBOX:TRASH", ":")
            ),
            MailFolder::TYPE_TRASH
        );
        $this->assertSame(
            $mapper->getFolderType(
                $this->createListMailFolder("INBOX-DRAFTS", "-")
            ),
            MailFolder::TYPE_DRAFT
        );
    }


// -------------------------------
//  Helper
// -------------------------------

    /**
     * @param string $id
     * @param string $delimiter
     * @return ListMailFolder
     */
    public function createListMailFolder($id, $delimiter): ListMailFolder
    {

        $parts = explode($id, $delimiter);

        return new ListMailFolder(
            new FolderKey("dev", $id),
            ["name"        => array_pop($parts),
                "delimiter"   => $delimiter,
                "unreadCount" => 0]
        );
    }


    /**
     * @return DefaultFolderIdToTypeMapper
     */
    protected function createMapper(): DefaultFolderIdToTypeMapper
    {
        return new DefaultFolderIdToTypeMapper();
    }


    /**
     * @param $mid
     * @param $id
     * @return FolderKey
     */
    protected function createFolderKey($mid, $id): FolderKey
    {
        return new FolderKey($mid, $id);
    }
}
