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
    Conjoon\Mail\Client\Message\MessageItem,
    Conjoon\Mail\Client\Message\ListMessageItem;



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
