<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2021-2022 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

namespace Tests\Conjoon\Horde\Mail\Client\Imap;

use Conjoon\Core\ParameterBag;
use Conjoon\Horde\Mail\Client\Imap\HordeClient;
use Conjoon\Mail\Client\Data\CompoundKey\FolderKey;
use Conjoon\Mail\Client\Data\CompoundKey\MessageKey;
use Conjoon\Mail\Client\Data\MailAccount;
use Conjoon\Mail\Client\Data\MailAddress;
use Conjoon\Mail\Client\Data\MailAddressList;
use Conjoon\Mail\Client\Folder\MailFolderList;
use Conjoon\Mail\Client\Imap\ImapClientException;
use Conjoon\Mail\Client\MailClient;
use Conjoon\Mail\Client\Message\Flag\FlaggedFlag;
use Conjoon\Mail\Client\Message\Flag\FlagList;
use Conjoon\Mail\Client\Message\Flag\SeenFlag;
use Conjoon\Mail\Client\Message\ListMessageItem;
use Conjoon\Mail\Client\Message\MessageBody;
use Conjoon\Mail\Client\Message\MessageBodyDraft;
use Conjoon\Mail\Client\Message\MessageItem;
use Conjoon\Mail\Client\Message\MessageItemDraft;
use Conjoon\Mail\Client\Message\MessageItemList;
use Conjoon\Mail\Client\Message\MessagePart;
use Conjoon\Mail\Client\Query\MessageItemListResourceQuery;
use DateTime;
use Exception;
use Horde_Imap_Client;
use Horde_Imap_Client_Data_Fetch;
use Horde_Imap_Client_Fetch_Results;
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Search_Query;
use Horde_Imap_Client_Socket;
use Horde_Mail_Transport;
use Horde_Mime_Headers;
use Horde_Mime_Headers_Element;
use Horde_Mime_Headers_Element_Single;
use Horde_Mime_Mail;
use Horde_Mime_Part;
use Mockery;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Tests\TestCase;
use Tests\TestTrait;
use Conjoon\Horde\Mail\Client\Imap\FilterTrait;
use Conjoon\Horde\Mail\Client\Imap\AttributeTrait;
use Conjoon\Horde\Mail\Client\Imap\AttachmentTrait;

/**
 * Class HordeClientTest
 * @package Tests\Conjoon\Horde\Mail\Client\Imap
 */
class HordeClientTest extends TestCase
{
    use TestTrait;
    use ClientGeneratorTrait;

    /**
     * Tests constructor and base class.
     */
    public function testClass()
    {
        $uses = class_uses(HordeClient::class);
        $this->assertContains(FilterTrait::class, $uses);
        $this->assertContains(AttributeTrait::class, $uses);
        $this->assertContains(AttachmentTrait::class, $uses);

        $client = $this->createClient();
        $this->assertInstanceOf(MailClient::class, $client);
    }


    /**
     * Tests getMailAccount()
     */
    public function testGetMailAccount()
    {

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
    public function testConnectException()
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
        $this->assertInstanceOf(Horde_Imap_Client_Socket::class, $socket);

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
    public function testGetMessageItemListException()
    {

        $this->expectException(ImapClientException::class);

        $imapStub = Mockery::mock("overload:" . Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive("query")
            ->andThrow(new Exception("This exception should be caught properly by the test"));

        $client = $this->createClient();
        $client->getMessageItemList(
            $this->createFolderKey(
                $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org")->getId(),
                "INBOX"
            ),
            new MessageItemListResourceQuery(new ParameterBag(["start" => 0, "limit" => 25]))
        );
    }


    /**
     * Multiple Message Item Test
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageItemList()
    {

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = Mockery::mock("overload:" . Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive("search")->with("INBOX", Mockery::any(), [
            "sort" => [Horde_Imap_Client::SORT_REVERSE, Horde_Imap_Client::SORT_DATE]
        ])->andReturn(["match" => new Horde_Imap_Client_Ids([111, 222, 333])]);

        $messageIds = [111 => "foo", 222 => "bar"];
        $references = [111 => "reffoo", 222 => "refbar"];

        $fetchResults = new Horde_Imap_Client_Fetch_Results();

        $fetchResults[111] = new Horde_Imap_Client_Data_Fetch();
        $fetchResults[111]->setUid(111);
        $fetchResults[222] = new Horde_Imap_Client_Data_Fetch();
        $fetchResults[222]->setUid(222);

        $fetchResults[111]->setEnvelope([
            "from" => "dev@conjoon.org", "to" => "devrec@conjoon.org", "message-id" => $messageIds[111]
        ]);
        $fetchResults[111]->setHeaders("ContentType", "Content-Type=text/html;charset=UTF-8");
        $fetchResults[111]->setHeaders("References", "References: " . $references[111]);
        $fetchResults[222]->setEnvelope(["from" => "dev2@conjoon.org", "message-id" => $messageIds[222]]);
        $fetchResults[222]->setHeaders("ContentType", "Content-Type=text/plain;charset= ISO-8859-1");
        $fetchResults[222]->setHeaders("References", "References: " . $references[222]);

        $imapStub->shouldReceive("fetch")->with(
            "INBOX",
            Mockery::any(),
            Mockery::type("array")
        )->andReturn(
            $fetchResults
        );

        $client = $this->createClient();

        $messageItemList = $client->getMessageItemList(
            $this->createFolderKey(
                $account->getId(),
                "INBOX"
            ),
            new MessageItemListResourceQuery(new ParameterBag(["start" => 0, "limit" => 2]))
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
            ["name" => "dev@conjoon.org", "address" => "dev@conjoon.org"],
            $messageItemList[0]->getFrom()->toJson()
        );
        $this->assertEquals(
            [["name" => "devrec@conjoon.org", "address" => "devrec@conjoon.org"]],
            $messageItemList[0]->getTo()->toJson()
        );
        $this->assertEquals(
            [],
            $messageItemList[1]->getTo()->toJson()
        );

        $this->assertSame($messageIds[111], $messageItemList[0]->getMessageId());
        $this->assertSame($messageIds[222], $messageItemList[1]->getMessageId());

        $this->assertSame($references[111], $messageItemList[0]->getReferences());
        $this->assertSame($references[222], $messageItemList[1]->getReferences());
    }


    /**
     * Multiple Message Item Test with attributes specified
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageItemListWithSpecifiedAttributes()
    {

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = Mockery::mock("overload:" . Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive("search")->with("INBOX", Mockery::any(), [
            "sort" => [Horde_Imap_Client::SORT_REVERSE, Horde_Imap_Client::SORT_DATE]
        ])->andReturn(["match" => new Horde_Imap_Client_Ids([111])]);

        $messageIds = [111 => "foo"];
        $references = [111 => "reffoo"];

        $fetchResults = new Horde_Imap_Client_Fetch_Results();

        $fetchResults[111] = new Horde_Imap_Client_Data_Fetch();
        $fetchResults[111]->setUid(111);

        $fetchResults[111]->setEnvelope([
            "from" => "dev@conjoon.org", "to" => "devrec@conjoon.org", "message-id" => $messageIds[111]
        ]);
        $fetchResults[111]->setHeaders("ContentType", "Content-Type=text/html;charset=UTF-8");
        $fetchResults[111]->setHeaders("References", "References: " . $references[111]);

        $imapStub->shouldReceive("fetch")->with(
            "INBOX",
            Mockery::any(),
            Mockery::type("array")
        )->andReturn(
            $fetchResults
        );

        $client = $this->createClient();

        $messageItemList = $client->getMessageItemList(
            $this->createFolderKey(
                $account->getId(),
                "INBOX"
            ),
            new MessageItemListResourceQuery(new ParameterBag(
                ["start" => 0, "limit" => 1, "attributes" => ["from" => true, "references" => true]]
            ))
        );

        $this->assertEquals([
            "id" => $messageItemList[0]->getMessageKey()->getId(),
            "mailFolderId" => $messageItemList[0]->getMessageKey()->getMailFolderId(),
            "mailAccountId" => $messageItemList[0]->getMessageKey()->getMailAccountId(),
            "references" => $references[111],
            "from" => ["name" => "dev@conjoon.org", "address" => "dev@conjoon.org"]
        ], $messageItemList[0]->toJson());
    }


    /**
     * Message Item Test with id specified in search options
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageItemListWidthIdSpecified()
    {

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = Mockery::mock("overload:" . Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive("search")->with(
            "INBOX",
            Mockery::on(function ($searchQuery) {
                return $searchQuery instanceof Horde_Imap_Client_Search_Query &&
                    $searchQuery->__toString() === "UID 34";
            }),
            ["sort" => [Horde_Imap_Client::SORT_REVERSE, Horde_Imap_Client::SORT_DATE]]
        )->andReturn(["match" => new Horde_Imap_Client_Ids([34])]);

        $fetchResults = new Horde_Imap_Client_Fetch_Results();

        $fetchResults[34] = new Horde_Imap_Client_Data_Fetch();
        $fetchResults[34]->setUid(34);

        $fetchResults[34]->setEnvelope(["from" => "dev@conjoon.org", "to" => "devrec@conjoon.org"]);
        $fetchResults[34]->setHeaders("ContentType", "Content-Type=text/html;charset=UTF-8");
        $fetchResults[34]->setHeaders("References", "References: <foo>");


        $imapStub->shouldReceive("fetch")->with(
            "INBOX",
            Mockery::any(),
            Mockery::type("array")
        )->andReturn(
            $fetchResults
        );

        $client = $this->createClient();

        $messageItemList = $client->getMessageItemList(
            $this->createFolderKey(
                $account->getId(),
                "INBOX"
            ),
            new MessageItemListResourceQuery(new ParameterBag(
                ["filter" => [["property" => "id", "operator" => "in", "value" => [34]]]]
            ))
        );


        $this->assertInstanceOf(MessageItemList::class, $messageItemList);

        $this->assertSame(1, count($messageItemList));

        $this->assertInstanceOf(ListMessageItem::Class, $messageItemList[0]);

        $this->assertSame("utf-8", $messageItemList[0]->getCharset());

        $this->assertSame("34", $messageItemList[0]->getMessageKey()->getId());
        $this->assertSame("INBOX", $messageItemList[0]->getMessageKey()->getMailFolderId());
        $this->assertEquals(
            ["name" => "dev@conjoon.org", "address" => "dev@conjoon.org"],
            $messageItemList[0]->getFrom()->toJson()
        );
        $this->assertEquals(
            [["name" => "devrec@conjoon.org", "address" => "devrec@conjoon.org"]],
            $messageItemList[0]->getTo()->toJson()
        );

        $this->assertSame("<foo>", $messageItemList[0]->getReferences());
    }


    /**
     * Single messageItem Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageItem()
    {


        $client = $this->createClient();

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = Mockery::mock("overload:" . Horde_Imap_Client_Socket::class);

        $fetchResults = new Horde_Imap_Client_Fetch_Results();
        $fetchResults[16] = new Horde_Imap_Client_Data_Fetch();
        $fetchResults[16]->setUid("16");
        $fetchResults[16]->setSize("1600");


        $attach = new Horde_Mime_Part();
        $attach->setDisposition("attachment");
        $fetchResults[16]->setStructure($attach);


        $imapStub->shouldReceive("fetch")->with(
            "INBOX",
            Mockery::any(),
            Mockery::type("array")
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
    public function testGetMessageItemDraft()
    {


        $client = $this->createClient();

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = Mockery::mock("overload:" . Horde_Imap_Client_Socket::class);

        $fetchResults = new Horde_Imap_Client_Fetch_Results();
        $fetchResults[16] = new Horde_Imap_Client_Data_Fetch();
        $fetchResults[16]->setUid("16");

        $cc = new MailAddressList();
        $cc[] = new MailAddress("test@dot", "test@dot");

        $bcc = new MailAddressList();
        $bcc[] = new MailAddress("test2@dot", "test2@dot");

        $replyTo = new MailAddress("test@replyto", "test@replyto");

        $messageId = "kjgjkggjkkgjkgj";

        $fetchResults[16]->setEnvelope([
            "cc" => $cc[0]->getAddress(),
            "bcc" => $bcc[0]->getAddress(),
            "reply-to" => $replyTo->getAddress(),
            "message-id" => $messageId
        ]);

        $imapStub->shouldReceive("fetch")->with(
            "INBOX",
            Mockery::any(),
            Mockery::type("array")
        )->andReturn($fetchResults);

        $item = $client->getMessageItemDraft($this->createMessageKey($account->getId(), "INBOX", "16"));

        $this->assertInstanceOf(MessageItemDraft::class, $item);
        $this->assertEquals($cc, $item->getCc());
        $this->assertEquals($bcc, $item->getBcc());
        $this->assertEquals($replyTo, $item->getReplyTo());
        $this->assertSame($messageId, $item->getMessageId());
    }


    /**
     * Single MessageBody Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageBody()
    {

        $client = $this->createClient();

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = Mockery::mock("overload:" . Horde_Imap_Client_Socket::class);

        $fetchResults = new Horde_Imap_Client_Fetch_Results();
        $fetchResults[16] = new Horde_Imap_Client_Data_Fetch();
        $fetchResults[16]->setUid("16");

        $imapStub->shouldReceive("fetch")->with(
            "INBOX",
            Mockery::any(),
            Mockery::type("array")
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
    public function testGetTotalMessageCount()
    {
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = Mockery::mock("overload:" . Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive("search")->with("INBOX", Mockery::any(), [])
            ->andReturn(["match" => new Horde_Imap_Client_Ids([111, 222, 333])]);

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
    public function testGetTotalUnreadCount()
    {
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = Mockery::mock("overload:" . Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive("status")->with("INBOX", Horde_Imap_Client::STATUS_UNSEEN)
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
    public function testGetMailFolderList()
    {

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = Mockery::mock("overload:" . Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive("listMailboxes")->with(
            "*",
            Horde_Imap_Client::MBOX_ALL,
            ["attributes" => true]
        )->andReturn([
            "INBOX" => ["delimiter" => ".", "attributes" => []],
            "INBOX.Folder" => ["delimiter" => ":", "attributes" => ["\\noselect"]]
        ]);

        $imapStub->shouldReceive("status")->with(
            "INBOX",
            Horde_Imap_Client::STATUS_UNSEEN
        )->andReturn(["unseen" => 30]);

        $imapStub->shouldNotReceive("status")->with(
            "INBOX.Folder",
            Horde_Imap_Client::STATUS_UNSEEN
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
     * Tests setFlags
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSetFlags()
    {
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $imapStub = Mockery::mock("overload:" . Horde_Imap_Client_Socket::class);

        $messageItemId = "123";
        $mailFolderId = "INBOX";

        $imapStub->shouldReceive("store")->with(
            $mailFolderId,
            [
                "ids" => new Horde_Imap_Client_Ids([$messageItemId]),
                "add" => ["\\Seen"],
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
    public function testCreateMessageBodyDraftHasMessageKey()
    {

        $this->expectException(ImapClientException::class);

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId = "INBOX";
        $folderKey = new FolderKey($account, $mailFolderId);
        $client = $this->createClient();
        $client->createMessageBodyDraft(
            $folderKey,
            new MessageBodyDraft(new MessageKey("a", "b", "c"))
        );
    }


    /**
     * Tests createMessageBodyDraft
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCreateMessageBodyDraft()
    {

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId = "INBOX";
        $folderKey = new FolderKey($account, $mailFolderId);
        $messageItemId = "989786";

        $messageBodyDraft = new MessageBodyDraft();
        $htmlPart = new MessagePart("foo", "UTF-8", "text/html");
        $plainPart = new MessagePart("bar", "UTF-8", "text/plain");
        $messageBodyDraft->setTextHtml($htmlPart);
        $messageBodyDraft->setTextPlain($plainPart);

        $imapStub = Mockery::mock("overload:" . Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive("append")->with(
            $folderKey->getId(),
            [["data" => "__HEADER__\n\nFULL_TXT_MSG"]]
        )->andReturn(new Horde_Imap_Client_Ids([$messageItemId]));

        $imapStub->shouldReceive("store")->with(
            $mailFolderId,
            [
                "ids" => new Horde_Imap_Client_Ids([$messageItemId]),
                "add" => ["\\Draft"]
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
    public function testCreateMessageBodyDraftHasNoMessageKey()
    {

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
    public function testUpdateMessageBodyDraft()
    {

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId = "INBOX";
        $messageItemId = "989786";
        $createdId = "4643473743";
        $messageKey = new MessageKey($account, $mailFolderId, $messageItemId);

        $messageBodyDraft = new MessageBodyDraft($messageKey);
        $htmlPart = new MessagePart("foo", "UTF-8", "text/html");
        $plainPart = new MessagePart("bar", "UTF-8", "text/plain");
        $messageBodyDraft->setTextHtml($htmlPart);
        $messageBodyDraft->setTextPlain($plainPart);

        $imapStub = Mockery::mock("overload:" . Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive("append")->with(
            $messageKey->getMailFolderId(),
            [["data" => "__HEADER__\n\nFETCHED\n\nFULL_TXT_MSG"]]
        )->andReturn(new Horde_Imap_Client_Ids([$createdId]));


        $imapStub->shouldReceive("store")->with(
            $mailFolderId,
            [
                "ids" => new Horde_Imap_Client_Ids([$createdId]),
                "add" => ["\\Draft"]
            ]
        );

        $rangeList = new Horde_Imap_Client_Ids();
        $rangeList->add($messageItemId);

        $fetchResult = [];
        $fetchResult[$messageKey->getId()] = new class () {
            /**
             * @return string
             * @noinspection PhpUnused
             */
            public function getFullMsg(): string
            {
                return "FETCHED";
            }
        };
        $imapStub->shouldReceive("fetch")
            ->with(
                $messageKey->getMailFolderId(),
                Mockery::any(),
                ["ids" => $rangeList]
            )
            ->andReturn($fetchResult);

        $imapStub->shouldReceive("expunge")
            ->with(
                $messageKey->getMailFolderId(),
                ["delete" => true, "ids" => $rangeList, "list" => true]
            )->andReturn([]);


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
    public function testUpdateMessageDraft()
    {

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId = "INBOX";
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
            "date" => new DateTime("@1234566"),
            "replyTo" => new MailAddress("test@foobar.com", "test")
        ]);


        $rangeList = new Horde_Imap_Client_Ids();
        $rangeList->add($messageKey->getId());

        $resultList = new Horde_Imap_Client_Ids();
        $resultList->add($resultMessageKey->getId());

        $imapStub = Mockery::mock("overload:" . Horde_Imap_Client_Socket::class);

        $fetchResult = [];
        $fetchResult[$messageKey->getId()] = new class () {
            /**
             * @return string
             * @noinspection PhpUnused
             */
            public function getFullMsg(): string
            {
                return "BODY";
            }
        };

        $imapStub->shouldReceive("fetch")
            ->with(
                $messageKey->getMailFolderId(),
                Mockery::any(),
                ["ids" => $rangeList]
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
                ["delete" => true, "ids" => $rangeList, "list" => true]
            )->andReturn([]);

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
    public function testGetMailerException()
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
        $this->assertInstanceOf(Horde_Mail_Transport::class, $mailer);

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
    public function testSendMessageDraftNoDraft()
    {

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId = "DRAFTS";
        $messageItemId = "989786";
        $messageKey = new MessageKey($account, $mailFolderId, $messageItemId);

        $client = $this->createClient();

        $rangeList = new Horde_Imap_Client_Ids();
        $rangeList->add($messageKey->getId());

        $imapStub = Mockery::mock("overload:" . Horde_Imap_Client_Socket::class);

        $fetchResult = [];
        $fetchResult[$messageKey->getId()] = new class () {
            /**
             *  constructor.
             */
            public function __construct()
            {
            }

            /**
             *
             * @noinspection PhpUnused
             */
            public function getFullMsg()
            {
            }

            /**
             * @return array
             */
            public function getFlags(): array
            {
                return [];
            }
        };


        $imapStub->shouldReceive("fetch")
            ->with(
                $messageKey->getMailFolderId(),
                Mockery::any(),
                ["ids" => $rangeList]
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
    public function testSendMessageDraftRegular()
    {
        $this->sendMessageDraftTest();
    }


    /**
     * Tests sendMessageDraft
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSendMessageDraftNoDraftInfo()
    {
        $this->sendMessageDraftTest("NO_DRAFTINFO");
    }


    /**
     * Tests sendMessageDraft
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSendMessageDraftDraftInfoInvalid()
    {
        $this->sendMessageDraftTest("DRAFTINFO_INVALID");
    }


    /**
     * Tests sendMessageDraft
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSendMessageDraftDraftInfoNoJson()
    {
        $this->sendMessageDraftTest("DRAFTINFO_NOJSON");
    }


    /**
     * Tests sendMessageDraft
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSendMessageDraftInvalidMailAccount()
    {
        $this->sendMessageDraftTest("INVALID_MAILACCOUNT");
    }


    /**
     * Tests moveMessage with keys not sharing same MailAccountId
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testMoveMessage()
    {
        $this->assertSame($this->funcForTestMove(), "");
    }


    /**
     * Tests moveMessage with exception thrown by client
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testMoveMessageException()
    {
        $this->assertSame($this->funcForTestMove("exception"), "exception");
    }


    /**
     * Tests moveMessage with no array returned by client
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testMoveMessageNoarray()
    {
        $this->assertSame($this->funcForTestMove("noarray"), "noarray");
    }


    /**
     * Tests moveMessage with keys not sharing same MailAccountId
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testMoveMessageDiffererentMailAccountId()
    {


        $client = $this->createClient();

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId = "DRAFTS";
        $messageItemId = "989786";
        $toMailFolderId = "SENT";

        $messageKey = new MessageKey($account, $mailFolderId, $messageItemId);
        $folderKey = new FolderKey("zut", $toMailFolderId);

        $this->expectException(ImapClientException::class);
        $client->moveMessage($messageKey, $folderKey);
    }


    /**
     * Tests moveMessage with same mailFolderIds
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testMoveMessageSameMailFolderIds()
    {


        $client = $this->createClient();

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId = "DRAFTS";
        $messageItemId = "989786";
        $toMailFolderId = $mailFolderId;

        $messageKey = new MessageKey($account, $mailFolderId, $messageItemId);
        $folderKey = new FolderKey($account, $toMailFolderId);

        $res = $client->moveMessage($messageKey, $folderKey);

        $this->assertSame($res, $messageKey);
    }


    /**
     * Tests deleteMessage / okay
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDeleteMessageOkay()
    {
        $this->deleteMessageTest(true);
    }


    /**
     * Tests deleteMessage / failed
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDeleteMessageFailed()
    {
        $this->deleteMessageTest(false);
    }


    /**
     * Tests deleteMessage / exception
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDeleteMessageException()
    {
        $this->deleteMessageTest("exception");
    }


    /**
     * test getTextContent
     * @throws ReflectionException
     */
    public function testGetTextContentReturnsDefaultNoMessageData()
    {
        $client = $this->getMockBuilder(HordeClient::class)
                       ->disableOriginalConstructor()
                       ->getMock();

        $reflection = new ReflectionClass($client);
        $getTextContent = $reflection->getMethod("getTextContent");
        $getTextContent->setAccessible(true);

        $this->assertEquals(
            ["content" => "", "charset" => ""],
            $getTextContent->invokeArgs($client, ["value", "value", null, "value"])
        );
    }


    /**
     * @throws ReflectionException
     */
    public function testQueryItems()
    {
        $options = ["filter" => [[]]];

        $key = new FolderKey("dev", "INBOX");

        $search = new Horde_Imap_Client_Search_Query();

        $socket = $this->getMockBuilder(Horde_Imap_Client_Socket::class)
                        ->disableOriginalConstructor()
                        ->onlyMethods(["search"])
                        ->getMock();

        $client = $this->getMockBuilder(HordeClient::class)
                       ->disableOriginalConstructor()
                       ->getMock();

        $socket->expects($this->once())
               ->method("search")
               ->with(
                   $key->getId(),
                   $search,
                   $this->anything()
               )
               ->willReturn([]);

        $client->expects($this->once())
               ->method("getSearchQueryFromFilter")
               ->with($options["filter"])
               ->willReturn($search);

        $reflection = new ReflectionClass(HordeClient::class);
        $queryItems = $reflection->getMethod("queryItems");
        $queryItems->setAccessible(true);

        $queryItems->invokeArgs($client, [$socket, $key, $options]);
    }

// -------------------------------
//  Helper
// -------------------------------


    /**
     * Helper for testing deleteMessage()
     *
     * @param mixed $testType true, false or "exception"
     */
    protected function deleteMessageTest($testType)
    {

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId = "INBOX";
        $messageItemId = "989786";
        $messageKey = new MessageKey($account, $mailFolderId, $messageItemId);

        $rangeList = new Horde_Imap_Client_Ids();
        $rangeList->add($messageKey->getId());

        $imapStub = Mockery::mock("overload:" . Horde_Imap_Client_Socket::class);

        $returnValue = $testType === true ? ["someunrelatedvar"] : [];

        $op = $imapStub->shouldReceive("expunge")
            ->with(
                $mailFolderId,
                ["delete" => true, "ids" => $rangeList, "list" => true]
            );

        if ($testType === "exception") {
            $op->andThrow(new Exception());
        } else {
            $op->andReturn($returnValue);
        }

        $client = $this->createClient();

        try {
            $res = $client->deleteMessage($messageKey);
        } catch (ImapClientException $exc) {
            if ($testType === "exception") {
                $this->assertTrue(true);
                return;
            }
        }

        if ($testType === "exception") {
            // if we are here, no exception was thrown
            $this->assertTrue(false);
        }

        $resultValue = $testType === true;

        $this->assertSame($resultValue, $res);
    }


    /**
     * Helper function for sending a MessageDraft
     *
     * @param string $testType The test to perform.
     * Valid switches: "" -> regular test, with X-CN-DRAFT-INFO set
     * "NO_DRAFTINFO" -> with NO X-CN-DRAFT-INFO set
     * "DRAFTINFO_INVALID" -> parsing the header X-CN-DRAFT-INFO failed
     * "DRAFTINFO_NOJSON" -> the parsed header does not contain a valid JSON string
     * "INVALID_MAILACCOUNT" -> X-CN-DRAFT-INFO contained invalid mail account id
     *
     */
    protected function sendMessageDraftTest(string $testType = "")
    {

        if (
            !in_array(
                $testType,
                [
                "",
                "NO_DRAFTINFO",
                "DRAFTINFO_INVALID",
                "INVALID_MAILACCOUNT",
                "DRAFTINFO_NOJSON"
                ]
            )
        ) {
            throw new RuntimeException("Unexpected invalid test configuration.");
        }

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId = "DRAFTS";
        $messageItemId = "989786";
        $messageKey = new MessageKey($account, $mailFolderId, $messageItemId);

        $client = $this->createClient();

        $rangeList = new Horde_Imap_Client_Ids();
        $rangeList->add($messageKey->getId());

        $imapStub = Mockery::mock("overload:" . Horde_Imap_Client_Socket::class);
        $mailer = $client->getMailer($account);
        $mimePartStub = Mockery::mock("overload:" . Horde_Mime_Part::class);
        $mimeHeadersStub = Mockery::mock("overload:" . Horde_Mime_Headers::class);
        $mimeMailStub = Mockery::mock("overload:" . Horde_Mime_Mail::class);


        $headerElement = Mockery::mock("overload:" . Horde_Mime_Headers_Element::class);

        $headers = new  $mimeHeadersStub();
        $basePart = null;
        $rawMsg = "RAWMESSAGE";
        $fetchResult = [];
        $fetchResult[$messageKey->getId()] = new class ($rawMsg) {

            /**
             * @var string
             */
            protected string $rawMsg;

            /**
             *  constructor.
             * @param $rawMsg
             */
            public function __construct($rawMsg)
            {
                $this->rawMsg = $rawMsg;
            }

            /**
             * @return string
             *
             * @noinspection PhpUnused
             */
            public function getFullMsg(): string
            {
                return $this->rawMsg;
            }

            /**
             * @return array
             */
            public function getFlags(): array
            {
                return [Horde_Imap_Client::FLAG_DRAFT];
            }
        };


        $imapStub->shouldReceive("fetch")
            ->with(
                $messageKey->getMailFolderId(),
                Mockery::any(),
                ["ids" => $rangeList]
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


        switch ($testType) {
            case "DRAFTINFO_INVALID":
                $value = "NO!";
                break;

            case "DRAFTINFO_NOJSON":
                $value = base64_encode("{\"abc\" \"def\"}");
                break;
            case "INVALID_MAILACCOUNT":
                $value = base64_encode(json_encode(["abc", "foo", "bar"]));
                break;
            default:
                $value = base64_encode(json_encode([$account->getId(), "foo", "bar"]));
                break;
        }


        $headerElement->shouldReceive("__get")
            ->with("value_single")
            ->andReturn($value);

        if ($testType === "NO_DRAFTINFO") {
            $headers->shouldReceive("getHeader")
                ->with("X-CN-DRAFT-INFO")
                ->andReturn(null);
        } else {
            $headers->shouldReceive("getHeader")
                ->with("X-CN-DRAFT-INFO")
                ->andReturn(
                    new Horde_Mime_Headers_Element_Single(
                        "X-CN-DRAFT-INFO",
                        $value
                    )
                );
        }


        $headers->shouldReceive("removeHeader")
            ->with("X-CN-DRAFT-INFO");


        if (in_array($testType, ["NO_DRAFTINFO", "DRAFTINFO_INVALID", "DRAFTINFO_NOJSON", "INVALID_MAILACCOUNT"])) {
            $imapStub->shouldNotReceive("store");
        } else {
            $imapStub->shouldReceive("store")->with(
                "foo",
                [
                    "ids" => new Horde_Imap_Client_Ids(["bar"]),
                    "add" => ["\\Answered"]
                ]
            )->andReturn(true);
        }


        $mimeMailStub->shouldReceive("send")->with($mailer);

        $res = $client->sendMessageDraft($messageKey);

        $this->assertTrue($res);
    }


    /**
     * helper for moveMessage tests
     */
    protected function funcForTestMove($type = "")
    {


        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId = "DRAFTS";
        $messageItemId = "989786";
        $toMailFolderId = "SENT";
        $newMessageItemId = "24";

        $messageKey = new MessageKey($account, $mailFolderId, $messageItemId);
        $folderKey = new FolderKey($account, $toMailFolderId);
        $cmpMessageKey = new MessageKey($account, $toMailFolderId, $newMessageItemId);

        $client = $this->createClient();

        $rangeList = new Horde_Imap_Client_Ids();
        $rangeList->add($messageKey->getId());

        $imapStub = Mockery::mock("overload:" . Horde_Imap_Client_Socket::class);
        $proc = $imapStub->shouldReceive("copy")
            ->with(
                $mailFolderId,
                $toMailFolderId,
                ["ids" => $rangeList, "move" => true, "force_map" => true]
            );

        if ($type === "exception") {
            $this->expectException(ImapClientException::class);
            $proc->andThrow(new Exception("foo"));
            $client->moveMessage($messageKey, $folderKey);
        } elseif ($type === "noarray") {
            $proc->andReturn(false);
            $this->expectException(ImapClientException::class);
            $client->moveMessage($messageKey, $folderKey);
        } else {
            $proc->andReturn([$messageItemId => $newMessageItemId]);
            $res = $client->moveMessage($messageKey, $folderKey);
            $this->assertEquals($res, $cmpMessageKey);
        }

        return $type;
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


    /**
     * @param $mid
     * @param $fid
     * @param $id
     * @return MessageKey
     */
    protected function createMessageKey($mid, $fid, $id): MessageKey
    {
        return new MessageKey($mid, $fid, $id);
    }
}
