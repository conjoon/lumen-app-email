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

use
    Conjoon\Mail\Client\Service\MessageItemService,
    Conjoon\Mail\Client\Service\DefaultMessageItemService,
    Conjoon\Mail\Client\Message\MessagePart,
    Conjoon\Mail\Client\Message\AbstractMessageItem,
    Conjoon\Mail\Client\Message\MessageItem,
    Conjoon\Mail\Client\Message\MessageItemDraft,
    Conjoon\Mail\Client\Message\MessageBody,
    Conjoon\Mail\Client\MailClientException,
    Conjoon\Mail\Client\Message\ListMessageItem,
    Conjoon\Mail\Client\Data\CompoundKey\MessageKey,
    Conjoon\Mail\Client\Data\CompoundKey\FolderKey,
    Conjoon\Mail\Client\Data\MailAddress,
    Conjoon\Mail\Client\Data\MailAddressList,
    Conjoon\Mail\Client\Message\MessageItemList,
    Conjoon\Mail\Client\Message\Flag\FlagList,
    Conjoon\Mail\Client\Message\Flag\SeenFlag,
    Conjoon\Mail\Client\Message\Flag\DraftFlag,
    Conjoon\Mail\Client\MailClient,
    Conjoon\Mail\Client\Reader\ReadableMessagePartContentProcessor,
    Conjoon\Mail\Client\Writer\WritableMessagePartContentProcessor,
    Conjoon\Mail\Client\Writer\MessageItemDraftWriter,
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
        $midp = $this->getMessageItemDraftWriter();

        $service = $this->createService($miP, $rP, $wP, $pP, $midp);

        $this->assertInstanceOf(MessageItemService::class, $service);
        $this->assertInstanceOf(MailClient::class, $service->getMailClient());

        $this->assertSame($rP, $service->getReadableMessagePartContentProcessor());
        $this->assertSame($wP, $service->getWritableMessagePartContentProcessor());
        $this->assertSame($pP, $service->getPreviewTextProcessor());
        $this->assertSame($miP, $service->getMessageItemFieldsProcessor());
        $this->assertSame($midp, $service->getMessageItemDraftWriter());
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
     * Single MessageBody Test
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageBodyFor() {

        $service = $this->createService();

        $mailFolderId = "INBOX";
        $messageItemId = "8977";
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $messageKey = $this->createMessageKey($account, $mailFolderId, $messageItemId);

        $clientStub = $service->getMailClient();
        $clientStub->method('getMessageBody')
            ->with($messageKey)
            ->willReturn(
                $this->buildTestMessageBody($account->getId(), $mailFolderId, $messageItemId, "plain")
            );


        $body = $service->getMessageBody($messageKey);

        $this->assertSame($messageItemId, $body->getMessageKey()->getId());

        $this->assertTrue($body->getTextPlain() == true);
        $this->assertTrue($body->getTextHtml() == true);


        $this->assertSame("READtext/plainplain", $body->getTextPlain()->getContents());
        $this->assertSame("READtext/plainplain", $body->getTextHtml()->getContents());
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
     * Tests createMessageBody()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCreateMessageBody() {
        $service = $this->createService();

        $mailFolderId = "INBOX";
        $id           = "123";
        $account      = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $folderKey  = $this->createFolderKey($account, $mailFolderId);
        $messageKey = $this->createMessageKey($account, $mailFolderId, $id);

        $clientStub = $service->getMailClient();

        $clientStub->method('createMessageBody')
            ->with($folderKey)
            ->willReturn($messageKey);


        $clientStub->expects($this->never())
                   ->method('setFlags');

        $messageBody = $service->createMessageBody($folderKey,"a", "");
        $this->assertSame("WRITTENtext/htmla", $messageBody->getTextHtml()->getContents());
        $this->assertSame("WRITTENtext/plaina", $messageBody->getTextPlain()->getContents());

        $messageBody = $service->createMessageBody($folderKey,"", "a");
        $this->assertSame("WRITTENtext/htmla", $messageBody->getTextHtml()->getContents());
        $this->assertSame("WRITTENtext/plaina", $messageBody->getTextPlain()->getContents());

        $messageBody = $service->createMessageBody($folderKey,"a", "b");
        $this->assertSame("WRITTENtext/plaina", $messageBody->getTextPlain()->getContents());
        $this->assertSame("WRITTENtext/htmlb", $messageBody->getTextHtml()->getContents());

        $messageBody = $service->createMessageBody($folderKey,"", "");
        $this->assertSame("WRITTENtext/plain", $messageBody->getTextPlain()->getContents());
        $this->assertSame("WRITTENtext/html", $messageBody->getTextHtml()->getContents());
    }


    /**
     * Tests testCreateMessageBody_withDraftFlag()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCreateMessageBody_withDraftFlag() {
        $service = $this->createService();

        $mailFolderId = "INBOX";
        $id           = "123";
        $account      = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $folderKey  = $this->createFolderKey($account, $mailFolderId);
        $messageKey = $this->createMessageKey($account, $mailFolderId, $id);

        $clientStub = $service->getMailClient();

        $clientStub->method('createMessageBody')
                   ->with($folderKey)
                   ->willReturn($messageKey);

        $flagList = new FlagList;
        $flagList[0] = new DraftFlag(true);

        $clientStub->expects($this->once())
                   ->method("setFlags")
                   ->with($messageKey, $this->equalTo($flagList));

        $service->createMessageBody($folderKey,"a", "", true);
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

        $clientStub->method('createMessageBody')
            ->with($folderKey)
            ->willThrowException(new MailClientException);

        $messageBody = $service->createMessageBody($folderKey,"a", "");
        $this->assertNull($messageBody);
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
        $newKey = $this->createMessageKey($account, $mailFolderId, $newId);

        $messageItemDraft = new MessageItemDraft();
        $messageItemDraft->setMessageKey($newKey);

        $clientStub = $service->getMailClient();

        $clientStub->method('updateMessageDraft')
            ->with($messageKey)
            ->willReturn($messageItemDraft);

        $data = [];

        $messageItemDraft = $service->updateMessageDraft($messageKey, $data);
        $this->assertSame($newKey, $messageItemDraft->getMessageKey());
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

        $data = [];

        $clientStub = $service->getMailClient();

        $clientStub->method('updateMessageDraft')
                   ->with($messageKey)
                   ->willThrowException(new MailClientException);

        $messageItemDraft = $service->updateMessageDraft($messageKey, $data);
        $this->assertNull($messageItemDraft);
    }

// ------------------
//     Test Helper
// ------------------

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
                        "getMessageItemList", "getMessageItem", "getMessageBody",
                        "getUnreadMessageCount", "getTotalMessageCount", "getMailFolderList",
                        "getFileAttachmentList", "setFlags", "createMessageBody", "updateMessageDraft"])
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
                                     $previewTextProcessor = null,
                                     $messageItemDraftWriter = null) {
        return new DefaultMessageItemService(
            $this->getMailClientMock(),
            $messageItemFieldsProcessor ? $messageItemFieldsProcessor : $this->getMessageItemFieldsProcessor(),
            $readableMessagePartContentProcessor ? $readableMessagePartContentProcessor : $this->getReadableMessagePartContentProcessor(),
            $writableMessagePartContentProcessor ? $writableMessagePartContentProcessor : $this->getWritableMessagePartContentProcessor(),
            $previewTextProcessor ? $previewTextProcessor : $this->getPreviewTextProcessor(),
            $messageItemDraftWriter ? $messageItemDraftWriter : $this->getMessageItemDraftWriter()
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
     * @return MessageItemDraftWriter
     */
    protected function getMessageItemDraftWriter() :MessageItemDraftWriter{
        return new class implements MessageItemDraftWriter {
            public function process(array $data) :MessageItemDraft{
                return new MessageItemDraft;
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
     * @return array
     * @throws Exception
     */
    protected function buildTestMessageItem($mailAccountId, $mailFolderId, $messageItemId = null) {
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
            "seen" => false,
            "answered" =>false,
            "draft" =>false,
            "flagged" => false,
            "recent" => false,
            "hasAttachments" => false
        ];

        return new MessageItem($messageKey, $data);
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