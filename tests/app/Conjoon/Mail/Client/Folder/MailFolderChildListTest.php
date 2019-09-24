<?php
/**
 * conjoon
 * php-cn_imapuser
 * Copyright (C) 2019 Thorsten Suckow-Homberg https://github.com/conjoon/php-cn_imapuser
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

use Conjoon\Util\AbstractList,
    Conjoon\Util\Jsonable,
    Conjoon\Mail\Client\Folder\MailFolder,
    Conjoon\Mail\Client\Folder\MailFolderChildList,
    Conjoon\Mail\Client\Data\CompoundKey\FolderKey;


class MailFolderChildListTest extends TestCase
{


// ---------------------
//    Tests
// ---------------------

    /**
     * Tests constructor
     */
    public function testClass() {

        $mailFolderChildList = new MailFolderChildList();
        $this->assertInstanceOf(AbstractList::class, $mailFolderChildList);
        $this->assertInstanceOf(Jsonable::class, $mailFolderChildList);

        $this->assertSame(MailFolder::class, $mailFolderChildList->getEntityType());
    }


    /**
     * Tests constructor
     */
    public function testToJson() {

        $data = [
            "name"        => "INBOX",
            "unreadCount" => 5,
            "folderType"  => MailFolder::TYPE_INBOX
        ];

        $folder = new MailFolder(
            new FolderKey("dev", "INBOX"),
            $data
        );

        $mailFolderChildList = new MailFolderChildList();

        $mailFolderChildList[] = $folder;

        $this->assertEquals([[
            "mailAccountId" => "dev",
            "id"            => "INBOX",
            "folderType"  => MailFolder::TYPE_INBOX,
            "unreadCount"   => 5,
            "name"          => "INBOX",
            "data"          => []
        ]], $mailFolderChildList->toJson());

    }

}