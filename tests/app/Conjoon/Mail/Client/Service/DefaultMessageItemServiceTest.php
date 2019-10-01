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
    Conjoon\Mail\Client\Message\MessageBody,
    Conjoon\Mail\Client\Message\ListMessageItem,
    Conjoon\Mail\Client\Data\CompoundKey\MessageKey,
    Conjoon\Mail\Client\Data\CompoundKey\FolderKey,
    Conjoon\Mail\Client\Data\MailAddress,
    Conjoon\Mail\Client\Data\MailAddressList,
    Conjoon\Mail\Client\Message\MessageItemList,
    Conjoon\Mail\Client\Message\Flag\FlagList,
    Conjoon\Mail\Client\Message\Flag\SeenFlag,
    Conjoon\Mail\Client\MailClient,
    Conjoon\Mail\Client\Message\Text\MessagePartContentProcessor,
    Conjoon\Mail\Client\Message\Text\PreviewTextProcessor,
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

        $mP = $this->getMessagePartContentProcessor();
        $pP = $this->getPreviewTextProcessor();
        $miP = $this->getMessageItemFieldsProcessor();


        $service = $this->createService($miP, $mP, $pP);

        $this->assertInstanceOf(MessageItemService::class, $service);
        $this->assertInstanceOf(MailClient::class, $service->getMailClient());

        $this->assertSame($mP, $service->getMessagePartContentProcessor());
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
                $this->buildTestMessageBody($account->getId(), $mailFolderId, $messageItemId)
            );


        $body = $service->getMessageBody($messageKey);

        $cmpBody = $this->buildTestMessageBody($account->getId(), $mailFolderId, $messageItemId);

        $this->assertSame($messageItemId, $body->getMessageKey()->getId());
        $this->assertEquals($cmpBody->getMessageKey(), $body->getMessageKey());

        $this->assertTrue($body->getTextPlain() == true);
        $this->assertTrue($body->getTextHtml() == true);

        $this->assertNull($cmpBody->getTextPlain());
        $this->assertNull($cmpBody->getTextHtml());

        $this->assertSame("foo", $body->getTextPlain()->getContents());
        $this->assertSame("foo", $body->getTextHtml()->getContents());
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
                        "getFileAttachmentList", "setFlags"])
                    ->disableOriginalConstructor()
                    ->getMock();
    }


    /**
     * Helper function for creating the service.
     * @return DefaultMessageItemService
     */
    protected function createService($messageItemFieldsProcessor = null, $messagePartContentProcessor = null, $previewTextProcessor = null) {
        return new DefaultMessageItemService(
            $this->getMailClientMock(),
            $messageItemFieldsProcessor ? $messageItemFieldsProcessor : $this->getMessageItemFieldsProcessor(),
            $messagePartContentProcessor ? $messagePartContentProcessor : $this->getMessagePartContentProcessor(),
            $previewTextProcessor ? $previewTextProcessor : $this->getPreviewTextProcessor()
        );
    }


    /**
     * @return MessagePartContentProcessor
     */
    protected function getMessagePartContentProcessor() :MessagePartContentProcessor{
        return new class implements MessagePartContentProcessor {
            public function process(MessagePart $messagePart, string $toCharset = "UTF-8") :MessagePart{
                $messagePart->setContents("foo", "ISO-8859-1");
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
    protected function buildTestMessageBody($mailAccountId, $mailFolderId, $messageItemId) {

        $messageKey = new MessageKey($mailAccountId, $mailFolderId, $messageItemId);

        $mb = new MessageBody($messageKey);
        
        return $mb;

    }

}