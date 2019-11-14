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



class VariousTest extends TestCase
{

    use TestTrait;

    /**
     * Get information and validate registered middleware for the app.
     *
     * @return void
     */
    public function testMiddleware() {
        $reflection = new \ReflectionClass($this->app);
        $property = $reflection->getMethod('gatherMiddlewareClassNames');
        $property->setAccessible(true);
        $ret = $property->invokeArgs($this->app, ['auth']);

        $this->assertSame($ret[0], "App\Http\Middleware\Authenticate");
    }


    public function testRoutes() {

        $routes = $this->app->router->getRoutes();

        $this->assertArrayHasKey("POST/cn_imapuser/auth", $routes);


        $testAuthsFor = [
            "GET/cn_mail/MailAccounts",
            "GET/cn_mail/MailAccounts/{mailAccountId}/MailFolders",
            "GET/cn_mail/MailAccounts/{mailAccountId}/MailFolders/{mailFolderId:.*}/MessageItems",
            "POST/cn_mail/MailAccounts/{mailAccountId}/MailFolders/{mailFolderId:.*}/MessageItems",
            "GET/cn_mail/MailAccounts/{mailAccountId}/MailFolders/{mailFolderId:.*}/MessageItems/{messageItemId}",
            "PUT/cn_mail/MailAccounts/{mailAccountId}/MailFolders/{mailFolderId:.*}/MessageItems/{messageItemId}",
            "GET/cn_mail/MailAccounts/{mailAccountId}/MailFolders/{mailFolderId:.*}/MessageItems/{messageItemId}/Attachments"
        ];

        foreach ($testAuthsFor as $route) {
            $this->assertArrayHasKey($route, $routes);
            $this->assertSame("auth", $routes[$route]["action"]["middleware"][0]);
        }

    }


    public function testConcretes() {

        $userStub = $this->getTemplateUserStub(['getMailAccount']);
        $userStub->method('getMailAccount')
            ->with(null)
            ->willReturn($this->getTestMailAccount("dev_sys_conjoon_org"));
        $this->app->auth->setUser($userStub);



        $reflection = new \ReflectionClass($this->app);
        $property = $reflection->getMethod('getConcrete');
        $property->setAccessible(true);


        $this->assertInstanceOf(
            \App\Imap\DefaultImapUserRepository::class,
            $this->app->build($property->invokeArgs($this->app, ['App\Imap\ImapUserRepository']))
        );

        $attachmentService = $this->app->build($property->invokeArgs($this->app, ['Conjoon\Mail\Client\Service\AttachmentService']));
        $attachmentServiceMailClient = $attachmentService->getMailClient();
        $this->assertInstanceOf(
            \Conjoon\Mail\Client\Service\DefaultAttachmentService::class,
            $attachmentService
        );
        $this->assertInstanceOf(
            \Conjoon\Mail\Client\Attachment\Processor\InlineDataProcessor::class,
            $attachmentService->getFileAttachmentProcessor()
        );

        $mailFolderService = $this->app->build($property->invokeArgs($this->app, ['Conjoon\Mail\Client\Service\MailFolderService']));
        $mailFolderServiceMailClient = $mailFolderService->getMailClient();
        $mailFolderTreeBuilder = $mailFolderService->getMailFolderTreeBuilder();
        $folderIdToTypeMapper = $mailFolderTreeBuilder->getFolderIdToTypeMapper();
        $this->assertInstanceOf(
            \Conjoon\Mail\Client\Service\DefaultMailFolderService::class,
            $mailFolderService
        );
        $this->assertInstanceOf(\Conjoon\Mail\Client\Imap\HordeClient::class, $mailFolderServiceMailClient);
        $this->assertInstanceOf(\Conjoon\Mail\Client\Folder\Tree\DefaultMailFolderTreeBuilder::class, $mailFolderTreeBuilder);
        $this->assertInstanceOf(\Conjoon\Mail\Client\Imap\Util\DefaultFolderIdToTypeMapper::class, $folderIdToTypeMapper);

        // sharing the same client
        $this->assertSame($attachmentServiceMailClient, $mailFolderServiceMailClient);

        $messageItemService = $this->app->build($property->invokeArgs($this->app, ['Conjoon\Mail\Client\Service\MessageItemService']));
        $this->assertInstanceOf(
            \Conjoon\Mail\Client\Service\DefaultMessageItemService::class,
            $messageItemService
        );

        $messageItemServiceMailClient = $messageItemService->getMailClient();

        // sharing the same client
        $this->assertSame($messageItemServiceMailClient, $mailFolderServiceMailClient);
        $this->assertInstanceOf(\Conjoon\Mail\Client\Imap\HordeClient::class, $messageItemServiceMailClient);

        $this->assertInstanceOf(\Conjoon\Mail\Client\Message\Text\DefaultMessageItemFieldsProcessor::class, $messageItemService->getMessageItemFieldsProcessor());
        $this->assertInstanceOf(\Conjoon\Mail\Client\Reader\ReadableMessagePartContentProcessor::class, $messageItemService->getReadableMessagePartContentProcessor());
        $this->assertInstanceOf(\Conjoon\Mail\Client\Writer\WritableMessagePartContentProcessor::class, $messageItemService->getWritableMessagePartContentProcessor());
        $this->assertInstanceOf(\Conjoon\Mail\Client\Message\Text\DefaultPreviewTextProcessor::class, $messageItemService->getPreviewTextProcessor());


    }
}
