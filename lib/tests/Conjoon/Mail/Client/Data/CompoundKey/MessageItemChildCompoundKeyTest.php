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

use Conjoon\Mail\Client\Data\CompoundKey\MessageItemChildCompoundKey;
use Conjoon\Mail\Client\Data\CompoundKey\MessageKey;
use Tests\TestCase;
use InvalidArgumentException;

/**
 * Class MessageItemChildCompoundKeyTest
 * @package Tests\Conjoon\Mail\Client\Data\CompoundKey
 */
class MessageItemChildCompoundKeyTest extends TestCase
{


// ---------------------
//    Tests
// ---------------------
    /**
     * Test class
     */
    public function testClass()
    {

        $mailAccountId = "dev";
        $mailFolderId = "INBOX";
        $parentMessageItemId = "89";
        $id = "123";
        $key = $this->createMessageItemChildCompoundKey($mailAccountId, $mailFolderId, $parentMessageItemId, $id);

        $this->assertInstanceOf(MessageKey::class, $key);
        $this->assertSame($mailAccountId, $key->getMailAccountId());
        $this->assertSame($mailFolderId, $key->getMailFolderId());
        $this->assertSame($parentMessageItemId, $key->getParentMessageItemId());
        $this->assertSame($id, $key->getId());

        $this->assertEquals([
            "mailAccountId" => $mailAccountId,
            "mailFolderId" => $mailFolderId,
            "parentMessageItemId" => $parentMessageItemId,
            "id" => $id
        ], $key->toJson());


        // w/ MessageKey
        $mailAccountId = "dev1";
        $mailFolderId = "INBOX2";
        $parentMessageItemId = "893";
        $id = "1234";
        $key = $this->createMessageItemChildCompoundKey(new MessageKey($mailAccountId, $mailFolderId, $parentMessageItemId), $id);

        $this->assertInstanceOf(MessageKey::class, $key);
        $this->assertSame($mailAccountId, $key->getMailAccountId());
        $this->assertSame($mailFolderId, $key->getMailFolderId());
        $this->assertSame($parentMessageItemId, $key->getParentMessageItemId());
        $this->assertSame($id, $key->getId());

        $this->assertEquals([
            "mailAccountId" => $mailAccountId,
            "mailFolderId" => $mailFolderId,
            "parentMessageItemId" => $parentMessageItemId,
            "id" => $id
        ], $key->toJson());
    }


    /**
     * Test Constructor with omitted id
     */
    public function testConstructorWithOmittedId()
    {

        $this->expectException(InvalidArgumentException::class);
        $this->createMessageItemChildCompoundKey("dev", "INBOX", "1");
    }


    /**
     * Test Constructor with omitted parentMessageItemId
     */
    public function testConstructorWithOmittedParentMessageItemId()
    {

        $this->expectException(InvalidArgumentException::class);
        $this->createMessageItemChildCompoundKey("dev", "INBOX", null, "2");
    }

// ---------------------
//    Helper Functions
// ---------------------


    /**
     * @param $mailAccountId
     * @param $mailFolderId
     * @param null $parentMessageItemId
     * @param null $id
     * @return MessageItemChildCompoundKey
     */
    protected function createMessageItemChildCompoundKey(
        $mailAccountId,
        $mailFolderId,
        $parentMessageItemId = null,
        $id = null
    ): MessageItemChildCompoundKey {

        // Create a new instance from the Abstract Class
        return new class (
            $mailAccountId,
            $mailFolderId,
            $parentMessageItemId,
            $id
        ) extends MessageItemChildCompoundKey {

        };
    }
}
