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

namespace Tests\Conjoon\Mail\Client\Data\CompoundKey;

use Conjoon\Mail\Client\Data\CompoundKey\CompoundKey;
use Conjoon\Mail\Client\Data\CompoundKey\FolderKey;
use Conjoon\Mail\Client\Data\CompoundKey\MessageKey;
use Conjoon\Mail\Client\Data\MailAccount;
use Conjoon\Util\Stringable;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Class MessageKeyTest
 * @package Tests\Conjoon\Mail\Client\Data\CompoundKey
 */
class MessageKeyTest extends TestCase
{

    /**
     * Test class
     */
    public function testClass()
    {

        $mailAccountId = "dev";
        $mailFolderId = "INBOX";
        $id = "123";
        $key = new MessageKey($mailAccountId, $mailFolderId, $id);

        $this->assertInstanceOf(CompoundKey::class, $key);
        $this->assertInstanceOf(Stringable::class, $key);
        $this->assertSame($mailAccountId, $key->getMailAccountId());
        $this->assertSame($mailFolderId, $key->getMailFolderId());
        $this->assertSame($id, $key->getId());


        $this->assertEquals([
            "mailAccountId" => $mailAccountId,
            "mailFolderId" => $mailFolderId,
            "id" => $id
        ], $key->toJson());

        $this->assertSame(json_encode($key->toJson()), $key->toString());

        $mailAccount = new MailAccount(["id" => "dev"]);
        $key = new MessageKey($mailAccount, $mailFolderId, $id);
        $this->assertSame($mailAccount->getId(), $key->getMailAccountId());
    }


    /**
     * Test Constructor with FolderKey
     */
    public function testConstructorWithFolderKey()
    {
        $mailAccountId = "dev";
        $mailFolderId = "INBOX";
        $id = "123";

        $obsoleteId = "FOOBAR";

        $folderKey = new FolderKey($mailAccountId, $mailFolderId);
        $key = new MessageKey($folderKey, $id);

        $this->assertSame($mailAccountId, $key->getMailAccountId());
        $this->assertSame($mailFolderId, $key->getMailFolderId());
        $this->assertSame($id, $key->getId());

        $key = new MessageKey($folderKey, $id, $obsoleteId);

        $this->assertSame($mailAccountId, $key->getMailAccountId());
        $this->assertSame($mailFolderId, $key->getMailFolderId());
        $this->assertSame($id, $key->getId());
        $this->assertNotSame($id, $obsoleteId);
    }


    /**
     * Test Constructor with omitted id
     */
    public function testConstructorWithOmittedId()
    {
        $mailAccountId = "dev";
        $mailFolderId = "INBOX";

        $this->expectException(InvalidArgumentException::class);
        new MessageKey($mailAccountId, $mailFolderId);
    }


    /**
     * Test getFolderKey
     */
    public function testGetFolderKey()
    {
        $mailAccountId = "dev";
        $mailFolderId = "INBOX";
        $id = "123";

        $folderKey = new FolderKey($mailAccountId, $mailFolderId);
        $key = new MessageKey($folderKey, $id);

        $this->assertEquals($folderKey, $key->getFolderKey());
        $this->assertEquals($key->getFolderKey(), $key->getFolderKey());
        $this->assertNotSame($key->getFolderKey(), $key->getFolderKey());
    }
}
