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


class MessageItemControllerTest extends TestCase
{
    use TestTrait;


    /**
     * Tests get() to make sure method returns list of available ImapAccounts associated with
     * the current signed in user.
     *
     * @return void
     */
    public function testIndex_exception()
    {

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        $this->actingAs($this->getTestUserStub())
             ->call('GET', 'cn_mail/MailAccounts/foo/MailFolders/INBOX/MessageItems');
    }


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


        $serviceStub->expects($this->once())
                   ->method('getMessageItemsFor')
                   ->with($this->getTestImapAccount(), "INBOX", [
                       "start" => 0, "limit" => 25, "sort" => [["property" => "date", "direction" => "DESC"]]
                   ])
                   ->willReturn([
                       "total" => 0,
                       "data" => [],
                       "meta" => ["cn_unreadCount" => 3, "mailAccountId" => $this->getTestImapAccount()->getId(), "mailFolderId" => "INBOX"]
                   ]);


        $response = $this->actingAs($this->getTestUserStub())
                         ->call('GET', 'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems?start=0&limit=25');

        $this->assertEquals(200, $response->status());

        $this->seeJsonEquals([
            "success" => true,
            "total"   => 0,
            "meta" => ["cn_unreadCount" => 3, "mailAccountId" => $this->getTestImapAccount()->getId(), "mailFolderId" => "INBOX"],
            "data"    => []
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

        $serviceStub->expects($this->once())
            ->method('getMessageBodyFor')
            ->with($this->getTestImapAccount(), "INBOX", "311")
            ->willReturn([
                "mailAccountId" => $this->getTestImapAccount()->getId(),
                "mailFolderId" => "INBOX",
                "id"           => "311",
                "textHtml"     => "foo",
                "textPlain"    => "bar"
             ]);


        $response = $this->actingAs($this->getTestUserStub())
            ->call('GET', 'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems/311?target=MessageBody');

        $this->assertEquals(200, $response->status());

        $this->seeJsonEquals([
            "success" => true,
            "data"    => [
                "mailAccountId" => $this->getTestImapAccount()->getId(),
                "mailFolderId" => "INBOX",
                "id"           => "311",
                "textHtml"     => "foo",
                "textPlain"    => "bar"
            ]
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

        $serviceStub->expects($this->once())
            ->method('getMessageItemFor')
            ->with($this->getTestImapAccount(), "INBOX", "311")
            ->willReturn([
                "dummy"
            ]);


        $response = $this->actingAs($this->getTestUserStub())
            ->call('GET', 'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems/311?target=MessageItem');

        $this->assertEquals(200, $response->status());

        $this->seeJsonEquals([
            "success" => true,
            "data"    => [
                "dummy"
            ]
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
                    ->method('getMessageItemFor');


        $response = $this->actingAs($this->getTestUserStub())
            ->call('GET', 'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders/INBOX/MessageItems/311');

        $this->assertEquals(400, $response->status());

        $this->seeJsonEquals([
            "success" => false,
            "msg"    => "\"target\" must be specified with either \"MessageBody\" or \"MessageItem\"."
        ]);
    }


    protected function initServiceStub() {

        $serviceStub = $this->getMockBuilder('App\Imap\Service\DefaultMessageItemService')
            ->disableOriginalConstructor()
            ->getMock();

        $this->app->when(App\Http\Controllers\MessageItemController::class)
            ->needs(App\Imap\Service\MessageItemService::class)
            ->give(function () use ($serviceStub) {
                return $serviceStub;
            });

        return $serviceStub;

    }

}
