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

namespace Test\App\Http\V0\Controllers;

use Conjoon\Mail\Client\Folder\MailFolderChildList,
    Conjoon\Mail\Client\Folder\MailFolder,
    Conjoon\Mail\Client\Data\CompoundKey\FolderKey;

class MailFolderControllerTest extends TestCase
{
    use TestTrait;


    /**
     * Tests get() to make sure method returns list of available MailFolders associated with
     * the current signed in user.
     *
     *
     * @return void
     */
    public function testIndex_success()
    {
        $service = $this->getMockBuilder("Conjoon\Mail\Client\Service\DefaultMailFolderService")
                           ->disableOriginalConstructor()
                           ->getMock();

        $this->app->when(App\Http\Controllers\MailFolderController::class)
            ->needs(Conjoon\Mail\Client\Service\MailFolderService::class)
            ->give(function () use ($service) {
                return $service;
            });

        $resultList   = new MailFolderChildList;
        $resultList[] = new MailFolder(
            new FolderKey("dev", "INBOX"),
            ["name"        => "INBOX",
             "unreadCount" => 2,
             "folderType"  => MailFolder::TYPE_INBOX]
        );

        $service->expects($this->once())
                   ->method("getMailFolderChildList")
                   ->with($this->getTestMailAccount("dev_sys_conjoon_org"))
                   ->willReturn($resultList);


        $response = $this->actingAs($this->getTestUserStub())
                         ->call("GET", "cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders");

        $this->assertEquals(200, $response->status());

        $this->seeJsonEquals([
            "success" => true,
            "data"    => $resultList->toJson()
          ]);
    }


}
