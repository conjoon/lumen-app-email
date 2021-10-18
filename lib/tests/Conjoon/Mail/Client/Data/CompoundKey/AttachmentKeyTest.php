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

use Conjoon\Mail\Client\Data\CompoundKey\AttachmentKey;
use Conjoon\Mail\Client\Data\CompoundKey\MessageItemChildCompoundKey;
use Tests\TestCase;

/**
 * Class AttachmentKeyTest
 * @package Tests\Conjoon\Mail\Client\Data\CompoundKey
 */
class AttachmentKeyTest extends TestCase
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
        $key = $this->createAttachmentKey($mailAccountId, $mailFolderId, $parentMessageItemId, $id);

        $this->assertInstanceOf(MessageItemChildCompoundKey::class, $key);
    }


// ---------------------
//    Helper Functions
// ---------------------

    /**
     * @param $mailAccountId
     * @param $mailFolderId
     * @param null $parentMessageItemId
     * @param null $id
     * @return AttachmentKey
     */
    protected function createAttachmentKey(
        $mailAccountId,
        $mailFolderId,
        $parentMessageItemId = null,
        $id = null
    ): AttachmentKey {

        // Create a new instance from the Abstract Class
        return new AttachmentKey($mailAccountId, $mailFolderId, $parentMessageItemId, $id);
    }
}
