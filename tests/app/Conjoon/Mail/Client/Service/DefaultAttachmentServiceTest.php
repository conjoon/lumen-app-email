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

use Conjoon\Mail\Client\Service\DefaultAttachmentService,
    Conjoon\Mail\Client\Service\AttachmentService,
    Conjoon\Mail\Client\Data\CompoundKey\MessageKey,
    Conjoon\Mail\Client\Data\CompoundKey\AttachmentKey,
    Conjoon\Mail\Client\Attachment\FileAttachmentList,
    Conjoon\Mail\Client\Attachment\FileAttachmentItemList,
    Conjoon\Mail\Client\Attachment\FileAttachment,
    Conjoon\Mail\Client\Attachment\FileAttachmentItem,
    Conjoon\Mail\Client\Attachment\Processor\FileAttachmentProcessor,
    Conjoon\Mail\Client\MailClient;


class DefaultAttachmentServiceTest extends TestCase {

    use TestTrait;


    /**
     * Test the instance
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInstance() {

        $service = $this->createService();
        $this->assertInstanceOf(AttachmentService::class, $service);
    }


    /**
     * Test getFileAttachmentItemList()
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetFileAttachmentItemList() {

        $mailAccount = $this->getTestMailAccount("dev");
        $messageKey = new MessageKey($mailAccount, "INBOX", "123");
        $service = $this->createService();

        $fileAttachmentList = new FileAttachmentList();
        $fileAttachmentList[] = new FileAttachment(
            new AttachmentKey($messageKey, "1"),
            [
                "size"     => 0,
                "text"     => "file",
                "type"     => "text/html",
                "content"  => "CONTENT",
                "encoding" => "base64"
            ]
        );

        $service->getMailClient()
            ->shouldReceive('getFileAttachmentList')
            ->with($messageKey)
            ->andReturn($fileAttachmentList);

        $result = $service->getFileAttachmentItemList($messageKey);

        $this->assertSame(1, count($result));
        $this->assertSame("mockfile", $result[0]->getText());
    }


// +--------------------------------------
// | Helper
// +--------------------------------------
    /**
     * Helper function for creating the service.
     * @return DefaultAttachmentService
     */
    protected function createService() {
        return new DefaultAttachmentService(
            $this->getMailClientMock(),
            $this->getFileAttachmentProcessorMock()
        );
    }


    /**
     * Helper function for creating the client Mock.
     * @return mixed
     */
    protected function getMailClientMock() {

        return \Mockery::mock('overload:'.MailClient::class);

    }


    /**
     * Helper function for creating the FileAttachmentProcessor-Mock.
     * @return mixed
     */
    protected function getFileAttachmentProcessorMock() :FileAttachmentProcessor{

       return new class() implements FileAttachmentProcessor {

            public $def;

            public function process(FileAttachment $fileAttachment) :FileAttachmentItem {


                return new FileAttachmentItem(
                    $fileAttachment->getAttachmentKey(),
                    [
                        "size"          => 0,
                        "type"          => "text/plain",
                        "text"          => "mockfile",
                        "downloadUrl"   => "",
                        "previewImgSrc" => ""
                    ]
                );
            }
        };

    }

}