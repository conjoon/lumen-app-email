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

use App\Imap\Service\DefaultMailFolderService,
    App\Imap\Service\MailFolderServiceException;


class DefaultMailFolderServiceTest extends TestCase {

    use TestTrait;


    public function testInstance() {

        $service = new DefaultMailFolderService();
        $this->assertInstanceOf(\App\Imap\Service\MailFolderService::class, $service);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMailFolders_exception() {

        $this->expectException(MailFolderServiceException::class);

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive('listMailboxes')
                 ->andThrow(new \Exception("This exception should be caught properly by the test"));

        $service = new DefaultMailFolderService();
        $service->getMailFoldersFor($this->getTestUserStub()->getImapAccount());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMailFoldersFor() {

        $account = $this->getTestUserStub()->getImapAccount();

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $mbox1 = new \Horde_Imap_Client_Mailbox("INBOX", true);
        $mbox2 = new \Horde_Imap_Client_Mailbox("INBOX.Sent", true);
        $mbox3 = new \Horde_Imap_Client_Mailbox("INBOX.Drafts", true);

        $imapReturn = [
            "INBOX" => [
                "delimiter" => ".",
                "mailbox"   => $mbox1
            ],
            "INBOX.Sent" => [
                "delimiter" => ".",
                "mailbox"   => $mbox2
            ],
            "INBOX.Drafts" => [
                "delimiter" => ".",
                "mailbox"   => $mbox3
            ]
        ];


        $imapStub->shouldReceive('listMailboxes')
            ->once()
            ->with('*', \Horde_Imap_Client::MBOX_ALL)
            ->andReturn($imapReturn);


        $imapStub->shouldReceive('status')->with($mbox1, 16)->andReturn(["unseen" => 0]);
        $imapStub->shouldReceive('status')->with($mbox2, 16)->andReturn(["unseen" => 3]);
        $imapStub->shouldReceive('status')->with($mbox3, 16)->andReturn(["unseen" => 4]);


        $service = new DefaultMailFolderService();


        $this->assertSame($service->getMailFoldersFor($account), [[
            "id" => "INBOX",
            "mailAccountId" => $account->getId(),
            "name" => "INBOX",
            "unreadCount" => 0,
            "cn_folderType" => "INBOX",
            "data" => [[
                "id" => "INBOX.Sent",
                "mailAccountId" => $account->getId(),
                "name" => "Sent",
                "unreadCount" => 3,
                "cn_folderType" => "SENT",
                "data" => [],
            ], [
                "id" => "INBOX.Drafts",
                "mailAccountId" => $account->getId(),
                "name" => "Drafts",
                "unreadCount" => 4,
                "cn_folderType" => "DRAFT",
                "data" => []
            ]]
        ]]);
    }


    public function testMapFullFolderNameToType() {

        $service = new DefaultMailFolderService();

        $this->assertSame($service->mapFullFolderNameToType("SomeRandomFolder.Draft", "."), "INBOX");
        $this->assertSame($service->mapFullFolderNameToType("SomeRandom", "."), "INBOX");
        $this->assertSame($service->mapFullFolderNameToType("INBOX", "."), "INBOX");
        $this->assertSame($service->mapFullFolderNameToType("INBOX.Somefolder.Deep.Drafts", "."), "INBOX");
        $this->assertSame($service->mapFullFolderNameToType("INBOX.Drafts", "."), "DRAFT");
        $this->assertSame($service->mapFullFolderNameToType("INBOX.Trash.Deep.Deeper.Folder", "."), "TRASH");

    }


}