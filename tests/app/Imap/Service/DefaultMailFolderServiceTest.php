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
        $service->getMailFoldersFor($this->getTestUserStub()->getImapAccount("dev_sys_conjoon_org"));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMailFoldersFor() {

        $account = $this->getTestUserStub()->getImapAccount("dev_sys_conjoon_org");

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $mbox1 = new \Horde_Imap_Client_Mailbox("INBOX", true);
        $mbox2 = new \Horde_Imap_Client_Mailbox("INBOX.Sent", true);
        $mbox3 = new \Horde_Imap_Client_Mailbox("INBOX.Drafts", true);
        $mbox4 = new \Horde_Imap_Client_Mailbox("INBOX.Drafts.Foobar", true);

        $mbox5 = new \Horde_Imap_Client_Mailbox("INBOX.Nonexistent", true);
        $mbox6 = new \Horde_Imap_Client_Mailbox("INBOX.Noselect", true);
        $mbox7 = new \Horde_Imap_Client_Mailbox("INBOX.Nonexistent.Child", true);

        $mbox8 = new \Horde_Imap_Client_Mailbox("INBOX.Sent.Foobar.YO!", true);

        $imapReturn = [
            "INBOX" => [
                "delimiter" => ".",
                "mailbox"   => $mbox1,
                "attributes" => []
            ],
            "INBOX.Sent" => [
                "delimiter" => ".",
                "mailbox"   => $mbox2,
                "attributes" => []
            ],
            "INBOX.Sent.Foobar" => [
                "delimiter" => ".",
                "mailbox"   => $mbox4,
                "attributes" => []
            ],
            "INBOX.Sent.Foobar.YO!" => [
                "delimiter" => ".",
                "mailbox"   => $mbox8,
                "attributes" => []
            ],
            "INBOX.Drafts" => [
                "delimiter" => ".",
                "mailbox"   => $mbox3,
                "attributes" => []
            ],
            "INBOX.Nonexistent" => [
                "delimiter" => ".",
                "mailbox"   => $mbox5,
                "attributes" => ['\nonexistent']
            ],
            "INBOX.Noselect" => [
                "delimiter" => ".",
                "mailbox"   => $mbox6,
                "attributes" => ['\noselect']
            ],
            "INBOX.Nonexistent.Child" => [
            "delimiter" => ".",
            "mailbox"   => $mbox7,
            "attributes" => []
            ]
        ];


        $imapStub->shouldReceive('listMailboxes')
            ->once()
            ->with('*', \Horde_Imap_Client::MBOX_ALL, ["attributes" => true])
            ->andReturn($imapReturn);


        $imapStub->shouldReceive('status')->with($mbox1, 16)->andReturn(["unseen" => 0]);
        $imapStub->shouldReceive('status')->with($mbox2, 16)->andReturn(["unseen" => 3]);
        $imapStub->shouldReceive('status')->with($mbox3, 16)->andReturn(["unseen" => 4]);
        $imapStub->shouldReceive('status')->with($mbox4, 16)->andReturn(["unseen" => 1]);
        $imapStub->shouldReceive('status')->with($mbox8, 16)->andReturn(["unseen" => 311]);
        $imapStub->shouldReceive('status')->with($mbox7, 16)->andReturn(["unseen" => 9]);

        $imapStub->shouldNotReceive('status')->with($mbox5, 16);
        $imapStub->shouldNotReceive('status')->with($mbox6, 16);

        $service = new DefaultMailFolderService();


        $this->assertSame($service->getMailFoldersFor($account), [
            [
                "id" => "INBOX",
                "mailAccountId" => $account->getId(),
                "name" => "INBOX",
                "unreadCount" => 0,
                "cn_folderType" => "INBOX",
                "data" => []
            ],
            [
                "id" => "INBOX.Sent",
                "mailAccountId" => $account->getId(),
                "name" => "Sent",
                "unreadCount" => 3,
                "cn_folderType" => "SENT",
                "data" => [
                    [
                        "id" => "INBOX.Sent.Foobar",
                        "mailAccountId" => $account->getId(),
                        "name" => "Foobar",
                        "unreadCount" => 1,
                        "cn_folderType" => "SENT",
                        "data" => [
                            [
                            "id" => "INBOX.Sent.Foobar.YO!",
                            "mailAccountId" => $account->getId(),
                            "name" => "YO!",
                            "unreadCount" => 311,
                            "cn_folderType" => "SENT",
                            "data" => []
                            ]
                        ]
                    ]
                ],
            ], [
                "id" => "INBOX.Drafts",
                "mailAccountId" => $account->getId(),
                "name" => "Drafts",
                "unreadCount" => 4,
                "cn_folderType" => "DRAFT",
                "data" => []
            ],
            [
                "id" => "INBOX.Nonexistent.Child",
                "mailAccountId" => $account->getId(),
                "name" => "INBOX.Nonexistent.Child",
                "unreadCount" => 9,
                "cn_folderType" => "FOLDER",
                "data" => []
            ]
        ]);

    }


    public function testMapFullFolderIdToType() {

        $service = new DefaultMailFolderService();

        $this->assertSame($service->mapFullFolderIdToType("SomeRandomFolder/Draft", "/"), "FOLDER");
        $this->assertSame($service->mapFullFolderIdToType("SomeRandomFolder/Draft/Test", "/"), "FOLDER");
        $this->assertSame($service->mapFullFolderIdToType("SomeRandom", "."), "FOLDER");
        $this->assertSame($service->mapFullFolderIdToType("INBOX", "."), "INBOX");
        $this->assertSame($service->mapFullFolderIdToType("INBOX/Somefolder/Deep/Drafts", "/"), "FOLDER");
        $this->assertSame($service->mapFullFolderIdToType("INBOX.Drafts", "."), "DRAFT");
        $this->assertSame($service->mapFullFolderIdToType("INBOX.Trash.Deep.Deeper.Folder", "."), "FOLDER");

        $this->assertSame($service->mapFullFolderIdToType("Junk/Draft", "/"), "FOLDER");
        $this->assertSame($service->mapFullFolderIdToType("TRASH.Draft.folder", "."), "FOLDER");
        $this->assertSame($service->mapFullFolderIdToType("TRASH", "/"), "TRASH");

        $this->assertSame($service->mapFullFolderIdToType("INBOX:TRASH", ":"), "TRASH");
        $this->assertSame($service->mapFullFolderIdToType("INBOX-DRAFTS", "-"), "DRAFT");

    }


    public function testShouldBeRootFolder() {

        $service = new DefaultMailFolderService();

        $this->assertFalse($service->shouldBeRootFolder("SomeRandomFolder/Draft", "/"));
        $this->assertTrue($service->shouldBeRootFolder("SomeRandom", "."));
        $this->assertTrue($service->shouldBeRootFolder("INBOX", "."));
        $this->assertFalse($service->shouldBeRootFolder("INBOX/Somefolder/Deep/Drafts", "/"));
        $this->assertTrue($service->shouldBeRootFolder("INBOX/Drafts", "/"));
        $this->assertFalse($service->shouldBeRootFolder("INBOX.Trash.Deep.Deeper.Folder", "."));

        $this->assertFalse($service->shouldBeRootFolder("Junk/Draft", "/"));
        $this->assertFalse($service->shouldBeRootFolder("TRASH.Draft.folder", "."));
        $this->assertTrue($service->shouldBeRootFolder("TRASH", "/"));

    }


    public function testShouldSkipMailbox() {

        $service = new DefaultMailFolderService();

        $mailboxes = [
            "NOSELECT" => [
                "id" => "NOSELECT",
                "delimiter" => "/",
                "attributes" => ['\noselect']
            ],
            "Nonexistant" => [
                "id" => "Nonexistant",
                "delimiter" => "/",
                "attributes" => ['\nonexistent']
            ],
            "Nonexistant/butchildokay" => [
                "id" => "Nonexistant/butchildokay",
                "delimiter" => "/",
                "attributes" => []
            ],
            "canselect/childokay" => [
                "id" => "canselect/childokay",
                "delimiter" => "/",
                "attributes" => []
            ],
            "canselect" => [
                "id" => "canselect",
                "delimiter" => "/",
                "attributes" => []
            ]
        ];

        $this->assertTrue($service->shouldSkipMailbox("NOSELECT", $mailboxes));
        $this->assertTrue($service->shouldSkipMailbox("Nonexistant", $mailboxes));
        $this->assertFalse($service->shouldSkipMailbox("Nonexistant/butchildokay", $mailboxes));
        $this->assertFalse($service->shouldSkipMailbox("canselect", $mailboxes));
        $this->assertFalse($service->shouldSkipMailbox("canselect/childokay", $mailboxes));



    }



}