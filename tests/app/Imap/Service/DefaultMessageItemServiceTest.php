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

use App\Imap\Service\DefaultMessageItemService;


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
     */
    public function testInstance() {

        $service = $this->createService();

        $this->assertInstanceOf(\App\Imap\Service\MessageItemService::class, $service);
        $this->assertInstanceOf(\Conjoon\Mail\Client\MailClient::class, $service->getClient());
    }


    /**
     * Multiple Message Item Test.
     */
    public function testGetMessageItemsFor() {

        $service = $this->createService();

        $clientStub = $service->getClient();

        $mailFolderId = "INBOX";
        $options = ["start" => 0, "limit" => 2];
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");


        $clientStub->method('getMessageItemsFor')
                   ->with($account, $mailFolderId, $options)
                    ->willReturn([
                        "total" => 3,
                        "data" => $this->buildTestMessageItem(2, $account->getId(), $mailFolderId),
                        "meta" => [
                        "cn_unreadCount" => 43, "mailFolderId" => $mailFolderId, "mailAccountId" => $account->getId()
                    ]]);

        $results = $service->getMessageItemsFor($account, $mailFolderId, $options);


        $this->assertSame([
            "cn_unreadCount" => 43,
            "mailFolderId" => $mailFolderId,
            "mailAccountId" => $account->getId()
            ], $results["meta"]
        );

        $this->assertSame(3, $results["total"]);

        $this->assertSame(2, count($results["data"]));

        $structure = [
            "id", "mailAccountId", "mailFolderId", "from", "to", "size", "subject",
            "date", "seen", "answered", "draft", "flagged", "recent", "previewText",
            "hasAttachments"
        ];

        foreach ($results["data"] as $item) {
            foreach ($structure as $key) {
                $this->assertArrayHasKey($key, $item);
            }
        }
    }


    /**
     * Single MessageItem Test
     */
    public function testGetMessageItemFor() {

        $service = $this->createService();

        $mailFolderId = "INBOX";
        $messageItemId = "8977";
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $clientStub = $service->getClient();
        $clientStub->method('getMessageItemFor')
            ->with($account, $mailFolderId, $messageItemId)
            ->willReturn(
                $this->buildTestMessageItem(1, $account->getId(), $mailFolderId, $messageItemId)[0]
            );

        $item = $service->getMessageItemFor($account, $mailFolderId, $messageItemId);

        $cmpItem = $this->buildTestMessageItem(1, $account->getId(), $mailFolderId, $messageItemId)[0];
        $cmpItem["date"] = $item["date"] = "";

        $this->assertSame($messageItemId, $item["id"]);
        $this->assertFalse(array_key_exists('previewText', $item));
        $this->assertSame($cmpItem, $item);
    }


    /**
     * Single MessageBody Test
     */
    public function testGetMessageBodyFor() {

        $service = $this->createService();

        $mailFolderId = "INBOX";
        $messageItemId = "8977";
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $clientStub = $service->getClient();
        $clientStub->method('getMessageBodyFor')
            ->with($account, $mailFolderId, $messageItemId)
            ->willReturn(
                $this->buildTestMessageBody($account->getId(), $mailFolderId, $messageItemId)
            );

        $body = $service->getMessageBodyFor($account, "INBOX", $messageItemId);

        $cmpBody = $this->buildTestMessageBody($account->getId(), $mailFolderId, $messageItemId);

        $this->assertSame($messageItemId, $body["id"]);
        $this->assertSame($cmpBody, $body);
    }


// ------------------
//     Test Helper
// ------------------
    /**
     * Helper function for creating the client Mock.
     * @return mixed
     */
    protected function getMailClientMock() {
        return $this->getMockBuilder('Conjoon\Mail\Client\MailClient')
                    ->setMethods(["getMessageItemsFor", "getMessageItemFor", "getMessageBodyFor"])
                    ->disableOriginalConstructor()
                    ->getMock();
    }


    /**
     * Helper function for creating the service.
     * @return DefaultMessageItemService
     */
    protected function createService() {
        return new DefaultMessageItemService($this->getMailClientMock());
    }


    /**
     * Helper function for creating Dummy MessageItem.
     * @param $count
     * @param $mailAccountId
     * @param $mailFolderId
     * @return array
     * @throws Exception
     */
    protected function buildTestMessageItem($count, $mailAccountId, $mailFolderId, $messageItemId = null) {
        $items = [];

        if ($messageItemId !== null && $count > 1) {
            throw new \RuntimeExeption("Unexpected value for messageItemId since count was greater than 1.");
        }

        for ($i = 0; $i < $count; $i++) {
            $item = [
                "id" => $messageItemId ?: $count+1,
                "mailAccountId" => $mailAccountId,
                "mailFolderId" => $mailFolderId,
                "from" => [],
                "to" => [],
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

            if ($count > 1) {
                $item["previewText"] = "Text";
            }

            $items[] = $item;
        }
        return $items;
    }


    /**
     * Helper function for creating a MessageBody.
     * @param $mailAccountId
     * @param $mailFolderId
     * @param $messageItemId
     * @return array
     */
    protected function buildTestMessageBody($mailAccountId, $mailFolderId, $messageItemId) {

        return [
            "id" => $messageItemId,
            "mailAccountId" => $mailAccountId,
            "mailFolderId" => $mailFolderId,
            "textHtml" => "foo",
            "textPlain" => "bar"
        ];

    }

}