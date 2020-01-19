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

use Conjoon\Horde\Mail\Client\Imap\HordeClient,
    Conjoon\Mail\Client\Data\MailAddress,
    Conjoon\Mail\Client\Data\MailAccount,
    Conjoon\Mail\Client\Data\MailAddressList,
    Conjoon\Mail\Client\Data\CompoundKey\MessageKey,
    Conjoon\Mail\Client\Data\CompoundKey\FolderKey,
    Conjoon\Mail\Client\Imap\ImapClientException,
    Conjoon\Mail\Client\Message\MessageBody,
    Conjoon\Mail\Client\Message\MessageBodyDraft,
    Conjoon\Mail\Client\Message\MessageItem,
    Conjoon\Mail\Client\Message\MessageItemDraft,
    Conjoon\Mail\Client\Message\MessagePart,
    Conjoon\Mail\Client\Message\MessageItemList,
    Conjoon\Mail\Client\Message\ListMessageItem,
    Conjoon\Mail\Client\Folder\MailFolderList,
    Conjoon\Mail\Client\Message\Flag\FlagList,
    Conjoon\Mail\Client\Message\Flag\SeenFlag,
    Conjoon\Mail\Client\Message\Flag\FlaggedFlag,
    Conjoon\Mail\Client\Message\Composer\BodyComposer,
    Conjoon\Mail\Client\Message\Composer\HeaderComposer;


/**
 * Class HordeClientTest
 */
class HordeClientTest extends TestCase {

    use TestTrait;


    /**
     * Tests constructor and base class.
     */
    public function testInstance() {

        $client = $this->createClient();
        $this->assertInstanceOf(\Conjoon\Mail\Client\MailClient::class, $client);
    }


    /**
     * Tests getMailAccount()
     */
    public function testGetMailAccount() {

        $mailAccount = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $client = $this->createClient($mailAccount);

        $someKey = new FolderKey("foo", "bar");
        $this->assertSame(null, $client->getMailAccount($someKey));


        $key = new FolderKey($mailAccount->getId(), "bar");

        $this->assertSame(
            $mailAccount,
            $client->getMailAccount($key)
        );

        $this->assertSame(
            $mailAccount,
            $client->getMailAccount($key->getMailAccountId())
        );

        $this->assertSame(
            null,
            $client->getMailAccount(89)
        );
    }


    /**
     * Tests connect() with exception
     */
    public function testConnect_exception()
    {

        $this->expectException(ImapClientException::class);
        $client = $this->createClient();

        $someKey = new FolderKey("foo", "bar");
        $client->connect($someKey);
    }


    /**
     * Tests connect()
     */
    public function testConnect()
    {
        $mailAccount = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $client = $this->createClient($mailAccount);

        $someKey = new FolderKey($mailAccount->getId(), "bar");
        $socket = $client->connect($someKey);
        $this->assertInstanceOf(\Horde_Imap_Client_Socket::class, $socket);

        $someKey2 = new FolderKey($mailAccount->getId(), "bar");
        $socket2 = $client->connect($someKey2);

        $this->assertSame($socket, $socket2);

        $socket3 = $client->connect($mailAccount->getId());

        $this->assertSame($socket, $socket3);

    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageItemList_exception() {

        $this->expectException(ImapClientException::class);

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive('query')
            ->andThrow(new \Exception("This exception should be caught properly by the test"));

        $client = $this->createClient();
        $client->getMessageItemList(
            $this->createFolderKey(
                $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org")->getId(),
            "INBOX"
            ), ["start" => 0, "limit" => 25], function(){}
        );
    }


    /**
     * Multiple Message Item Test
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageItemList() {

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive('search')->with("INBOX", \Mockery::any(), [
            "sort" => [\Horde_Imap_Client::SORT_REVERSE, \Horde_Imap_Client::SORT_DATE]
        ])->andReturn(["match" => new \Horde_Imap_Client_Ids([111, 222, 333])]);

        $fetchResults = new \Horde_Imap_Client_Fetch_Results();

        $fetchResults[111] = new \Horde_Imap_Client_Data_Fetch();
        $fetchResults[111]->setUid(111);
        $fetchResults[222] = new \Horde_Imap_Client_Data_Fetch();
        $fetchResults[222]->setUid(222);

        $fetchResults[111]->setEnvelope(['from' => "dev@conjoon.org", "to" => "devrec@conjoon.org"]);
        $fetchResults[111]->setHeaders('ContentType', 'Content-Type=text/html;charset=UTF-8');
        $fetchResults[222]->setEnvelope(['from' => "dev2@conjoon.org"]);
        $fetchResults[222]->setHeaders('ContentType', 'Content-Type=text/plain;charset= ISO-8859-1');


        $imapStub->shouldReceive('fetch')->with(
            "INBOX", \Mockery::any(),
            \Mockery::type('array')
        )->andReturn(
            $fetchResults
        );

        $client = $this->createClient();

        $messageItemList = $client->getMessageItemList(
            $this->createFolderKey(
                $account->getId(),
                "INBOX"
            ),
            ["start" => 0, "limit" => 2]
        );


        $this->assertInstanceOf(MessageItemList::class, $messageItemList);

        $this->assertSame(2, count($messageItemList));

        $this->assertInstanceOf(ListMessageItem::Class, $messageItemList[0]);
        $this->assertInstanceOf(ListMessageItem::Class, $messageItemList[1]);

        $this->assertSame("utf-8", $messageItemList[0]->getCharset());
        $this->assertSame("iso-8859-1", $messageItemList[1]->getCharset());

        $this->assertSame("111", $messageItemList[0]->getMessageKey()->getId());
        $this->assertSame("INBOX", $messageItemList[0]->getMessageKey()->getMailFolderId());
        $this->assertEquals(
            ["name" => "dev@conjoon.org", "address" => "dev@conjoon.org"], $messageItemList[0]->getFrom()->toJson()
        );
        $this->assertEquals(
            [["name" => "devrec@conjoon.org", "address" => "devrec@conjoon.org"]], $messageItemList[0]->getTo()->toJson()
        );
        $this->assertEquals(
            [], $messageItemList[1]->getTo()->toJson()
        );

    }


    /**
     * Single messageItem Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageItem() {


        $client = $this->createClient();

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $fetchResults = new \Horde_Imap_Client_Fetch_Results();
        $fetchResults[16] = new \Horde_Imap_Client_Data_Fetch();
        $fetchResults[16]->setUid("16");
        $fetchResults[16]->setSize("1600");

        $attach = new \Horde_Mime_Part;
        $attach->setDisposition("attachment");
        $fetchResults[16]->setStructure($attach);


        $imapStub->shouldReceive('fetch')->with(
            "INBOX", \Mockery::any(), \Mockery::type('array')
        )->andReturn($fetchResults);


        $item = $client->getMessageItem($this->createMessageKey($account->getId(), "INBOX", "16"));

        $this->assertInstanceOf(MessageItem::class, $item);
        $this->assertSame(null, $item->getFrom());
        $this->assertSame(1600, $item->getSize());
        $this->assertSame(true, $item->getHasAttachments());
    }


    /**
     * Single messageItemDraft Test (get)
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageItemDraft() {


        $client = $this->createClient();

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $fetchResults = new \Horde_Imap_Client_Fetch_Results();
        $fetchResults[16] = new \Horde_Imap_Client_Data_Fetch();
        $fetchResults[16]->setUid("16");

        $cc   = new MailAddressList;
        $cc[] = new MailAddress("test@dot", "test@dot");

        $bcc   = new MailAddressList;
        $bcc[] = new MailAddress("test2@dot", "test2@dot");

        $replyTo = new MailAddress("test@replyto", "test@replyto");

        $fetchResults[16]->setEnvelope([
            "cc"      => $cc[0]->getAddress(),
            "bcc"     => $bcc[0]->getAddress(),
            "reply-to" => $replyTo->getAddress()
        ]);

        $imapStub->shouldReceive('fetch')->with(
            "INBOX", \Mockery::any(), \Mockery::type('array')
        )->andReturn($fetchResults);

        $item = $client->getMessageItemDraft($this->createMessageKey($account->getId(), "INBOX", "16"));

        $this->assertInstanceOf(MessageItemDraft::class, $item);
        $this->assertEquals($cc, $item->getCc());
        $this->assertEquals($bcc, $item->getBcc());
        $this->assertEquals($replyTo, $item->getReplyTo());
        
    }


    /**
     * Single MessageBody Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageBody() {

        $client = $this->createClient();

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $fetchResults = new \Horde_Imap_Client_Fetch_Results();
        $fetchResults[16] = new \Horde_Imap_Client_Data_Fetch();
        $fetchResults[16]->setUid("16");

        $imapStub->shouldReceive('fetch')->with(
            "INBOX", \Mockery::any(), \Mockery::type('array')
        )->andReturn($fetchResults);

        $key = $this->createMessageKey($account->getId(), "INBOX", "16");

        $messageBody = $client->getMessageBody($key);

        $this->assertInstanceOf(MessageBody::Class, $messageBody);
    }


    /**
     * Tests getTotalMessageCount
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetTotalMessageCount() {
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive('search')->with("INBOX", \Mockery::any(), [])
                 ->andReturn(["match" => new \Horde_Imap_Client_Ids([111, 222, 333])]);

        $client = $this->createClient();

        $count = $client->getTotalMessageCount($this->createFolderKey($account->getId(), "INBOX"));
        $this->assertSame(3, $count);
    }


    /**
     * Tests getTotalUnreadCount
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetTotalUnreadCount() {
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive('status')->with("INBOX", \Horde_Imap_Client::STATUS_UNSEEN)
                 ->andReturn(["unseen" => 2]);

        $client = $this->createClient();

        $unreadCount = $client->getUnreadMessageCount($this->createFolderKey($account->getId(), "INBOX"));
        $this->assertSame(2, $unreadCount);
    }


    /**
     * Tests getMailFolderList
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMailFolderList() {

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive('listMailboxes')->with(
            "*",
            \Horde_Imap_Client::MBOX_ALL,
            ["attributes" => true]
        )->andReturn([
            "INBOX" => ["delimiter" => ".", "attributes" => []],
            "INBOX.Folder" => ["delimiter" => ":", "attributes" => ["\\noselect"]]
        ]);

        $imapStub->shouldReceive('status')->with(
            "INBOX",
            \Horde_Imap_Client::STATUS_UNSEEN
        )->andReturn(["unseen" => 30]);

        $imapStub->shouldNotReceive('status')->with(
            "INBOX.Folder",
            \Horde_Imap_Client::STATUS_UNSEEN
        );


        $client = $this->createClient();

        $mailFolderList = $client->getMailFolderList($account);

        $this->assertInstanceOf(MailFolderList::class, $mailFolderList);

        $listMailFolder = $mailFolderList[0];
        $this->assertSame("INBOX", $listMailFolder->getName());
        $this->assertSame(".", $listMailFolder->getDelimiter());
        $this->assertSame(30, $listMailFolder->getUnreadCount());
        $this->assertSame([], $listMailFolder->getAttributes());

        $listMailFolder = $mailFolderList[1];
        $this->assertSame("INBOX.Folder", $listMailFolder->getName());
        $this->assertSame(":", $listMailFolder->getDelimiter());
        $this->assertSame(0, $listMailFolder->getUnreadCount());
        $this->assertSame(["\\noselect"], $listMailFolder->getAttributes());
    }


    /**
     * Test getFileAttachmentList()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetFileAttachmentList() {

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $mailFolderId  = "INBOX";
        $messageItemId = "123";

        $messageKey = new MessageKey($account, $mailFolderId, $messageItemId);

        $fetchResults = unserialize('O:31:"Horde_Imap_Client_Fetch_Results":3:{s:5:"_data";a:1:{i:155117;O:28:"Horde_Imap_Client_Data_Fetch":1:{s:5:"_data";a:3:{i:14;i:103958;i:13;i:155117;i:1;C:15:"Horde_Mime_Part":1372:{a:10:{i:0;i:2;i:1;N;i:2;s:1:" ";i:3;N;i:4;C:18:"Horde_Mime_Headers":490:{a:3:{i:0;i:3;i:1;a:2:{s:19:"Content-Disposition";C:50:"Horde_Mime_Headers_ContentParam_ContentDisposition":96:{a:3:{s:7:"_params";a:0:{}s:5:"_name";s:19:"Content-Disposition";s:7:"_values";a:1:{i:0;s:0:"";}}}s:12:"Content-Type";C:43:"Horde_Mime_Headers_ContentParam_ContentType":191:{a:3:{s:7:"_params";a:2:{s:7:"charset";s:8:"us-ascii";s:8:"boundary";s:34:"=_0ee451bb88ceef8dab403daf6c4b30cb";}s:5:"_name";s:12:"Content-Type";s:7:"_values";a:1:{i:0;s:15:"multipart/mixed";}}}}i:2;s:1:" ";}}i:5;a:0:{}i:6;s:1:"0";i:7;a:1:{i:0;C:15:"Horde_Mime_Part":717:{a:10:{i:0;i:2;i:1;s:5:"60918";i:2;s:1:" ";i:3;N;i:4;C:18:"Horde_Mime_Headers":575:{a:3:{i:0;i:3;i:1;a:2:{s:19:"Content-Disposition";C:50:"Horde_Mime_Headers_ContentParam_ContentDisposition":188:{a:3:{s:7:"_params";a:2:{s:4:"size";s:5:"60918";s:8:"filename";s:35:"Image Pasted at 2019-9-30 14-57.png";}s:5:"_name";s:19:"Content-Disposition";s:7:"_values";a:1:{i:0;s:10:"attachment";}}}s:12:"Content-Type";C:43:"Horde_Mime_Headers_ContentParam_ContentType":183:{a:3:{s:7:"_params";a:2:{s:7:"charset";s:8:"us-ascii";s:4:"name";s:35:"Image Pasted at 2019-9-30 14-57.png";}s:5:"_name";s:12:"Content-Type";s:7:"_values";a:1:{i:0;s:10:"image/jpeg";}}}}i:2;s:1:" ";}}i:5;a:0:{}i:6;s:1:"1";i:7;a:0:{}i:8;i:0;i:9;s:6:"base64";}}}i:8;i:0;i:9;s:6:"binary";}}}}}s:8:"_keyType";i:2;s:8:"_obClass";s:28:"Horde_Imap_Client_Data_Fetch";}');

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive('fetch')->with(
            $mailFolderId,
            \Mockery::any(),
            \Mockery::any()
        )->andReturn($fetchResults);

        $client = $this->createClient();

        $fileAttachmentList = $client->getFileAttachmentList($messageKey);

        $this->assertSame(1, count($fileAttachmentList));

        $fileAttachment = $fileAttachmentList[0];

        $this->assertSame("image/jpeg",                          $fileAttachment->getType());
        $this->assertSame("Image Pasted at 2019-9-30 14-57.png", $fileAttachment->getText());
    }


    /**
     * Tests setFlags
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSetFlags() {
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $messageItemId = "123";
        $mailFolderId  = "INBOX";

        $imapStub->shouldReceive('store')->with(
            $mailFolderId, [
                "ids"    => new \Horde_Imap_Client_Ids([$messageItemId]),
                "add"    => ["\\Seen"],
                "remove" => ["\\Flagged"]
            ]
        )->andReturn(true);

        $client = $this->createClient();

        $flagList = new FlagList();
        $flagList[] = new SeenFlag(true);
        $flagList[] = new FlaggedFlag(false);

        $result = $client->setFlags(
            $this->createMessageKey($account->getId(), $mailFolderId, $messageItemId),
            $flagList
        );
        $this->assertSame(true, $result);
    }

    /**
     * Tests createMessageBody with a MessageBodyDraft that already has a MessageKey
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCreateMessageBodyDraft_hasMessageKey() {

        $this->expectException(ImapClientException::class);

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId  = "INBOX";
        $folderKey = new FolderKey($account, $mailFolderId);
        $client = $this->createClient();
        $client->createMessageBodyDraft(
            $folderKey, new MessageBodyDraft(new MessageKey("a", "b", "c"))
        );

    }


    /**
     * Tests createMessageBodyDraft
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCreateMessageBodyDraft() {

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId  = "INBOX";
        $folderKey = new FolderKey($account, $mailFolderId);
        $messageItemId = "989786";

        $messageBodyDraft = new MessageBodyDraft();
        $htmlPart = new MessagePart("foo", "UTF-8", "text/html");
        $plainPart = new MessagePart("bar", "UTF-8", "text/plain");
        $messageBodyDraft->setTextHtml($htmlPart);
        $messageBodyDraft->setTextPlain($plainPart);

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive('append')->with(
            $folderKey->getId(), [["data" => "__HEADER__\n\nFULL_TXT_MSG"]]
        )->andReturn(new \Horde_Imap_Client_Ids([$messageItemId]));

        $imapStub->shouldReceive('store')->with(
            $mailFolderId, [
                "ids"    => new \Horde_Imap_Client_Ids([$messageItemId]),
                "add"    => ["\\Draft"]
            ]
        );


        $client = $this->createClient();
        $res = $client->createMessageBodyDraft($folderKey, $messageBodyDraft);

        $this->assertNotSame($res, $messageBodyDraft);
        $this->assertSame($res->getMessageKey()->getMailAccountId(), $account->getId());
        $this->assertSame($res->getMessageKey()->getMailFolderId(), $mailFolderId);
        $this->assertSame($res->getMessageKey()->getId(), $messageItemId);
    }


    /**
     * Tests updateMessageBodyDraft with a MessageBodyDraft that has no MessageKey
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCreateMessageBodyDraft_hasNoMessageKey() {

        $this->expectException(ImapClientException::class);

        $client = $this->createClient();
        $client->updateMessageBodyDraft(new MessageBodyDraft());

    }


    /**
     * Tests updateMessageBodyDraft
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUpdateMessageBodyDraft() {

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId  = "INBOX";
        $messageItemId = "989786";
        $createdId     = "u";
        $messageKey = new MessageKey($account, $mailFolderId, $messageItemId);

        $messageBodyDraft = new MessageBodyDraft($messageKey);
        $htmlPart = new MessagePart("foo", "UTF-8", "text/html");
        $plainPart = new MessagePart("bar", "UTF-8", "text/plain");
        $messageBodyDraft->setTextHtml($htmlPart);
        $messageBodyDraft->setTextPlain($plainPart);

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive('append')->with(
            $messageKey->getMailFolderId(), [["data" => "__HEADER__\n\nFETCHED\n\nFULL_TXT_MSG"]]
        )->andReturn(new \Horde_Imap_Client_Ids([$createdId]));


        $imapStub->shouldReceive('store')->with(
            $mailFolderId, [
                "ids"    => new \Horde_Imap_Client_Ids([$createdId]),
                "add"    => ["\\Draft"]
            ]
        );

        $rangeList = new \Horde_Imap_Client_Ids();
        $rangeList->add($messageItemId);

        $fetchResult = [];
        $fetchResult[$messageKey->getId()] = new class() {
            public function getFullMsg($bool) {
                return "FETCHED";
            }
        };
        $imapStub->shouldReceive("fetch")
            ->with(
                $messageKey->getMailFolderId(),
                \Mockery::any(),
                ['ids' => $rangeList]
            )
            ->andReturn($fetchResult);

        $imapStub->shouldReceive("expunge")
            ->with(
                $messageKey->getMailFolderId(),
                ["delete" => true, "ids" => $rangeList]
            );


        $client = $this->createClient();
        $res = $client->updateMessageBodyDraft($messageBodyDraft);

        $this->assertNotSame($res, $messageBodyDraft);
        $this->assertSame($res->getMessageKey()->getMailAccountId(), $account->getId());
        $this->assertSame($res->getMessageKey()->getMailFolderId(), $mailFolderId);
        $this->assertSame($res->getMessageKey()->getId(), $createdId);
    }


    /**
     * Tests updateMessageDraft
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUpdateMessageDraft() {

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId  = "INBOX";
        $messageItemId = "989786";
        $messageKey = new MessageKey($account, $mailFolderId, $messageItemId);
        $resultMessageKey = new MessageKey($account, $mailFolderId, "abcd");


        $to = new MailAddressList();
        $to[] = new MailAddress("test@test.com", "test");

        $cc = new MailAddressList();
        $cc[] = new MailAddress("test@dropi.org", "test");
        $cc[] = new MailAddress("test2@dropi.org", "test2");

        $bcc = new MailAddressList();
        $bcc[] = new MailAddress("test@test.com", "test");


        $messageItemDraft = new MessageItemDraft($messageKey, [
            "subject" => "foo",
            "from" => new MailAddress("testa@fobbar.com", "test"),
            "to" => $to,
            "cc" => $cc,
            "bcc" => $bcc,
            "date" => new \DateTime("@1234566"),
            "replyTo" => new MailAddress("test@foobar.com", "test")
        ]);


        $rangeList = new \Horde_Imap_Client_Ids();
        $rangeList->add($messageKey->getId());

        $resultList = new \Horde_Imap_Client_Ids();
        $resultList->add($resultMessageKey->getId());

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $fetchResult = [];
        $fetchResult[$messageKey->getId()] = new class() {
            public function getFullMsg($bool) {
                return "BODY";
            }
        };

        $imapStub->shouldReceive("fetch")
                  ->with(
                      $messageKey->getMailFolderId(),
                      \Mockery::any(),
                      ['ids' => $rangeList]
                      )
                  ->andReturn($fetchResult);

        $fullMsg = "__HEADER__\n\nBODY";

        $imapStub->shouldReceive("append")
            ->with(
                $messageKey->getMailFolderId(),
                [["data" => $fullMsg]]
            )
            ->andReturn($resultList);

        $imapStub->shouldReceive("store")
            ->with(
                $messageKey->getMailFolderId(),
                ["ids" => $resultList,
                 "add" => ["\Draft"]
                ]
            );


        $imapStub->shouldReceive("expunge")
                 ->with(
                     $messageKey->getMailFolderId(),
                     ["delete" => true, "ids" => $rangeList]
                 );

        $client = $this->createClient();

        $res = $client->updateMessageDraft($messageItemDraft);

        $aJson = $messageItemDraft->toJson();
        $bJson = $res->toJson();

        unset($aJson["id"]);
        unset($bJson["id"]);

        $this->assertEquals($aJson, $bJson);

        $this->assertNotSame($res, $messageItemDraft);
        $this->assertEquals($res->getMessageKey(), $resultMessageKey);
    }

    /**
     * Tests getMailer() with exception
     */
    public function testGetMailer_exception()
    {

        $this->expectException(ImapClientException::class);
        $client = $this->createClient();

        $someAccount = new MailAccount(["id" => "foo"]);
        $client->getMailer($someAccount);
    }


    /**
     * Tests getMailer()
     */
    public function testGetMailer()
    {
        $mailAccount = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $client = $this->createClient($mailAccount);

        $someAccount = new MailAccount(["id" => $mailAccount->getId()]);
        $mailer = $client->getMailer($someAccount);
        $this->assertInstanceOf(\Horde_Mail_Transport::class, $mailer);

        $someAccount2 = new MailAccount(["id" => $mailAccount->getId()]);
        $mailer2 = $client->getMailer($someAccount2);

        $this->assertSame($mailer, $mailer2);

        $mailer3 = $client->getMailer($someAccount);

        $this->assertSame($mailer, $mailer3);

    }


    /**
     * Tests sendMessageDraft exception (no draft)
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSendMessageDraft_noDraft() {

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId  = "DRAFTS";
        $messageItemId = "989786";
        $messageKey = new MessageKey($account, $mailFolderId, $messageItemId);

        $client = $this->createClient();

        $rangeList = new \Horde_Imap_Client_Ids();
        $rangeList->add($messageKey->getId());

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $fetchResult = [];
        $fetchResult[$messageKey->getId()] = new class() {
            public function __construct() {
            }

            public function getFullMsg() {
            }
            public function getFlags() {
                return [];
            }
        };


        $imapStub->shouldReceive("fetch")
            ->with(
                $messageKey->getMailFolderId(),
                \Mockery::any(),
                ['ids' => $rangeList]
            )
            ->andReturn($fetchResult);

        $this->expectException(ImapClientException::class);
        $client->sendMessageDraft($messageKey);

    }


    /**
     * Tests sendMessageDraft
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSendMessageDraft() {

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId  = "DRAFTS";
        $messageItemId = "989786";
        $messageKey = new MessageKey($account, $mailFolderId, $messageItemId);

        $client = $this->createClient();

        $rangeList = new \Horde_Imap_Client_Ids();
        $rangeList->add($messageKey->getId());

        $imapStub        = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);
        $mailer          = $client->getMailer($account);
        $mimePartStub    = \Mockery::mock('overload:'.\Horde_Mime_Part::class);
        $mimeHeadersStub = \Mockery::mock('overload:'.\Horde_Mime_Headers::class);
        $mimeMailStub    = \Mockery::mock('overload:'.\Horde_Mime_Mail::class);


        $headers     = [];
        $basePart    = null;
        $rawMsg      = "RAWMESSAGE";
        $fetchResult = [];
        $fetchResult[$messageKey->getId()] = new class($rawMsg) {

            public function __construct($rawMsg) {
                $this->rawMsg = $rawMsg;
            }

            public function getFullMsg($bool) {
                return $this->rawMsg;
            }
            public function getFlags() {
                return [\Horde_Imap_Client::FLAG_DRAFT];
            }
        };


        $imapStub->shouldReceive("fetch")
            ->with(
                $messageKey->getMailFolderId(),
                \Mockery::any(),
                ['ids' => $rangeList]
            )
            ->andReturn($fetchResult);


        $mimePartStub->shouldReceive("parseMessage")
            ->with($rawMsg)
            ->andReturn($basePart);

        $mimeHeadersStub->shouldReceive("parseHeaders")
            ->with($rawMsg)
            ->andReturn($headers);

        $mimeMailStub->shouldReceive("setBasePart")
            ->with($basePart);

        $mimeMailStub->shouldReceive("send")->with($mailer);

        $res = $client->sendMessageDraft($messageKey);

        $this->assertTrue($res);

    }


    /**
     * Tests moveMessage with keys not sharing same MailAccountId
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testMoveMessage() {
        $this->funcForTestMove(false);
    }


    /**
     * Tests moveMessage with exception thrown by client
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testMoveMessage_exception() {
        $this->funcForTestMove(true);
    }


    /**
     * Tests moveMessage with keys not sharing same MailAccountId
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testMoveMessage_differerentMailAccountId() {


        $client = $this->createClient();

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId     = "DRAFTS";
        $messageItemId    = "989786";
        $toMailFolderId   = "SENT";
        $newMessageItemId = "abcde";

        $messageKey     = new MessageKey($account, $mailFolderId, $messageItemId);
        $folderKey      = new FolderKey("zut", $toMailFolderId);

        $this->expectException(ImapClientException::class);
        $client->moveMessage($messageKey, $folderKey);
    }


    /**
     * Tests moveMessage with same mailFolderIds
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testMoveMessage_sameMailFolderIds() {


        $client = $this->createClient();

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId     = "DRAFTS";
        $messageItemId    = "989786";
        $toMailFolderId   = $mailFolderId;

        $messageKey     = new MessageKey($account, $mailFolderId, $messageItemId);
        $folderKey      = new FolderKey($account, $toMailFolderId);

        $res = $client->moveMessage($messageKey, $folderKey);

        $this->assertSame($res, $messageKey);

    }

// -------------------------------
//  Helper
// -------------------------------

    /**
     * helper for moveMessage tests
     */
    protected function funcForTestMove($exception = false) {


        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId     = "DRAFTS";
        $messageItemId    = "989786";
        $toMailFolderId   = "SENT";
        $newMessageItemId = "abcde";

        $messageKey     = new MessageKey($account, $mailFolderId, $messageItemId);
        $folderKey      = new FolderKey($account, $toMailFolderId);
        $cmpMessageKey  = new MessageKey($account, $toMailFolderId, $newMessageItemId);

        $client = $this->createClient();

        $rangeList = new \Horde_Imap_Client_Ids();
        $rangeList->add($messageKey->getId());

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);
        $proc = $imapStub->shouldReceive("copy")
            ->with(
                $mailFolderId,
                $toMailFolderId,
                ['ids' => $rangeList, "move" => true, "force_map" => true]
            );

        if ($exception === true) {
            $this->expectException(ImapClientException::class);
            $proc->andThrow(new \Exception("foo"));
            $client->moveMessage($messageKey, $folderKey);
        } else {
            $proc->andReturn([$messageItemId => $newMessageItemId]);
            $res = $client->moveMessage($messageKey, $folderKey);
            $this->assertEquals($res, $cmpMessageKey);
        }
    }


    /**
     * @param $mid
     * @param $id
     * @return FolderKey
     */
    protected function createFolderKey($mid, $id) {
        return new FolderKey($mid, $id);

    }


    /**
     * @param $mid
     * @param $fid
     * @param $id
     * @return MessageKey
     */
    protected function createMessageKey($mid, $fid, $id) {
        return new MessageKey($mid, $fid, $id);
    }


    /**
     * @return BodyComposer
     */
    protected function createBodyComposer() :BodyComposer {

        return new class() implements BodyComposer {

            public function compose(string $target, MessageBodyDraft $draft) :string {
                return trim($target) . "\n\n" . "FULL_TXT_MSG";
            }

        };

    }

    /**
     * @return HeaderComposer
     */
    protected function createHeaderComposer() :HeaderComposer {

        return new class() implements HeaderComposer {

            public function compose(string $target, MessageItemDraft $draft = null) :string {
                return "__HEADER__" . "\n\n" . trim($target);
            }

        };

    }


    /**
     * Creates an instance of HordeClient.
     *
     * @return HordeCient
     */
    protected function createClient($mailAccount = null, $bodyComposer = null, $headerComposer = null) :HordeClient {

        if (!$mailAccount) {
            $mailAccount = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        }

        if (!$bodyComposer) {
            $bodyComposer = $this->createBodyComposer();
        }

        if (!$headerComposer) {
            $headerComposer = $this->createHeaderComposer();
        }

        return new HordeClient($mailAccount, $bodyComposer, $headerComposer);
    }
}