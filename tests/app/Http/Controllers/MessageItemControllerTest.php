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
    Conjoon\Mail\Client\Message\MessageItem,
    Conjoon\Mail\Client\Message\ListMessageItem,
    Conjoon\Mail\Client\Message\Flag\FlagList,
    Conjoon\Mail\Client\Message\Flag\SeenFlag,
    Conjoon\Mail\Client\Message\Flag\FlaggedFlag;



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

        $serviceStub->expects($this->never())
            ->method('setFlags');

        $response = $this->actingAs($this->getTestUserStub())
            ->call('PUT', 'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems/311?target=MessageBody');

        $this->assertEquals(400, $response->status());

        $this->seeJsonContains([
            "success" => false,
            "msg"    => "\"target\" must be specified with \"MessageItem\"."
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

        $messageKey = new MessageKey($this->getTestMailAccount("dev_sys_conjoon_org"), "INBOX", "311");
        $folderKey  = new FolderKey($this->getTestMailAccount("dev_sys_conjoon_org"), "INBOX");
        $textHtml   = "HTML";
        $textPlain  = "PLAIN";
        $messageBody = new MessageBody($messageKey);
        $messageBody->setTextHtml(new MessagePart($textHtml, "UTF-8", "text/html"));
        $messageBody->setTextPlain(new MessagePart($textPlain, "UTF-8", "text/plain"));

        $serviceStub->expects($this->once())
            ->method('createMessageBody')
            ->with($folderKey, $textPlain, $textHtml)
            ->willReturn($messageBody);

        $response = $this->actingAs($this->getTestUserStub())
            ->post(
                'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems',
                ["target" => "MessageBody", "textHtml" => $textHtml, "textPlain" => $textPlain]
            );

        $response->assertResponseOk();

        $response->seeJsonEquals([
            "success" => true,
            "data"    => $messageBody->getMessageKey()->toJson()
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

        $serviceStub->expects($this->never())
            ->method('createMessageBody');

        $response = $this->actingAs($this->getTestUserStub())
            ->call('POST', 'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems');

        $this->assertEquals(400, $response->status());

        $this->seeJsonContains([
            "success" => false,
            "msg"    => "\"target\" must be specified with \"MessageBody\"."
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

        $folderKey  = new FolderKey($this->getTestMailAccount("dev_sys_conjoon_org"), "INBOX");
        $textHtml   = "HTML";
        $textPlain  = "PLAIN";

        $serviceStub->expects($this->once())
            ->method('createMessageBody')
            ->with($folderKey, $textPlain, $textHtml)
            ->willReturn(null);

        $response = $this->actingAs($this->getTestUserStub())
            ->call(
                'POST',
                'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems?target=MessageBody&textHtml=' .
                $textHtml . "&textPlain=" . $textPlain
            );

        $this->assertEquals(400, $response->status());

        $this->seeJsonContains([
            "success" => false,
            "msg"    => "Creating the MessageBody failed."
        ]);
    }


// +--------------------------
// | Helper
// +--------------------------

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
