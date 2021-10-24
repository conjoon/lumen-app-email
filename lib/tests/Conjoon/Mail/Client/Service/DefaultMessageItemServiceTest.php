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

namespace Tests\Conjoon\Mail\Client\Service;

use Conjoon\Core\ParameterBag;
use Conjoon\Mail\Client\Data\CompoundKey\FolderKey;
use Conjoon\Mail\Client\Data\CompoundKey\MessageKey;
use Conjoon\Mail\Client\Data\MailAddress;
use Conjoon\Mail\Client\Data\MailAddressList;
use Conjoon\Mail\Client\MailClient;
use Conjoon\Mail\Client\MailClientException;
use Conjoon\Mail\Client\Message\AbstractMessageItem;
use Conjoon\Mail\Client\Message\Flag\FlagList;
use Conjoon\Mail\Client\Message\Flag\SeenFlag;
use Conjoon\Mail\Client\Message\ListMessageItem;
use Conjoon\Mail\Client\Message\MessageBody;
use Conjoon\Mail\Client\Message\MessageBodyDraft;
use Conjoon\Mail\Client\Message\MessageItem;
use Conjoon\Mail\Client\Message\MessageItemDraft;
use Conjoon\Mail\Client\Message\MessageItemList;
use Conjoon\Mail\Client\Message\MessagePart;
use Conjoon\Mail\Client\Message\Text\MessageItemFieldsProcessor;
use Conjoon\Mail\Client\Message\Text\PreviewTextProcessor;
use Conjoon\Mail\Client\Query\MessageItemListResourceQuery;
use Conjoon\Mail\Client\Reader\DefaultPlainReadableStrategy;
use Conjoon\Mail\Client\Reader\PurifiedHtmlStrategy;
use Conjoon\Mail\Client\Reader\ReadableMessagePartContentProcessor;
use Conjoon\Mail\Client\Service\DefaultMessageItemService;
use Conjoon\Mail\Client\Service\MessageItemService;
use Conjoon\Mail\Client\Service\ServiceException;
use Conjoon\Mail\Client\Writer\DefaultHtmlWritableStrategy;
use Conjoon\Mail\Client\Writer\DefaultPlainWritableStrategy;
use Conjoon\Mail\Client\Writer\WritableMessagePartContentProcessor;
use Conjoon\Text\CharsetConverter;
use DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Tests\TestCase;
use Tests\TestTrait;

/**
 * Class DefaultMessageItemServiceTest
 *
 */
class DefaultMessageItemServiceTest extends TestCase
{
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
    public function testInstance()
    {

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
     * @noinspection PhpUndefinedMethodInspection
     * @noinspection PhpUnusedLocalVariableInspection
     */
    public function testGetMessageItemList()
    {
        $mailFolderId = "INBOX";
        $folderKey = $this->createFolderKey(null, $mailFolderId);

        $buildList = function ($options) use ($mailFolderId) {

            $start = $options["start"];
            $limit = $options["limit"];
            $messageItemList = new MessageItemList();

            $attributes = $options["attributes"] ?? [];
            $html  = $attributes["html"] ?? false;
            $plain = $attributes["html"] ?? false;
            $generatedTexts = [];

            for ($i = 0; $i < $limit; $i++) {
                $text = null;
                $texts  = [
                    "text/html"  => $this->generateRandomString(is_array($html) ? $html["length"] : null),
                    "text/plain" => $this->generateRandomString(is_array($plain) ? $plain["length"] : null)
                ];


                $types = ["text/html", "text/plain"];

                $contentType = $html && $plain ? $types[$i % 2] : ($html ? $types[0] : ($plain ? $types[1] : null));
                if ($contentType) {
                    $text = $texts[$contentType];
                }

                $generatedTexts[] = $text ? [$contentType => $text] : [];
                $id = "" . ($i + 1);
                $messageItemList[] = new ListMessageItem(
                    $this->createMessageKey(null, $mailFolderId, $id),
                    null,
                    $text ? new MessagePart($text, "UTF-8", $contentType) : null
                );
            }

            return [
                "texts" => $generatedTexts,
                "lists" => $messageItemList
            ];
        };

        $input = [
            ["start" => 0, "limit" => 2, "attributes" => ["html" => true, "plain" => true]],
            ["start" => 0, "limit" => 2, "attributes" => ["html" => false, "plain" => false]],
            ["start" => 0, "limit" => 2, "attributes" => ["html" => ["length" => 4], "plain" => ["length"  => 20]]]
        ];

        foreach ($input as $options) {
            $query = new MessageItemListResourceQuery(new ParameterBag($options));
            $service = $this->createService();
            // we can work with consecutive calls, but for this test, re-creating the client
            // works as well
            $clientStub = $service->getMailClient();

            list("texts" => $generatedTexts, "lists" => $messageItemList) = $buildList($options);

            $clientStub->method("getMessageItemList")
                ->with($folderKey, $query)
                ->willReturn($messageItemList);

            $results = $service->getMessageItemList($folderKey, $query);
            $i = 0;
            foreach ($results as $resultItem) {
                $this->assertInstanceOf(ListMessageItem::Class, $resultItem);
                $text = !empty($generatedTexts[$i])
                        ? $generatedTexts[$i][$resultItem->getMessagePart()->getMimeType()]
                        : null;

                $this->assertSame(
                    $text,
                    $resultItem->getMessagePart() ? $resultItem->getMessagePart()->getContents() : null
                );
                $i++;
            }
        }
    }


    /**
     * Single MessageItem Test
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @noinspection PhpUndefinedMethodInspection
     */
    public function testGetMessageItem()
    {

        $service = $this->createService();

        $mailFolderId = "INBOX";
        $messageItemId = "8977";
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $messageKey = $this->createMessageKey($account->getId(), $mailFolderId, $messageItemId);


        $clientStub = $service->getMailClient();
        $clientStub->method("getMessageItem")
            ->with($messageKey)
            ->willReturn(
                $this->buildTestMessageItem($account->getId(), $mailFolderId, $messageItemId)
            );


        $item = $service->getMessageItem($messageKey);

        $cmpItem = $this->buildTestMessageItem($account->getId(), $mailFolderId, $messageItemId);

        $cmpItem->setDate($item->getDate());

        $this->assertSame($messageItemId, $item->getMessageKey()->getId());

        $cmpJson = $cmpItem->toJson();
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
     * @noinspection PhpUndefinedMethodInspection
     */
    public function testGetMessageItemDraft()
    {

        $service = $this->createService();

        $mailFolderId = "INBOX";
        $messageItemId = "8977";
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $messageKey = $this->createMessageKey($account->getId(), $mailFolderId, $messageItemId);

        $clientStub = $service->getMailClient();
        $clientStub->method("getMessageItemDraft")
            ->with($messageKey)
            ->willReturn(
                $this->buildTestMessageItem($account->getId(), $mailFolderId, $messageItemId, true)
            );


        $item = $service->getMessageItemDraft($messageKey);

        $cmpItem = $this->buildTestMessageItem($account->getId(), $mailFolderId, $messageItemId, true);

        $cmpItem->setDate($item->getDate());

        $this->assertSame($messageItemId, $item->getMessageKey()->getId());

        $cmpJson = $cmpItem->toJson();
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
    public function testGetMessageBodyForPlain()
    {

        $this->getMessageBodyForTestHelper("plain");
    }


    /**
     * Single MessageBody Test
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageBodyForHtml()
    {

        $this->getMessageBodyForTestHelper("", "html");
    }


    /**
     * Single MessageBody Test
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetMessageBodyForBoth()
    {

        $this->getMessageBodyForTestHelper("plain", "html");
    }


    /**
     * Tests getTotalMessageCount()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @noinspection PhpUndefinedMethodInspection
     */
    public function testGetTotalMessageCount()
    {

        $service = $this->createService();

        $mailFolderId = "INBOX";
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $folderKey = $this->createFolderKey($account, $mailFolderId);

        $clientStub = $service->getMailClient();
        $clientStub->method("getTotalMessageCount")
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
     * @noinspection PhpUndefinedMethodInspection
     */
    public function testUnreadMessageCount()
    {
        $service = $this->createService();

        $mailFolderId = "INBOX";
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $folderKey = $this->createFolderKey($account, $mailFolderId);

        $clientStub = $service->getMailClient();
        $clientStub->method("getUnreadMessageCount")
            ->with($folderKey)
            ->willReturn(311);

        $this->assertSame(311, $service->getUnreadMessageCount($folderKey));
    }


    /**
     * Tests setFlags()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @noinspection PhpUndefinedMethodInspection
     */
    public function testSetFlags()
    {
        $service = $this->createService();

        $mailFolderId = "INBOX";
        $id = "123";
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $messageKey = $this->createMessageKey($account, $mailFolderId, $id);

        $flagList = new FlagList();
        $flagList[] = new SeenFlag(true);

        $clientStub = $service->getMailClient();
        $clientStub->method("setFlags")
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
    public function testCreateMessageBodyDraftException()
    {
        $service = $this->createService();

        $this->expectException(ServiceException::class);

        $mailFolderId = "INBOX";
        $id = "123";
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $folderKey = $this->createFolderKey($account, $mailFolderId);
        $messageBodyDraft = new MessageBodyDraft($this->createMessageKey($account, $mailFolderId, $id));

        $service->createMessageBodyDraft($folderKey, $messageBodyDraft);
    }


    /**
     * Tests createMessageBodyDraft()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @noinspection PhpUndefinedMethodInspection
     * @noinspection SpellCheckingInspection
     */
    public function testCreateMessageBodyDraft()
    {

        $mailFolderId = "INBOX";
        $id = "123";
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $folderKey = $this->createFolderKey($account, $mailFolderId);

        $clientMockedMethod = function ($folderKey, $messageBodyDraft) use ($account, $mailFolderId, $id) {
            return $messageBodyDraft->setMessageKey($this->createMessageKey($account, $mailFolderId, $id));
        };


        $service = $this->createService();
        $clientStub = $service->getMailClient();
        $messageBodyDraft = new MessageBodyDraft();
        $clientStub->method("createMessageBodyDraft")
            ->with($folderKey, $messageBodyDraft)
            ->will($this->returnCallback($clientMockedMethod));
        $messageBodyDraft->setTextHtml(new MessagePart("a", "UTF-8", "text/html"));
        $messageBody = $service->createMessageBodyDraft($folderKey, $messageBodyDraft);
        $this->assertSame("WRITTENtext/htmla", $messageBody->getTextHtml()->getContents());
        $this->assertSame("WRITTENtext/plaina", $messageBody->getTextPlain()->getContents());
        $this->assertNotSame($messageBodyDraft, $messageBody);


        $service = $this->createService();
        $clientStub = $service->getMailClient();
        $messageBodyDraft = new MessageBodyDraft();
        $clientStub->method("createMessageBodyDraft")
            ->with($folderKey, $messageBodyDraft)
            ->will($this->returnCallback($clientMockedMethod));
        $messageBodyDraft->setTextPlain(new MessagePart("a", "UTF-8", "text/plain"));
        $messageBody = $service->createMessageBodyDraft($folderKey, $messageBodyDraft);
        $this->assertSame("WRITTENtext/htmla", $messageBody->getTextHtml()->getContents());
        $this->assertSame("WRITTENtext/plaina", $messageBody->getTextPlain()->getContents());
        $this->assertNotSame($messageBodyDraft, $messageBody);


        $service = $this->createService();
        $clientStub = $service->getMailClient();
        $messageBodyDraft = new MessageBodyDraft();
        $clientStub->method("createMessageBodyDraft")
            ->with($folderKey, $messageBodyDraft)
            ->will($this->returnCallback($clientMockedMethod));
        $messageBodyDraft->setTextPlain(new MessagePart("a", "UTF-8", "text/plain"));
        $messageBodyDraft->setTextHtml(new MessagePart("b", "UTF-8", "text/html"));
        $messageBody = $service->createMessageBodyDraft($folderKey, $messageBodyDraft);
        $this->assertSame("WRITTENtext/plaina", $messageBody->getTextPlain()->getContents());
        $this->assertSame("WRITTENtext/htmlb", $messageBody->getTextHtml()->getContents());
        $this->assertNotSame($messageBodyDraft, $messageBody);


        $service = $this->createService();
        $clientStub = $service->getMailClient();
        $messageBodyDraft = new MessageBodyDraft();
        $clientStub->method("createMessageBodyDraft")
            ->with($folderKey, $messageBodyDraft)
            ->will($this->returnCallback($clientMockedMethod));
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
     * @noinspection PhpUndefinedMethodInspection
     */
    public function testCreateMessageBodyReturningNull()
    {
        $service = $this->createService();

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId = "INBOX";
        $folderKey = $this->createFolderKey($account, $mailFolderId);

        $clientStub = $service->getMailClient();

        $messageBodyDraft = new MessageBodyDraft();
        $messageBodyDraft->setTextHtml(new MessagePart("a", "UTF-8", "text/html"));

        $clientStub->method("createMessageBodyDraft")
            ->with($folderKey, $messageBodyDraft)
            ->willThrowException(new MailClientException());

        $result = $service->createMessageBodyDraft($folderKey, $messageBodyDraft);
        $this->assertNull($result);
    }

    /**
     * Tests updateMessageDraft()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @noinspection PhpUndefinedMethodInspection
     */
    public function testUpdateMessageDraft()
    {
        $service = $this->createService();

        $mailFolderId = "INBOX";
        $id = "123";
        $newId = "1234";
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $messageKey = $this->createMessageKey($account, $mailFolderId, $id);
        $newKey = $this->createMessageKey($account, $mailFolderId, $newId);

        $messageItemDraft = new MessageItemDraft($messageKey);
        $transformDraft = new MessageItemDraft($newKey);

        $clientStub = $service->getMailClient();

        $clientStub->method("updateMessageDraft")
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
     * @noinspection PhpUndefinedMethodInspection
     */
    public function testUpdateMessageDraftReturningNull()
    {

        $service = $this->createService();

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId = "INBOX";
        $id = "123";
        $messageKey = $this->createMessageKey($account, $mailFolderId, $id);

        $transformDraft = new MessageItemDraft($messageKey);

        $clientStub = $service->getMailClient();

        $clientStub->method("updateMessageDraft")
            ->with($transformDraft)
            ->willThrowException(new MailClientException());

        $messageItemDraft = $service->updateMessageDraft($transformDraft);
        $this->assertNull($messageItemDraft);
    }


    /**
     * Tests testUpdateMessageBodyDraft_exception()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUpdateMessageBodyDraftException()
    {
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
     * @noinspection PhpUndefinedMethodInspection
     */
    public function testUpdateMessageBodyDraftReturningNull()
    {

        $service = $this->createService();

        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $mailFolderId = "INBOX";
        $id = "123";
        $messageKey = $this->createMessageKey($account, $mailFolderId, $id);

        $messageBodyDraft = new MessageBodyDraft($messageKey);

        $clientStub = $service->getMailClient();

        $clientStub->method("updateMessageBodyDraft")
            ->with($messageBodyDraft)
            ->willThrowException(new MailClientException());

        $newDraft = $service->updateMessageBodyDraft($messageBodyDraft);
        $this->assertNull($newDraft);
    }


    /**
     * Tests updateMessageBodyDraft()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @noinspection PhpUndefinedMethodInspection
     * @noinspection SpellCheckingInspection
     */
    public function testUpdateMessageBodyDraft()
    {

        $mailFolderId = "INBOX";
        $id = "123";
        $newId = "abc";
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $getKey = function () use ($account, $mailFolderId, $id) {
            return $this->createMessageKey($account, $mailFolderId, $id);
        };

        $clientMockedMethod = function ($messageBodyDraft) use ($account, $mailFolderId, $newId) {
            return $messageBodyDraft->setMessageKey($this->createMessageKey($account, $mailFolderId, $newId));
        };

        $service = $this->createService();
        $clientStub = $service->getMailClient();
        $messageBodyDraft = new MessageBodyDraft($getKey());
        $clientStub->method("updateMessageBodyDraft")
            ->with($messageBodyDraft)
            ->will($this->returnCallback($clientMockedMethod));
        $messageBodyDraft->setTextHtml(new MessagePart("a", "UTF-8", "text/html"));
        $messageBody = $service->updateMessageBodyDraft($messageBodyDraft);
        $this->assertSame("WRITTENtext/htmla", $messageBody->getTextHtml()->getContents());
        $this->assertSame("WRITTENtext/plaina", $messageBody->getTextPlain()->getContents());
        $this->assertNotSame($messageBodyDraft, $messageBody);


        $service = $this->createService();
        $clientStub = $service->getMailClient();
        $messageBodyDraft = new MessageBodyDraft($getKey());
        $clientStub->method("updateMessageBodyDraft")
            ->with($messageBodyDraft)
            ->will($this->returnCallback($clientMockedMethod));
        $messageBodyDraft->setTextPlain(new MessagePart("a", "UTF-8", "text/plain"));
        $messageBody = $service->updateMessageBodyDraft($messageBodyDraft);
        $this->assertSame("WRITTENtext/htmla", $messageBody->getTextHtml()->getContents());
        $this->assertSame("WRITTENtext/plaina", $messageBody->getTextPlain()->getContents());
        $this->assertNotSame($messageBodyDraft, $messageBody);


        $service = $this->createService();
        $clientStub = $service->getMailClient();
        $messageBodyDraft = new MessageBodyDraft($getKey());
        $clientStub->method("updateMessageBodyDraft")
            ->with($messageBodyDraft)
            ->will($this->returnCallback($clientMockedMethod));
        $messageBodyDraft->setTextPlain(new MessagePart("a", "UTF-8", "text/plain"));
        $messageBodyDraft->setTextHtml(new MessagePart("b", "UTF-8", "text/html"));
        $messageBody = $service->updateMessageBodyDraft($messageBodyDraft);
        $this->assertSame("WRITTENtext/plaina", $messageBody->getTextPlain()->getContents());
        $this->assertSame("WRITTENtext/htmlb", $messageBody->getTextHtml()->getContents());
        $this->assertNotSame($messageBodyDraft, $messageBody);


        $service = $this->createService();
        $clientStub = $service->getMailClient();
        $messageBodyDraft = new MessageBodyDraft($getKey());
        $clientStub->method("updateMessageBodyDraft")
            ->with($messageBodyDraft)
            ->will($this->returnCallback($clientMockedMethod));
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
     * @noinspection PhpUndefinedMethodInspection
     */
    public function testSendMessageDraft()
    {

        $mailFolderId = "INBOX";
        $id = "123";
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $messageKey = $this->createMessageKey($account, $mailFolderId, $id);

        $testSend = function ($expected) use ($messageKey) {

            $service = $this->createService();
            $clientStub = $service->getMailClient();
            $clientStub->method("sendMessageDraft")->with($messageKey)->willReturn($expected);

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
     * @noinspection PhpUndefinedMethodInspection
     */
    public function testSendMessageDraftException()
    {

        $mailFolderId = "INBOX";
        $id = "123";
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $messageKey = $this->createMessageKey($account, $mailFolderId, $id);

        $service = $this->createService();
        $clientStub = $service->getMailClient();
        $clientStub->method("sendMessageDraft")
            ->with($messageKey)
            ->willThrowException(new MailClientException());

        $this->assertFalse($service->sendMessageDraft($messageKey));
    }


    /**
     * Tests moveMessage()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @noinspection PhpUndefinedMethodInspection
     * @noinspection SpellCheckingInspection
     */
    public function testMoveMessage()
    {

        $mailFolderId = "INBOX";
        $id = "123";
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $messageKey = $this->createMessageKey($account, $mailFolderId, $id);
        $folderKey = new FolderKey($account, "foo");

        $expected = new MessageKey($account, "foo", "newid");

        $service = $this->createService();
        $clientStub = $service->getMailClient();
        $clientStub->method("moveMessage")->with($messageKey, $folderKey)->willReturn($expected);

        $this->assertSame($service->moveMessage($messageKey, $folderKey), $expected);
    }


    /**
     * Tests moveMessage() /w exception
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @noinspection PhpUndefinedMethodInspection
     */
    public function testMoveMessageException()
    {

        $mailFolderId = "INBOX";
        $id = "123";
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $messageKey = $this->createMessageKey($account, $mailFolderId, $id);
        $folderKey = new FolderKey($account, "foo");

        $service = $this->createService();
        $clientStub = $service->getMailClient();
        $clientStub->method("moveMessage")->with($messageKey, $folderKey)
            ->willThrowException(new MailClientException("should not bubble"));

        $this->assertSame($service->moveMessage($messageKey, $folderKey), null);
    }


    /**
     * Tests getListMessageItem
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @noinspection PhpUndefinedMethodInspection
     */
    public function testGetListMessageItem()
    {

        $service = $this->createService();

        $clientStub = $service->getMailClient();

        $mailAccountId = "dev";
        $mailFolderId = "INBOX";
        $messageItemId = "1234";
        $messageKey = $this->createMessageKey($mailAccountId, $mailFolderId, $messageItemId);
        $folderKey = $messageKey->getFolderKey();

        $messageItemListMock = new MessageItemList();
        $messageItemListMock[] = new ListMessageItem(
            $messageKey,
            null,
            new MessagePart("preview", "UTF-8", "text/html")
        );

        $clientStub->method("getMessageItemList")
            ->with(
                $folderKey,
                $this->callback(
                    function ($query) use ($messageKey) {
                        $this->assertSame(["ids" => [$messageKey->getId()]], $query->toJson());
                        return true;
                    }
                )
            )
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
    public function testDeleteMessageOkay()
    {
        $this->deleteMessageTest(true);
    }


    /**
     * Tests deleteMessage()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDeleteMessageFailed()
    {
        $this->deleteMessageTest(false);
    }


    /**
     * Tests deleteMessage()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDeleteMessageException()
    {
        $this->deleteMessageTest("exception");
    }


// ------------------
//     Test Helper
// ------------------

    /**
     * @param string $plain
     * @param string $html
     * @noinspection PhpUndefinedMethodInspection
     * @noinspection SpellCheckingInspection
     */
    protected function getMessageBodyForTestHelper(string $plain = "", string $html = "")
    {

        $service = $this->createService();

        $mailFolderId = "INBOX";
        $messageItemId = "8977";
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $messageKey = $this->createMessageKey($account, $mailFolderId, $messageItemId);

        $clientStub = $service->getMailClient();
        $clientStub->method("getMessageBody")
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
     * @noinspection PhpUndefinedMethodInspection
     */
    public function deleteMessageTest($type)
    {

        $mailFolderId = "INBOX";
        $id = "123";
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        $messageKey = $this->createMessageKey($account, $mailFolderId, $id);

        $service = $this->createService();
        $clientStub = $service->getMailClient();

        $op = $clientStub->method("deleteMessage")->with($messageKey);

        if ($type === "exception") {
            $op->willThrowException(new MailClientException());
            $expected = false;
        } elseif (is_bool($type)) {
            $expected = $type;
            $op->willReturn($expected);
        } else {
            $this->fail("No valid type configured for test.");
        }

        $clientStub->method("deleteMessage")->with($messageKey)->willReturn($expected);

        $this->assertSame($service->deleteMessage($messageKey), $expected);
    }


    /**
     * @param mixed $mailAccountId
     * @param string $id
     *
     * @return FolderKey
     */
    protected function createFolderKey($mailAccountId, string $id = "INBOX"): FolderKey
    {
        $mailAccountId = $mailAccountId ??  $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        return new FolderKey(
            $mailAccountId,
            $id
        );
    }

    /**
     * @param $mailAccountId
     * @param $mailFolderId
     * @param $id
     * @return MessageKey
     */
    protected function createMessageKey($mailAccountId, $mailFolderId, $id): MessageKey
    {

        $mailAccountId = $mailAccountId ?? null;
        $mailFolderId = $mailFolderId ?? "INBOX";

        return new MessageKey(
            $mailAccountId ?? $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org"),
            $mailFolderId,
            $id
        );
    }


    /**
     * Helper function for creating the client Mock.
     * @return MockObject
     */
    protected function getMailClientMock(): MockObject
    {
        return $this->getMockBuilder(MailClient::class)
            ->onlyMethods([
                "getMessageItemList",
                "getMessageItem",
                "getMessageItemDraft",
                "getMessageBody",
                "getUnreadMessageCount",
                "getTotalMessageCount",
                "getMailFolderList",
                "getFileAttachmentList",
                "setFlags",
                "createMessageBodyDraft",
                "updateMessageDraft",
                "updateMessageBodyDraft",
                "sendMessageDraft",
                "moveMessage",
                "deleteMessage",
                "getSupportedAttributes",
                "getDefaultAttributes"
            ])
            ->addMethods([
                "getListMessageItem",
                ])
            ->disableOriginalConstructor()
            ->getMock();
    }


    /**
     * Helper function for creating the service.
     * @param null $messageItemFieldsProcessor
     * @param null $readableMessagePartContentProcessor
     * @param null $writableMessagePartContentProcessor
     * @param null $previewTextProcessor
     *
     * @return DefaultMessageItemService
     * @noinspection PhpParamsInspection
     */
    protected function createService(
        $messageItemFieldsProcessor = null,
        $readableMessagePartContentProcessor = null,
        $writableMessagePartContentProcessor = null,
        $previewTextProcessor = null
    ): DefaultMessageItemService {
        return new DefaultMessageItemService(
            $this->getMailClientMock(),
            $messageItemFieldsProcessor ?? $this->getMessageItemFieldsProcessor(),
            $readableMessagePartContentProcessor ?? $this->getReadableMessagePartContentProcessor(),
            $writableMessagePartContentProcessor ?? $this->getWritableMessagePartContentProcessor(),
            $previewTextProcessor ?? $this->getPreviewTextProcessor()
        );
    }


    /**
     * @return ReadableMessagePartContentProcessor
     */
    protected function getReadableMessagePartContentProcessor(): ReadableMessagePartContentProcessor
    {
        return new class (
            new CharsetConverter(),
            new DefaultPlainReadableStrategy(),
            new PurifiedHtmlStrategy()
        ) extends ReadableMessagePartContentProcessor {
            public function process(MessagePart $messagePart, string $toCharset = "UTF-8"): MessagePart
            {
                $messagePart->setContents(
                    "READ" .
                    $messagePart->getMimeType() .
                    $messagePart->getContents(),
                    "ISO-8859-1"
                );
                return $messagePart;
            }
        };
    }

    /**
     * @return WritableMessagePartContentProcessor
     */
    protected function getWritableMessagePartContentProcessor(): WritableMessagePartContentProcessor
    {
        return new class (
            new CharsetConverter(),
            new DefaultPlainWritableStrategy(),
            new DefaultHtmlWritableStrategy()
        ) extends WritableMessagePartContentProcessor {
            public function process(MessagePart $messagePart, string $toCharset = "UTF-8"): MessagePart
            {
                $messagePart->setContents(
                    "WRITTEN" .
                    $messagePart->getMimeType() .
                    $messagePart->getContents(),
                    "ISO-8859-1"
                );
                return $messagePart;
            }
        };
    }


    /**
     * @return PreviewTextProcessor
     */
    protected function getPreviewTextProcessor(): PreviewTextProcessor
    {
        return new class implements PreviewTextProcessor {
            public function process(MessagePart $messagePart, string $toCharset = "UTF-8"): MessagePart
            {
                $messagePart->setContents(
                    $messagePart->getContents(),
                    "ISO-8859-1"
                );
                return $messagePart;
            }
        };
    }


    /**
     * @return MessageItemFieldsProcessor
     */
    protected function getMessageItemFieldsProcessor(): MessageItemFieldsProcessor
    {
        return new class implements MessageItemFieldsProcessor {
            public function process(AbstractMessageItem $messageItem, string $toCharset = "UTF-8"): AbstractMessageItem
            {
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
     * @param null $messageItemId
     * @param bool $isDraft
     * @return MessageItem|MessageItemDraft
     * @noinspection SpellCheckingInspection
     */
    protected function buildTestMessageItem($mailAccountId, $mailFolderId, $messageItemId = null, bool $isDraft = false)
    {

        if ($messageItemId == null) {
            throw new RuntimeException("Unexpected value for messageItemId.");
        }

        $messageKey = new MessageKey($mailAccountId, $mailFolderId, $messageItemId);

        $data = [
            "from" => new MailAddress("addr", "from"),
            "to" => new MailAddressList(),
            "size" => 100,
            "subject" => "subject",
            "date" => new DateTime(),
            "answered" => false,
            "draft" => false,
            "flagged" => false,
            "recent" => false
        ];

        if ($isDraft === false) {
            $data["seen"] = false;
            $data["hasAttachments"] = false;
            return new MessageItem($messageKey, $data);
        } else {
            $data["cc"] = MailAddressList::fromString(json_encode([["address" => "test@cc"]]));
            $data["bcc"] = MailAddressList::fromString(json_encode([["address" => "test@bcc"]]));
            $data["replyTo"] = MailAddress::fromString(json_encode(["address" => "test@replyTo"]));
            return new MessageItemDraft($messageKey, $data);
        }
    }


    /**
     * Helper function for creating a MessageBody.
     * @param $mailAccountId
     * @param $mailFolderId
     * @param $messageItemId
     * @param string $plain
     * @param string $html
     *
     * @return MessageBody
     */
    protected function buildTestMessageBody(
        $mailAccountId,
        $mailFolderId,
        $messageItemId,
        string $plain = "",
        string $html = ""
    ): MessageBody {

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


    protected function generateRandomString($length = null): string
    {
        $chars = "0123456789#abcdefghilkmnopqrstuvwxyz";
        $random = [];

        for ($i = 0; $i < ($length ?? 400); $i++) {
            $random[] = $chars[rand(0, strlen($chars) - 1)];
        }
        return implode("", $random);
    }
}
