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

use Conjoon\Mail\Client\Service\MessageItemService,
    Conjoon\Mail\Client\Data\CompoundKey\FolderKey,
    Conjoon\Mail\Client\Data\CompoundKey\MessageKey,
    Conjoon\Mail\Client\Message\MessageItemList,
    Conjoon\Mail\Client\Message\MessagePart,
    Conjoon\Mail\Client\Message\MessageBody,
    Conjoon\Mail\Client\Message\MessageBodyDraft,
    Conjoon\Mail\Client\Message\MessageItemDraft,
    Conjoon\Mail\Client\Message\MessageItem,
    Conjoon\Mail\Client\Message\ListMessageItem,
    Conjoon\Mail\Client\Message\Flag\FlagList,
    Conjoon\Mail\Client\Message\Flag\SeenFlag,
    Conjoon\Mail\Client\Message\Flag\FlaggedFlag,
    Conjoon\Mail\Client\Request\Message\Transformer\MessageItemDraftJsonTransformer,
    Conjoon\Mail\Client\Request\Message\Transformer\MessageBodyDraftJsonTransformer,
    Conjoon\Util\ArrayUtil;



class MessageItemControllerTest extends TestCase
{
    use TestTrait;


    /**
     * Tests index() to make sure method returns list of available MessageItems associated with
     * the current signed in user.
     *
     *
     * @return void
     */
    public function testIndex_success()
    {
        $serviceStub = $this->initServiceStub();
        $this->initItemTransformerStub();
        $this->initBodyTransformerStub();

        $unreadCmp = 5;
        $totalCmp  = 100;


        $folderKey = new FolderKey(
            $this->getTestMailAccount("dev_sys_conjoon_org"), "INBOX"
        );

        $options = ["start" => 0, "limit" => 25, "sort" => [["property" => "date", "direction" => "DESC"]]];

        $messageItemList = new MessageItemList();
        $messageItemList[] = new ListMessageItem(
            new MessageKey($folderKey, "232"), [], new MessagePart("", "", "")
        );
        $messageItemList[] = new ListMessageItem(
            new MessageKey($folderKey, "233"), [], new MessagePart("", "", "")
        );

        $serviceStub->expects($this->once())
                   ->method('getMessageItemList')
                   ->with($folderKey, $options)
                   ->willReturn($messageItemList);

        $serviceStub->expects($this->once())
            ->method('getUnreadMessageCount')
            ->with($folderKey)
            ->willReturn($unreadCmp);

        $serviceStub->expects($this->once())
            ->method('getTotalMessageCount')
            ->with($folderKey)
            ->willReturn($totalCmp);


        $response = $this->actingAs($this->getTestUserStub())
                         ->call('GET', 'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems?start=0&limit=25');

        $this->assertEquals(200, $response->status());

        $this->seeJsonEquals([
            "success" => true,
            "total"   => $totalCmp,
            "meta"    => [
                "cn_unreadCount" => $unreadCmp,
                "mailAccountId" => $this->getTestMailAccount("dev_sys_conjoon_org")->getId(),
                "mailFolderId" => "INBOX"
            ],
            "data"    => $messageItemList->toJson()
          ]);
    }



    /**
     * Tests get() to make sure method returns the MessageBody of a Message
     *
     *
     * @return void
     */
    public function testGet_MessageBody_success()
    {
        $serviceStub = $this->initServiceStub();
        $this->initItemTransformerStub();
        $this->initBodyTransformerStub();

        $messageKey = new MessageKey($this->getTestMailAccount("dev_sys_conjoon_org"), "INBOX", "311");

        $messageBody = new MessageBody($messageKey);

        $serviceStub->expects($this->once())
            ->method('getMessageBody')
            ->with($messageKey)
            ->willReturn($messageBody);


        $response = $this->actingAs($this->getTestUserStub())
            ->call('GET', 'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems/311?target=MessageBody');

        $this->assertEquals(200, $response->status());

        $this->seeJsonEquals([
            "success" => true,
            "data"    => $messageBody->toJson()
        ]);
    }


    /**
     * Tests get() to make sure method returns the MessageItem of a Message
     *
     *
     * @return void
     */
    public function testGet_MessageItem_success()
    {
        $serviceStub = $this->initServiceStub();
        $this->initItemTransformerStub();
        $this->initBodyTransformerStub();

        $messageKey = new MessageKey($this->getTestMailAccount("dev_sys_conjoon_org"), "INBOX", "311");

        $messageItem = new MessageItem($messageKey);

        $serviceStub->expects($this->once())
            ->method('getMessageItem')
            ->with($messageKey)
            ->willReturn($messageItem);


        $response = $this->actingAs($this->getTestUserStub())
            ->call('GET', 'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems/311?target=MessageItem');

        $this->assertEquals(200, $response->status());

        $this->seeJsonEquals([
            "success" => true,
            "data"    => $messageItem->toJson()
        ]);
    }


    /**
     * Tests get() to make sure method relies on target-parameter
     *
     *
     * @return void
     */
    public function testGet_MessageItem_BadRequest()
    {
        $serviceStub = $this->initServiceStub();
        $this->initItemTransformerStub();
        $this->initBodyTransformerStub();

        $serviceStub->expects($this->never())
                    ->method('getMessageItem');


        $response = $this->actingAs($this->getTestUserStub())
            ->call('GET', 'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems/311');

        $this->assertEquals(400, $response->status());

        $this->seeJsonEquals([
            "success" => false,
            "msg"    => "\"target\" must be specified with either \"MessageBody\" or \"MessageItem\"."
        ]);
    }

    /**
     * Tests put() to make sure method relies on proper
     * request payload.
     *
     *
     * @return void
     */
    public function testPut_MessageItem_BadRequest_missingFlag()
    {
        $serviceStub = $this->initServiceStub();
        $this->initItemTransformerStub();
        $this->initBodyTransformerStub();

        $serviceStub->expects($this->never())
            ->method('setFlags');


        $response = $this->actingAs($this->getTestUserStub())
            ->call('PUT', 'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems/311?target=MessageItem');

        $this->assertEquals(400, $response->status());

        $this->seeJsonContains([
            "success" => false,
            "msg"    => "Invalid request payload."
        ]);
    }


    /**
     * Tests put() to make sure method relies on target-parameter.
     *
     *
     * @return void
     */
    public function testPut_MessageItem_BadRequest_missingTarget()
    {
        $serviceStub = $this->initServiceStub();
        $this->initItemTransformerStub();
        $this->initBodyTransformerStub();

        $serviceStub->expects($this->never())
            ->method('setFlags');

        $response = $this->actingAs($this->getTestUserStub())
            ->call('PUT', 'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems/311?target=MessageBody');

        $this->assertEquals(400, $response->status());

        $this->seeJsonContains([
            "success" => false,
            "msg"    => "\"target\" must be specified with \"MessageDraft\", \"MessageItem\" or \"MessageBodyDraft\"."
        ]);
    }


    /**
     * Tests put() to make sure setting flag works as expected
     *
     *
     * @return void
     */
    public function testPut_MessageItem_Flag()
    {
        $serviceStub = $this->initServiceStub();
        $this->initItemTransformerStub();
        $this->initBodyTransformerStub();

        $messageKey = new MessageKey($this->getTestMailAccount("dev_sys_conjoon_org"), "INBOX", "311");

        $flagList = new FlagList();
        $flagList[] = new SeenFlag(true);


        $serviceStub->expects($this->once())
            ->method('setFlags')
            ->with($messageKey, $flagList)
            ->willReturn(true);

        $response = $this->actingAs($this->getTestUserStub())
            ->put(
                'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems/311',
                ["target" => "MessageItem", "seen" => true]//,
            );

        $response->assertResponseOk();

        $response->seeJsonEquals([
            "success" => true,
            "data"    => array_merge($messageKey->toJson(), [
                "seen" => true
            ])
        ]);
    }


    /**
     * Tests put() to make sure setting flags (seen / flagged) works as expected
     *
     *
     * @return void
     */
    public function testPut_MessageItem_AllFlags()
    {
        $serviceStub = $this->initServiceStub();
        $this->initItemTransformerStub();
        $this->initBodyTransformerStub();

        $messageKey = new MessageKey($this->getTestMailAccount("dev_sys_conjoon_org"), "INBOX", "311");

        $flagList = new FlagList();
        $flagList[] = new SeenFlag(true);
        $flagList[] = new FlaggedFlag(false);


        $serviceStub->expects($this->once())
            ->method('setFlags')
            ->with($messageKey, $flagList)
            ->willReturn(true);

        $response = $this->actingAs($this->getTestUserStub())
            ->put(
                'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems/311',
                ["target" => "MessageItem", "seen" => true, "flagged" => false]//,
            );

        $response->assertResponseOk();

        $response->seeJsonEquals([
            "success" => true,
            "data"    => array_merge($messageKey->toJson(), [
                "seen"    => true,
                "flagged" => false
            ])
        ]);
    }


    /**
     * Tests put() with failed Service
     *
     *
     * @return void
     */
    public function testPut_MessageItem_ServiceFail()
    {
        $serviceStub = $this->initServiceStub();
        $this->initItemTransformerStub();
        $this->initBodyTransformerStub();

        $messageKey = new MessageKey($this->getTestMailAccount("dev_sys_conjoon_org"), "INBOX", "311");

        $flagList = new FlagList();
        $flagList[] = new FlaggedFlag(false);


        $serviceStub->expects($this->once())
            ->method('setFlags')
            ->with($messageKey, $flagList)
            ->willReturn(false);

        $response = $this->actingAs($this->getTestUserStub())
            ->put(
                'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems/311',
                ["target" => "MessageItem", "flagged" => false]//,
            );

        $response->assertResponseOk();

        $response->seeJsonEquals([
            "success" => false,
            "data"    => array_merge($messageKey->toJson(), [
                "flagged" => false
            ])
        ]);
    }


    /**
     * Tests post() to make sure creating a MessageBody works as expected
     *
     *
     * @return void
     */
    public function testPost_MessageBody()
    {
        $serviceStub = $this->initServiceStub();
        $this->initItemTransformerStub();
        $transformerStub = $this->initBodyTransformerStub();

        $messageKey = new MessageKey($this->getTestMailAccount("dev_sys_conjoon_org"), "INBOX", "311");
        $folderKey  = new FolderKey($this->getTestMailAccount("dev_sys_conjoon_org"), "INBOX");
        $textHtml   = "HTML";
        $textPlain  = "PLAIN";

        $data        = ["textHtml" => $textHtml, "textPlain" => $textPlain];
        $requestData = array_merge($data, ["target" => "MessageBodyDraft"]);

        $messageBody = new MessageBodyDraft();
        $messageBody->setTextHtml(new MessagePart($textHtml, "UTF-8", "text/html"));
        $messageBody->setTextPlain(new MessagePart($textPlain, "UTF-8", "text/plain"));

        $transformerStub->expects($this->once())
            ->method("transform")
            ->with($data)
            ->willReturn($messageBody);


        $serviceStub->expects($this->once())
            ->method('createMessageBodyDraft')
            ->with($folderKey, $messageBody)
            ->will($this->returnCallback(function($folderKey, $messageBody) use ($messageKey) {return $messageBody->setMessageKey($messageKey);}));

        $response = $this->actingAs($this->getTestUserStub())
            ->post(
                'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems',
                $requestData
            );

        $response->assertResponseOk();

        $response->seeJsonEquals([
            "success" => true,
            "data"    => array_merge($messageKey->toJson() ,$messageBody->toJson())
        ]);

    }


    /**
     * Tests post() with wrong target.
     *
     *
     * @return void
     */
    public function testPost_MessageBody_noMessageBody()
    {
        $serviceStub = $this->initServiceStub();
        $this->initItemTransformerStub();
        $this->initBodyTransformerStub();

        $serviceStub->expects($this->never())
            ->method('createMessageBodyDraft');

        $response = $this->actingAs($this->getTestUserStub())
            ->call('POST', 'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems');

        $this->assertEquals(400, $response->status());

        $this->seeJsonContains([
            "success" => false,
            "msg"    => "\"target\" must be specified with \"MessageBodyDraft\"."
        ]);

    }


    /**
     * Tests post() to make sure response is okay when no MessageBody as created.
     *
     * @return void
     */
    public function testPost_MessageBodyNotCreated()
    {
        $serviceStub = $this->initServiceStub();
        $this->initItemTransformerStub();
        $transformerStub = $this->initBodyTransformerStub();

        $folderKey  = new FolderKey($this->getTestMailAccount("dev_sys_conjoon_org"), "INBOX");
        $textHtml   = "HTML";
        $textPlain  = "PLAIN";

        $data        = ["textHtml" => $textHtml, "textPlain" => $textPlain];

        $messageBody = new MessageBodyDraft();
        $messageBody->setTextHtml(new MessagePart($textHtml, "UTF-8", "text/html"));
        $messageBody->setTextPlain(new MessagePart($textPlain, "UTF-8", "text/plain"));

        $transformerStub->expects($this->once())
            ->method("transform")
            ->with($data)
            ->willReturn($messageBody);

        $serviceStub->expects($this->once())
            ->method('createMessageBodyDraft')
            ->with($folderKey, $messageBody)
            ->willReturn(null);

        $response = $this->actingAs($this->getTestUserStub())
            ->call(
                'POST',
                'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems?target=MessageBodyDraft&textHtml=' .
                $textHtml . "&textPlain=" . $textPlain
            );

        $this->assertEquals(400, $response->status());

        $this->seeJsonContains([
            "success" => false,
            "msg"    => "Creating the MessageBody failed."
        ]);
    }


    /**
     * Tests put() to make sure setting MessageDraft-data works as expected
     *
     *
     * @return void
     */
    public function testPut_MessageDraft_data()
    {
        $serviceStub     = $this->initServiceStub();
        $transformerStub = $this->initItemTransformerStub();
        $this->initBodyTransformerStub();

        $messageKey = new MessageKey($this->getTestMailAccount("dev_sys_conjoon_org"), "INBOX", "311");
        $newMessageKey = new MessageKey($this->getTestMailAccount("dev_sys_conjoon_org"), "INBOX", "abc");

        $to = json_encode(["address" => "dev@conjoon.org"]);
        $data = [
            "subject" => "Hello World!",
            "to"      => $to
        ];

        $transformDraft = new MessageItemDraft($messageKey);
        $messageItemDraft = new MessageItemDraft($newMessageKey);

        $transformerStub->expects($this->once())
                        ->method("transform")
                        ->with($data)
                        ->willReturn($transformDraft);

        $serviceStub->expects($this->once())
            ->method('updateMessageDraft')
            ->with($transformDraft)
            ->willReturn($messageItemDraft);

        $response = $this->actingAs($this->getTestUserStub())
            ->put(
                'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems/311',
                ["target" => "MessageDraft", "subject" => "Hello World!", "to" => $to]//,
            );

        $response->assertResponseOk();

        $response->seeJsonEquals([
            "success" => true,
            "data"    => ArrayUtil::intersect($messageItemDraft->toJson(), ["subject", "to"])
        ]);
    }


    /**
     * Tests put() to make sure response is okay when no MessageDraft was created.
     *
     * @return void
     */
    public function testPut_MessageDraftNotCreated()
    {
        $serviceStub = $this->initServiceStub();
        $transformerStub = $this->initItemTransformerStub();
        $this->initBodyTransformerStub();

        $messageKey = new MessageKey($this->getTestMailAccount("dev_sys_conjoon_org"), "INBOX", "311");

        $transformDraft = new MessageItemDraft($messageKey);

        $to = json_encode(["address" => "dev@conjoon.org"]);
        $data = [
            "subject" => "Hello World!",
            "to"      => $to
        ];

        $transformerStub->expects($this->once())
            ->method("transform")
            ->with($data)
            ->willReturn($transformDraft);


        $serviceStub->expects($this->once())
            ->method('updateMessageDraft')
            ->with($transformDraft)
            ->willReturn(null);

        $response = $this->actingAs($this->getTestUserStub())
            ->put(
                'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems/311',
                ["target" => "MessageDraft", "subject" => "Hello World!", "to" => $to]//,
            );

        $response->assertResponseOk();

        $response->seeJsonEquals([
            "success" => false,
            "msg"     => "Updating the MessageDraft failed."
        ]);
    }


    /**
     * Tests put() to make sure response is okay when no MessageBodyDraft was created.
     *
     * @return void
     */
    public function testPut_MessageBodyDraftNotCreated()
    {
        $serviceStub = $this->initServiceStub();
        $this->initItemTransformerStub();
        $transformerStub = $this->initBodyTransformerStub();

        $messageKey = new MessageKey($this->getTestMailAccount("dev_sys_conjoon_org"), "INBOX", "311");

        $transformDraft = new MessageBodyDraft($messageKey);

        $data = [
            "textPlain" => "Hello World!"
        ];

        $transformerStub->expects($this->once())
            ->method("transform")
            ->with($data)
            ->willReturn($transformDraft);


        $serviceStub->expects($this->once())
            ->method('updateMessageBodyDraft')
            ->with($transformDraft)
            ->willReturn(null);

        $response = $this->actingAs($this->getTestUserStub())
            ->put(
                'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems/311',
                ["target" => "MessageBodyDraft", "textPlain" => "Hello World!"]//,
            );

        $response->assertResponseOk();

        $response->seeJsonEquals([
            "success" => false,
            "msg"     => "Updating the MessageBodyDraft failed."
        ]);
    }


    /**
     * Tests put() to make sure setting MessageBodyDraft-data works as expected
     *
     *
     * @return void
     */
    public function testPut_MessageBodyDraft_data()
    {
        $serviceStub     = $this->initServiceStub();
        $this->initItemTransformerStub();
        $transformerStub = $this->initBodyTransformerStub();

        $messageKey = new MessageKey($this->getTestMailAccount("dev_sys_conjoon_org"), "INBOX", "311");
        $newMessageKey = new MessageKey($this->getTestMailAccount("dev_sys_conjoon_org"), "INBOX", "abc");

        $data = [
            "textHtml" => "Hello World!"
        ];

        $transformDraft = new MessageBodyDraft($messageKey);
        $messageBodyDraft = new MessageBodyDraft($newMessageKey);

        $transformerStub->expects($this->once())
            ->method("transform")
            ->with($data)
            ->willReturn($transformDraft);

        $serviceStub->expects($this->once())
            ->method('updateMessageBodyDraft')
            ->with($transformDraft)
            ->willReturn($messageBodyDraft);

        $response = $this->actingAs($this->getTestUserStub())
            ->put(
                'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems/311',
                ["target" => "MessageBodyDraft", "textHtml" => "Hello World!"]//,
            );

        $response->assertResponseOk();

        $response->seeJsonEquals([
            "success" => true,
            "data"    => $messageBodyDraft->toJson()
        ]);
    }

// +--------------------------
// | Helper
// +--------------------------


    /**
     * @return mixed
     */
    protected function initItemTransformerStub() {

        $jsonStub = $this->getMockBuilder('Conjoon\Mail\Client\Request\Message\Transformer\DefaultMessageItemDraftJsonTransformer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->app->when(App\Http\Controllers\MessageItemController::class)
            ->needs(MessageItemDraftJsonTransformer::class)
            ->give(function () use ($jsonStub) {
                return $jsonStub;
            });

        return $jsonStub;
    }


    /**
     * @return mixed
     */
    protected function initBodyTransformerStub() {

        $jsonStub = $this->getMockBuilder('Conjoon\Mail\Client\Request\Message\Transformer\DefaultMessageBodyDraftJsonTransformer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->app->when(App\Http\Controllers\MessageItemController::class)
            ->needs(MessageBodyDraftJsonTransformer::class)
            ->give(function () use ($jsonStub) {
                return $jsonStub;
            });

        return $jsonStub;
    }


    /**
     * @return mixed
     */
    protected function initServiceStub() {

        $serviceStub = $this->getMockBuilder('Conjoon\Mail\Client\Service\DefaultMessageItemService')
            ->disableOriginalConstructor()
            ->getMock();

        $this->app->when(App\Http\Controllers\MessageItemController::class)
            ->needs(MessageItemService::class)
            ->give(function () use ($serviceStub) {
                return $serviceStub;
            });

        return $serviceStub;
    }

}
