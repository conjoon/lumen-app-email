<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2019-2022 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

use Conjoon\Mail\Client\Attachment\FileAttachment;
use Conjoon\Mail\Client\Attachment\FileAttachmentItem;
use Conjoon\Mail\Client\Attachment\FileAttachmentList;
use Conjoon\Mail\Client\Attachment\Processor\FileAttachmentProcessor;
use Conjoon\Mail\Client\Data\CompoundKey\AttachmentKey;
use Conjoon\Mail\Client\Data\CompoundKey\MessageKey;
use Conjoon\Mail\Client\MailClient;
use Conjoon\Mail\Client\Service\AttachmentService;
use Conjoon\Mail\Client\Service\DefaultAttachmentService;
use Mockery;
use Tests\TestCase;
use Tests\TestTrait;

class DefaultAttachmentServiceTest extends TestCase
{
    use TestTrait;


    /**
     * Test the instance
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInstance()
    {
        $service = $this->createService();
        $this->assertInstanceOf(AttachmentService::class, $service);
    }


    /**
     * Test getFileAttachmentItemList()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @noinspection PhpUndefinedMethodInspection
     */
    public function testGetFileAttachmentItemList()
    {

        $mailAccount = $this->getTestMailAccount("dev");
        $messageKey = new MessageKey($mailAccount, "INBOX", "123");
        $service = $this->createService();

        $fileAttachmentList = new FileAttachmentList();
        $fileAttachmentList[] = new FileAttachment(
            new AttachmentKey($messageKey, "1"),
            [
                "size" => 0,
                "text" => "file",
                "type" => "text/html",
                "content" => "CONTENT",
                "encoding" => "base64"
            ]
        );

        $service->getMailClient()
                ->shouldReceive("getFileAttachmentList")
                ->with($messageKey)
                ->andReturn($fileAttachmentList);

        $result = $service->getFileAttachmentItemList($messageKey);

        $this->isInstanceOf(FileAttachmentItemList::class, $result);

        $this->assertSame(1, count($result));
        $this->assertSame("mockFile", $result[0]->getText());
    }


    /**
     * Test createAttachments()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @noinspection PhpUndefinedMethodInspection
     */
    public function testCreateAttachments()
    {

        $mailAccount = $this->getTestMailAccount("dev");
        $messageKey = new MessageKey($mailAccount, "INBOX", "123");
        $service = $this->createService();

        $fileAttachmentListClient = new FileAttachmentList();
        $fileAttachmentListClient[] = new FileAttachment(
            [
                "size" => 0,
                "text" => "file",
                "type" => "text/html",
                "content" => "CONTENT",
                "encoding" => "base64"
            ]
        );

        $fileAttachmentListServer = new FileAttachmentList();
        $fileAttachmentListServer[] = new FileAttachment(
            new AttachmentKey($messageKey, "1"),
            [
                "size" => 0,
                "text" => "file",
                "type" => "text/html",
                "content" => "CONTENT",
                "encoding" => "base64"
            ]
        );


        $service->getMailClient()
            ->shouldReceive("createAttachments")
            ->with($messageKey, $fileAttachmentListClient)
            ->andReturn($fileAttachmentListServer);

        $result = $service->createAttachments($messageKey, $fileAttachmentListClient);
        $this->isInstanceOf(FileAttachmentItemList::class, $result);

        $this->assertSame(1, count($result));
        $this->assertSame("mockFile", $result[0]->getText());
    }


// +--------------------------------------
// | Helper
// +--------------------------------------
    /**
     * Helper function for creating the service.
     * @return DefaultAttachmentService
     */
    protected function createService(): DefaultAttachmentService
    {
        return new DefaultAttachmentService(
            $this->getMailClientMock(),
            $this->getFileAttachmentProcessorMock()
        );
    }


    /**
     * Helper function for creating the client Mock.
     * @return mixed
     */
    protected function getMailClientMock()
    {

        return Mockery::mock("overload:" . MailClient::class);
    }


    /**
     * Helper function for creating the FileAttachmentProcessor-Mock.
     * @return mixed
     */
    protected function getFileAttachmentProcessorMock(): FileAttachmentProcessor
    {

        return new class () implements FileAttachmentProcessor {

            public function process(FileAttachment $fileAttachment): FileAttachmentItem
            {
                return new FileAttachmentItem(
                    $fileAttachment->getAttachmentKey(),
                    [
                        "size" => 0,
                        "type" => "text/plain",
                        "text" => "mockFile",
                        "downloadUrl" => "",
                        "previewImgSrc" => ""
                    ]
                );
            }
        };
    }
}
