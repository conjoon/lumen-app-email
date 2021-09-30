<?php
/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2019-2020 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

use
    Conjoon\Mail\Client\Service\MessageItemService,
    Conjoon\Mail\Client\Service\DefaultMessageItemService,
    Conjoon\Mail\Client\Service\ServiceException,
    Conjoon\Mail\Client\Message\MessagePart,
    Conjoon\Mail\Client\Message\AbstractMessageItem,
    Conjoon\Mail\Client\Message\MessageItem,
    Conjoon\Mail\Client\Message\MessageItemDraft,
    Conjoon\Mail\Client\Message\MessageBody,
    Conjoon\Mail\Client\Message\MessageBodyDraft,
    Conjoon\Mail\Client\MailClientException,
    Conjoon\Mail\Client\Message\ListMessageItem,
    Conjoon\Mail\Client\Data\CompoundKey\MessageKey,
    Conjoon\Mail\Client\Data\CompoundKey\FolderKey,
    Conjoon\Mail\Client\Data\MailAddress,
    Conjoon\Mail\Client\Data\MailAddressList,
    Conjoon\Mail\Client\Message\MessageItemList,
    Conjoon\Mail\Client\Message\Flag\FlagList,
    Conjoon\Mail\Client\Message\Flag\SeenFlag,
    Conjoon\Mail\Client\MailClient,
    Conjoon\Mail\Client\Reader\ReadableMessagePartContentProcessor,
    Conjoon\Mail\Client\Writer\WritableMessagePartContentProcessor,
    Conjoon\Mail\Client\Message\Text\PreviewTextProcessor,
    Conjoon\Text\CharsetConverter,
    Conjoon\Mail\Client\Reader\DefaultPlainReadableStrategy,
    Conjoon\Mail\Client\Reader\PurifiedHtmlStrategy,
    Conjoon\Mail\Client\Writer\DefaultPlainWritableStrategy,
    Conjoon\Mail\Client\Writer\DefaultHtmlWritableStrategy,
    Conjoon\Mail\Client\Message\Text\MessageItemFieldsProcessor;


/**
 * Class DefaultMessageItemServiceTest
 *
 */
class DefaultMessageItemServiceTest extends TestCase {

    use TestTrait;


// ------------------
//     Tests
// ------------------
    /**
     * Tests constructor.
     *
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInstance() {

        $rP = $this->getReadableMessagePartContentProcessor();
        $wP = $this->getWritableMessagePartContentProcessor();
        $pP = $this->getPreviewTextProcessor();
        $miP = $this->getMessageItemFieldsProcessor();

        $service = $this->createService($miP, $rP, $wP, $pP);

        $this->assertInstanceOf(MessageItemService::class, $service);
        $this->assertInstanceOf(MailClient::class, $service->getMailClient());

        $this->assertSame($rP, $service->getReadableMessagePartContentProcessor());
        $this->assertSame($wP, $service->getWritableMessagePartContentProcessor());
        $this->assertSame($pP, $service->getPreviewTextProcessor());
        $this->assertSame($miP, $service->getMessageItemFieldsProcessor());

    }


    /**
     * Multiple Message Item Test.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageItemList() {

        $service = $this->createService();

        $clientStub = $service->getMailClient();

        $mailFolderId = "INBOX";
        $folderKey = $this->createFolderKey(null, $mailFolderId);

        $options = ["start" => 0, "limit" => 2];

        $messageItemListMock = new MessageItemList;
        $messageItemListMock[] = new ListMessageItem($this->createMessageKey(null, $mailFolderId, 1),
            null, new MessagePart("foo1", "UTF-8", "text/html"));
        $messageItemListMock[] = new ListMessageItem($this->createMessageKey(null, $mailFolderId, 2),
            null, new MessagePart("foo2", "UTF-8", "text/plain"));
        $messageItemListMock[] = new ListMessageItem($this->createMessageKey(null, $mailFolderId, 3),
            null, new MessagePart("foo3", "UTF-8", "text/html"));

        $clientStub->method('getMessageItemList')
                   ->with($folderKey, $options)
                    ->willReturn($messageItemListMock);

        $results = $service->getMessageItemList($folderKey, $options);

        foreach ($results as $resultItem) {
            $this->assertInstanceOf(ListMessageItem::Class, $resultItem);
            $this->assertSame("preview", $resultItem->getMessagePart()->getContents());
        }
    }


    /**
     * Single MessageItem Test
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageItem() {

        $service = $this->createService();

        $mailFolderId = "INBOX";
        $messageItemId = "8977";
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $messageKey = $this->createMessageKey($account->getId(), $mailFolderId, $messageItemId);


        $clientStub = $service->getMailClient();
        $clientStub->method('getMessageItem')
            ->with($messageKey)
            ->willReturn(
                $this->buildTestMessageItem($account->getId(), $mailFolderId, $messageItemId)
            );


        $item = $service->getMessageItem($messageKey);

        $cmpItem = $this->buildTestMessageItem($account->getId(), $mailFolderId, $messageItemId);

        $cmpItem->setDate($item->getDate());

        $this->assertSame($messageItemId, $item->getMessageKey()->getId());

        $cmpJson   = $cmpItem->toJson();
        $itemJson = $item->toJson();

        $this->assertSame("MessageItemFieldsProcessor", $itemJson["subject"]);

        $cmpJson["subject"] = $itemJson["subject"];

        $this->assertSame($cmpJson, $itemJson);
    }


    /**
     * Single MessageItemDraft Test
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageItemDraft() {

        $service = $this->createService();

        $mailFolderId = "INBOX";
        $messageItemId = "8977";
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $messageKey = $this->createMessageKey($account->getId(), $mailFolderId, $messageItemId);

        $clientStub = $service->getMailClient();
        $clientStub->method('getMessageItemDraft')
            ->with($messageKey)
            ->willReturn(
                $this->buildTestMessageItem($account->getId(), $mailFolderId, $messageItemId, true)
            );


        $item = $service->getMessageItemDraft($messageKey);

        $cmpItem = $this->buildTestMessageItem($account->getId(), $mailFolderId, $messageItemId, true);

        $cmpItem->setDate($item->getDate());

        $this->assertSame($messageItemId, $item->getMessageKey()->getId());

        $cmpJson   = $cmpItem->toJson();
        $itemJson = $item->toJson();

        $this->assertSame("MessageItemFieldsProcessor", $itemJson["subject"]);

        $cmpJson["subject"] = $itemJson["subject"];

        $this->assertSame($cmpJson, $itemJson);
    }


    /**
     * Single MessageBody Test
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageBodyFor_plain() {

        $this->getMessageBodyForTestHelper("plain");

    }


    /**
     * Single MessageBody Test
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageBodyFor_html() {

        $this->getMessageBodyForTestHelper("", "html");

    }


    /**
     * Single MessageBody Test
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageBodyFor_both() {

        $this->getMessageBodyForTestHelper("plain", "html");

    }


    /**
     * Tests getTotalMessageCount()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetTotalMessageCount() {

        $service = $this->createService();

        $mailFolderId = "INBOX";
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $folderKey = $this->createFolderKey($account, $mailFolderId);

        $clientStub = $service->getMailClient();
        $clientStub->method('getTotalMessageCount')
            ->with($folderKey)
            ->willReturn(
                300
            );

        $this->assertSame(300, $service->getTotalMessageCount($folderKey));
    }


    /**
     * Tests getUnreadMessageCount()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUnreadMessageCount() {
        $service = $this->createService();

        $mailFolderId = "INBOX";
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $folderKey = $this->createFolderKey($account, $mailFolderId);

        $clientStub = $service->getMailClient();
        $clientStub->method('getUnreadMessageCount')
            ->with($folderKey)
            ->willReturn(311);

        $this->assertSame(311, $service->getUnreadMessageCount($folderKey));
    }


    /**
     * Tests setFlags()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSetFlags() {
        $service = $this->createService();

        $mailFolderId = "INBOX";
        $id           = "123";
        $account      = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $messageKey = $this->createMessageKey($account, $mailFolderId, $id);

        $flagList = new FlagList();
        $flagList[] = new SeenFlag(true);

        $clientStub = $service->getMailClient();
        $clientStub->method('setFlags')
            ->with($messageKey, $flagList)
            ->willReturn(false);

        $this->assertSame(false, $service->setFlags($messageKey, $flagList));
    }


    /**
     * Tests testCreateMessageBodyDraft_exception()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCreateMessageBodyDraft_exception() {
        $service = $this->createService();

        $this->expectException(ServiceException::class);

        $mailFolderId = "INBOX";
        $id           = "123";
        $account      = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $folderKey  = $this->createFolderKey($account, $mailFolderId);
        $messageBodyDraft = new MessageBodyDraft($this->createMessageKey($account, $mailFolderId, $id));

        $service->createMessageBodyDraft($folderKey, $messageBodyDraft);
    }


    /**
     * Tests createMessageBodyDraft()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCreateMessageBodyDraft() {

        $mailFolderId = "INBOX";
        $id           = "123";
        $account      = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $folderKey    = $this->createFolderKey($account, $mailFolderId);

        $clientMockedMethod = function($folderKey, $messageBodyDraft) use ($account, $mailFolderId, $id) {
            return $messageBodyDraft->setMessageKey($this->createMessageKey($account, $mailFolderId, $id));
        };


        $service    = $this->createService();
        $clientStub = $service->getMailClient();
        $messageBodyDraft = new MessageBodyDraft();
        $clientStub->method('createMessageBodyDraft')->with($folderKey, $messageBodyDraft)->will($this->returnCallback($clientMockedMethod));
        $messageBodyDraft->setTextHtml(new MessagePart("a", "UTF-8", "text/html"));
        $messageBody = $service->createMessageBodyDraft($folderKey, $messageBodyDraft);
        $this->assertSame("WRITTENtext/htmla", $messageBody->getTextHtml()->getContents());
        $this->assertSame("WRITTENtext/plaina", $messageBody->getTextPlain()->getContents());
        $this->assertNotSame($messageBodyDraft, $messageBody);


        $service    = $this->createService();
        $clientStub = $service->getMailClient();
        $messageBodyDraft = new MessageBodyDraft();
        $clientStub->method('createMessageBodyDraft')->with($folderKey, $messageBodyDraft)->will($this->returnCallback($clientMockedMethod));
        $messageBodyDraft->setTextPlain(new MessagePart("a", "UTF-8", "text/plain"));
        $messageBody = $service->createMessageBodyDraft($folderKey, $messageBodyDraft);
        $this->assertSame("WRITTENtext/htmla", $messageBody->getTextHtml()->getContents());
        $this->assertSame("WRITTENtext/plaina", $messageBody->getTextPlain()->getContents());
        $this->assertNotSame($messageBodyDraft, $messageBody);


        $service    = $this->createService();
        $clientStub = $service->getMailClient();
        $messageBodyDraft = new MessageBodyDraft();
        $clientStub->method('createMessageBodyDraft')->with($folderKey, $messageBodyDraft)->will($this->returnCallback($clientMockedMethod));
        $messageBodyDraft->setTextPlain(new MessagePart("a", "UTF-8", "text/plain"));
        $messageBodyDraft->setTextHtml(new MessagePart("b", "UTF-8", "text/html"));
        $messageBody = $service->createMessageBodyDraft($folderKey, $messageBodyDraft);
        $this->assertSame("WRITTENtext/plaina", $messageBody->getTextPlain()->getContents());
        $this->assertSame("WRITTENtext/htmlb", $messageBody->getTextHtml()->getContents());
        $this->assertNotSame($messageBodyDraft, $messageBody);


        $service    = $this->createService();
        $clientStub = $service->getMailClient();
        $messageBodyDraft = new MessageBodyDraft();
        $clientStub->method('createMessageBodyDraft')->with($folderKey, $messageBodyDraft)->will($this->returnCallback($clientMockedMethod));
        $messageBody = $service->createMessageBodyDraft($folderKey, $messageBodyDraft);
        $this->assertSame("WRITTENtext/plain", $messageBody->getTextPlain()->getContents());
        $this->assertSame("WRITTENtext/html", $messageBody->getTextHtml()->getContents());
        $this->assertNotSame($messageBodyDraft, $messageBody);
    }


    /**
     * Tests testCreateMessageBody_returning_null()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCreateMessageBody_returning_null() {
        $service = $this->createService();

        $account      = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId = "INBOX";
        $folderKey    = $this->createFolderKey($account, $mailFolderId);

        $clientStub = $service->getMailClient();

        $messageBodyDraft = new MessageBodyDraft();
        $messageBodyDraft->setTextHtml(new MessagePart("a", "UTF-8", "text/html"));

        $clientStub->method('createMessageBodyDraft')
            ->with($folderKey, $messageBodyDraft)
            ->willThrowException(new MailClientException);

        $result = $service->createMessageBodyDraft($folderKey, $messageBodyDraft);
        $this->assertNull($result);
    }

    /**
     * Tests updateMessageDraft()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUpdateMessageDraft() {
        $service = $this->createService();

        $mailFolderId = "INBOX";
        $id           = "123";
        $newId        = "1234";
        $account      = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $messageKey = $this->createMessageKey($account, $mailFolderId, $id);
        $newKey     = $this->createMessageKey($account, $mailFolderId, $newId);

        $messageItemDraft = new MessageItemDraft($messageKey);
        $transformDraft   = new MessageItemDraft($newKey);

        $clientStub = $service->getMailClient();

        $clientStub->method('updateMessageDraft')
            ->with($messageItemDraft)
            ->willReturn($transformDraft);

        $res = $service->updateMessageDraft($messageItemDraft);
        $this->assertSame($newKey, $res->getMessageKey());
    }


    /**
     * Tests testUpdateMessageDraft_returning_null()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUpdateMessageDraft_returning_null() {

        $service = $this->createService();

        $account      = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId = "INBOX";
        $id           = "123";
        $messageKey    = $this->createMessageKey($account, $mailFolderId, $id);

        $transformDraft = new MessageItemDraft($messageKey);

        $clientStub = $service->getMailClient();

        $clientStub->method('updateMessageDraft')
                   ->with($transformDraft)
                   ->willThrowException(new MailClientException);

        $messageItemDraft = $service->updateMessageDraft($transformDraft);
        $this->assertNull($messageItemDraft);
    }


    /**
     * Tests testUpdateMessageBodyDraft_exception()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUpdateMessageBodyDraft_exception() {
        $service = $this->createService();

        $this->expectException(ServiceException::class);

        $messageBodyDraft = new MessageBodyDraft();

        $service->updateMessageBodyDraft($messageBodyDraft);
    }


    /**
     * Tests testUpdateMessageBodyDraft_returning_null()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUpdateMessageBodyDraft_returning_null() {

        $service = $this->createService();

        $account      = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId = "INBOX";
        $id           = "123";
        $messageKey    = $this->createMessageKey($account, $mailFolderId, $id);

        $messageBodyDraft = new MessageBodyDraft($messageKey);

        $clientStub = $service->getMailClient();

        $clientStub->method('updateMessageBodyDraft')
            ->with($messageBodyDraft)
            ->willThrowException(new MailClientException);

        $newDraft = $service->updateMessageBodyDraft($messageBodyDraft);
        $this->assertNull($newDraft);
    }


    /**
     * Tests updateMessageBodyDraft()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUpdateMessageBodyDraft() {

        $mailFolderId = "INBOX";
        $id           = "123";
        $newId        = "abc";
        $account      = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $getKey       = function() use($account, $mailFolderId, $id){return $this->createMessageKey($account, $mailFolderId, $id);};

        $clientMockedMethod = function($messageBodyDraft) use ($account, $mailFolderId, $newId) {
            return $messageBodyDraft->setMessageKey($this->createMessageKey($account, $mailFolderId, $newId));
        };

        $service    = $this->createService();
        $clientStub = $service->getMailClient();
        $messageBodyDraft = new MessageBodyDraft($getKey());
        $clientStub->method('updateMessageBodyDraft')->with($messageBodyDraft)->will($this->returnCallback($clientMockedMethod));
        $messageBodyDraft->setTextHtml(new MessagePart("a", "UTF-8", "text/html"));
        $messageBody = $service->updateMessageBodyDraft($messageBodyDraft);
        $this->assertSame("WRITTENtext/htmla", $messageBody->getTextHtml()->getContents());
        $this->assertSame("WRITTENtext/plaina", $messageBody->getTextPlain()->getContents());
        $this->assertNotSame($messageBodyDraft, $messageBody);


        $service    = $this->createService();
        $clientStub = $service->getMailClient();
        $messageBodyDraft = new MessageBodyDraft($getKey());
        $clientStub->method('updateMessageBodyDraft')->with($messageBodyDraft)->will($this->returnCallback($clientMockedMethod));
        $messageBodyDraft->setTextPlain(new MessagePart("a", "UTF-8", "text/plain"));
        $messageBody = $service->updateMessageBodyDraft($messageBodyDraft);
        $this->assertSame("WRITTENtext/htmla", $messageBody->getTextHtml()->getContents());
        $this->assertSame("WRITTENtext/plaina", $messageBody->getTextPlain()->getContents());
        $this->assertNotSame($messageBodyDraft, $messageBody);


        $service    = $this->createService();
        $clientStub = $service->getMailClient();
        $messageBodyDraft = new MessageBodyDraft($getKey());
        $clientStub->method('updateMessageBodyDraft')->with($messageBodyDraft)->will($this->returnCallback($clientMockedMethod));
        $messageBodyDraft->setTextPlain(new MessagePart("a", "UTF-8", "text/plain"));
        $messageBodyDraft->setTextHtml(new MessagePart("b", "UTF-8", "text/html"));
        $messageBody = $service->updateMessageBodyDraft($messageBodyDraft);
        $this->assertSame("WRITTENtext/plaina", $messageBody->getTextPlain()->getContents());
        $this->assertSame("WRITTENtext/htmlb", $messageBody->getTextHtml()->getContents());
        $this->assertNotSame($messageBodyDraft, $messageBody);


        $service    = $this->createService();
        $clientStub = $service->getMailClient();
        $messageBodyDraft = new MessageBodyDraft($getKey());
        $clientStub->method('updateMessageBodyDraft')->with($messageBodyDraft)->will($this->returnCallback($clientMockedMethod));
        $messageBody = $service->updateMessageBodyDraft($messageBodyDraft);
        $this->assertSame("WRITTENtext/plain", $messageBody->getTextPlain()->getContents());
        $this->assertSame("WRITTENtext/html", $messageBody->getTextHtml()->getContents());
        $this->assertNotSame($messageBodyDraft, $messageBody);
    }


    /**
     * Tests sendMessageDraft()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSendMessageDraft() {

        $mailFolderId = "INBOX";
        $id           = "123";
        $account      = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $messageKey   = $this->createMessageKey($account, $mailFolderId, $id);

        $testSend = function($expected) use ($messageKey) {

            $service    = $this->createService();
            $clientStub = $service->getMailClient();
            $clientStub->method('sendMessageDraft')->with($messageKey)->willReturn($expected);

            $this->assertSame($service->sendMessageDraft($messageKey), $expected);
        };

        $testSend(true);
        $testSend(false);
    }


    /**
     * Tests sendMessageDraft() /w exception
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSendMessageDraft_exception() {

        $mailFolderId = "INBOX";
        $id           = "123";
        $account      = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $messageKey   = $this->createMessageKey($account, $mailFolderId, $id);

        $service    = $this->createService();
        $clientStub = $service->getMailClient();
        $clientStub->method('sendMessageDraft')
                   ->with($messageKey)
                   ->willThrowException(new MailClientException);

        $this->assertFalse($service->sendMessageDraft($messageKey));
    }


    /**
     * Tests moveMessage()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testMoveMessage() {

        $mailFolderId = "INBOX";
        $id           = "123";
        $account      = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $messageKey   = $this->createMessageKey($account, $mailFolderId, $id);
        $folderKey    = new FolderKey($account, "foo");

        $expected = new MessageKey($account, "foo", "newid");

        $service    = $this->createService();
        $clientStub = $service->getMailClient();
        $clientStub->method('moveMessage')->with($messageKey, $folderKey)->willReturn($expected);

        $this->assertSame($service->moveMessage($messageKey, $folderKey), $expected);

    }


    /**
     * Tests moveMessage() /w exception
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testMoveMessage_exception() {

        $mailFolderId = "INBOX";
        $id           = "123";
        $account      = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $messageKey   = $this->createMessageKey($account, $mailFolderId, $id);
        $folderKey    = new FolderKey($account, "foo");

        $service    = $this->createService();
        $clientStub = $service->getMailClient();
        $clientStub->method('moveMessage')->with($messageKey, $folderKey)
                   ->willThrowException(new MailClientException("should not bubble"));

        $this->assertSame($service->moveMessage($messageKey, $folderKey), null);

    }


    /**
     * Tests getListMessageItem
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetListMessageItem() {

        $service = $this->createService();

        $clientStub = $service->getMailClient();

        $mailAccountId = "dev";
        $mailFolderId  = "INBOX";
        $messageItemId = "1234";
        $messageKey    = $this->createMessageKey($mailAccountId, $mailFolderId, $messageItemId);
        $folderKey     = $messageKey->getFolderKey();

        $messageItemListMock = new MessageItemList;
        $messageItemListMock[] = new ListMessageItem($messageKey,
            null, new MessagePart("preview", "UTF-8", "text/html"));

        $clientStub->method('getMessageItemList')
            ->with($folderKey, ["ids" => [$messageKey->getId()]])
            ->willReturn($messageItemListMock);

        $result = $service->getListMessageItem($messageKey);

        $this->assertInstanceOf(ListMessageItem::Class, $result);
        $this->assertSame("preview", $result->getMessagePart()->getContents());
    }


    /**
     * Tests deleteMessage()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDeleteMessage_okay() {
        $this->deleteMessageTest(true);
    }


    /**
     * Tests deleteMessage()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDeleteMessage_failed() {
        $this->deleteMessageTest(false);
    }


    /**
     * Tests deleteMessage()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDeleteMessage_exeption() {
        $this->deleteMessageTest("exception");
    }


// ------------------
//     Test Helper
// ------------------

    /**
     * @param $plain
     * @param $html
     */
    protected function getMessageBodyForTestHelper($plain ="", $html = "") {

        $service = $this->createService();

        $mailFolderId = "INBOX";
        $messageItemId = "8977";
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $messageKey = $this->createMessageKey($account, $mailFolderId, $messageItemId);

        $clientStub = $service->getMailClient();
        $clientStub->method('getMessageBody')
            ->with($messageKey)
            ->willReturn(
                $this->buildTestMessageBody($account->getId(), $mailFolderId, $messageKey->getId(), $plain, $html)
            );

        $body = $service->getMessageBody($messageKey);

        $this->assertSame($messageItemId, $body->getMessageKey()->getId());

        $this->assertTrue($body->getTextPlain() == (!!$plain));
        $this->assertTrue($body->getTextHtml() == (!!$html));


        if ($plain) {
            $this->assertSame("READtext/plainplain", $body->getTextPlain()->getContents());
        } else {
            $this->assertSame(null, $body->getTextPlain());
        }

        if ($html) {
            $this->assertSame("READtext/htmlhtml", $body->getTextHtml()->getContents());
        } else {
            $this->assertSame(null, $body->getTextHtml());
        }

    }


    /**
     * Helper for testDeleteMessage
     *
     * @param mixed $type bool|string=exception
     */
    public function deleteMessageTest($type) {

        $mailFolderId = "INBOX";
        $id           = "123";
        $account      = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $messageKey   = $this->createMessageKey($account, $mailFolderId, $id);

        $service    = $this->createService();
        $clientStub = $service->getMailClient();

        $op = $clientStub->method('deleteMessage')->with($messageKey);

        if ($type === "exception") {
            $op->willThrowException(new MailClientException);
            $expected = false;
        } else if (is_bool($type)) {
            $expected = $type;
            $op->willReturn($expected);
        } else {
            $this->fail("No valid type configured for test.");
        }

        $clientStub->method('deleteMessage')->with($messageKey)->willReturn($expected);

        $this->assertSame($service->deleteMessage($messageKey), $expected);
    }


    /**
     * @return FolderKey
     */
    protected function createFolderKey($mailAccountId = null, $id = "INBOX") {

        return new FolderKey(
            $mailAccountId ? $mailAccountId :  $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org"),
            $id
        );

    }

    /**
     * @return MessageKey
     */
    protected function createMessageKey($mailAccountId = null, $mailFolderId = "INBOX", $id) {

        return new MessageKey(
            $mailAccountId ? $mailAccountId :  $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org"),
            $mailFolderId,
            $id
        );

    }


    /**
     * Helper function for creating the client Mock.
     * @return mixed
     */
    protected function getMailClientMock() {
        return $this->getMockBuilder('Conjoon\Mail\Client\MailClient')
                    ->setMethods([
                        "getMessageItemList", "getMessageItem", "getListMessageItem", "getMessageItemDraft", "getMessageBody",
                        "getUnreadMessageCount", "getTotalMessageCount", "getMailFolderList",
                        "getFileAttachmentList", "setFlags", "createMessageBodyDraft",
                        "updateMessageDraft", "updateMessageBodyDraft", "sendMessageDraft", "moveMessage", "deleteMessage"])
                    ->disableOriginalConstructor()
                    ->getMock();
    }


    /**
     * Helper function for creating the service.
     * @return DefaultMessageItemService
     */
    protected function createService($messageItemFieldsProcessor = null,
                                     $readableMessagePartContentProcessor = null,
                                     $writableMessagePartContentProcessor = null,
                                     $previewTextProcessor = null) {
        return new DefaultMessageItemService(
            $this->getMailClientMock(),
            $messageItemFieldsProcessor ? $messageItemFieldsProcessor : $this->getMessageItemFieldsProcessor(),
            $readableMessagePartContentProcessor ? $readableMessagePartContentProcessor : $this->getReadableMessagePartContentProcessor(),
            $writableMessagePartContentProcessor ? $writableMessagePartContentProcessor : $this->getWritableMessagePartContentProcessor(),
            $previewTextProcessor ? $previewTextProcessor : $this->getPreviewTextProcessor()
        );
    }


    /**
     * @return ReadableMessagePartContentProcessor
     */
    protected function getReadableMessagePartContentProcessor() :ReadableMessagePartContentProcessor{
        return new class(
            new CharsetConverter(),
            new DefaultPlainReadableStrategy,
            new PurifiedHtmlStrategy
        ) extends ReadableMessagePartContentProcessor {
            public function process(MessagePart $messagePart, string $toCharset = "UTF-8") :MessagePart{
                $messagePart->setContents("READ" . $messagePart->getMimeType() . $messagePart->getContents(), "ISO-8859-1");
                return $messagePart;
            }
        };
    }

    /**
     * @return WritableMessagePartContentProcessor
     */
    protected function getWritableMessagePartContentProcessor() :WritableMessagePartContentProcessor{
        return new class(
            new CharsetConverter(),
            new DefaultPlainWritableStrategy,
            new DefaultHtmlWritableStrategy
        ) extends WritableMessagePartContentProcessor {
            public function process(MessagePart $messagePart, string $toCharset = "UTF-8") :MessagePart{
                $messagePart->setContents("WRITTEN" . $messagePart->getMimeType() . $messagePart->getContents(), "ISO-8859-1");
                return $messagePart;
            }
        };
    }


    /**
     * @return PreviewTextProcessor
     */
    protected function getPreviewTextProcessor() :PreviewTextProcessor{
        return new class implements PreviewTextProcessor {
            public function process(MessagePart $messagePart, string $toCharset = "UTF-8") :MessagePart{
                $messagePart->setContents("preview", "ISO-8859-1");
                return $messagePart;
            }
        };
    }


    /**
     * @return MessageItemFieldsProcessor
     */
    protected function getMessageItemFieldsProcessor() :MessageItemFieldsProcessor{
        return new class implements MessageItemFieldsProcessor {
            public function process(AbstractMessageItem $messageItem, string $toCharset = "UTF-8") :AbstractMessageItem{
                $messageItem->setSubject("MessageItemFieldsProcessor");
                return $messageItem;
            }
        };
    }


    /**
     * Helper function for creating Dummy MessageItem.
     *
     * @param $mailAccountId
     * @param $mailFolderId
     * @return MessageItem|MessageItemDraft
     * @throws Exception
     */
    protected function buildTestMessageItem($mailAccountId, $mailFolderId, $messageItemId = null, $isDraft = false) {
        $items = [];

        if ($messageItemId == null) {
            throw new \RuntimeExeption("Unexpected value for messageItemId.");
        }

        $messageKey = new MessageKey($mailAccountId, $mailFolderId, $messageItemId);

        $data = [
            "from" => new MailAddress("addr", "from"),
            "to" => new MailAddressList(),
            "size" => 100,
            "subject" => "subject",
            "date" => new \DateTime(),
            "answered" =>false,
            "draft" =>false,
            "flagged" => false,
            "recent" => false
        ];

        if ($isDraft === false) {
            $data["seen"]           = false;
            $data["hasAttachments"] = false;
            return new MessageItem($messageKey, $data);
        } else {
            $data["cc"]      = MailAddressList::fromJsonString(json_encode([["address" => "test@cc"]]));
            $data["bcc"]     = MailAddressList::fromJsonString(json_encode([["address" => "test@bcc"]]));
            $data["replyTo"] = MailAddress::fromJsonString(json_encode(["address" => "test@replyTo"]));
            return new MessageItemDraft($messageKey, $data);
        }


    }


    /**
     * Helper function for creating a MessageBody.
     * @param $mailAccountId
     * @param $mailFolderId
     * @param $messageItemId
     * @return array
     */
    protected function buildTestMessageBody($mailAccountId, $mailFolderId, $messageItemId, $plain = "", $html = "") {

        $messageKey = new MessageKey($mailAccountId, $mailFolderId, $messageItemId);

        $mb = new MessageBody($messageKey);

        if ($html) {
            $mb->setTextHtml(new MessagePart($html, "UTF-8", "text/html"));
        }

        if ($plain) {
            $mb->setTextPlain(new MessagePart($plain, "UTF-8", "text/plain"));
        }

        return $mb;

    }

}
