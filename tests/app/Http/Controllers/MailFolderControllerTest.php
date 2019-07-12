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


class MailFolderControllerTest extends TestCase
{
    use TestTrait;


    /**
     * Tests get() to make sure method returns list of available ImapAccounts associated with
     * the current signed in user.
     *
     * @return void
     */
    public function testGet_exception()
    {

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        $this->actingAs($this->getTestUserStub())
             ->call('GET', 'cn_mail/MailAccounts/foobar/MailFolders');
    }


    /**
     * Tests get() to make sure method returns list of available MailFolders associated with
     * the current signed in user.
     *
     *
     * @return void
     */
    public function testGet_success()
    {
        $repository = $this->getMockBuilder('App\Imap\Service\DefaultMailFolderService')
                           ->disableOriginalConstructor()
                           ->getMock();

        $this->app->when(App\Http\Controllers\MailFolderController::class)
            ->needs(App\Imap\Service\MailFolderService::class)
            ->give(function () use ($repository) {
                return $repository;
            });

        $repository->expects($this->once())
                   ->method('getMailFoldersFor')
                   ->with($this->getTestImapAccount())
                   ->willReturn(['testArray']);


        $response = $this->actingAs($this->getTestUserStub())
                         ->call('GET', 'cn_mail/MailAccounts/dev_sys_conjoon_org/MailFolders');

        $this->assertEquals(200, $response->status());

        $this->seeJsonEquals([
            "success" => true,
            "data"    => ['testArray']
          ]);
    }


}
