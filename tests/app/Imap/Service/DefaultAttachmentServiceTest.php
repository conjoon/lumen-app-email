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

use App\Imap\Service\DefaultAttachmentService,
    App\Imap\Service\AttachmentServiceException;


class DefaultAttachmentServiceTest extends TestCase {

    use TestTrait;


    public function testInstance() {

        $service = new DefaultAttachmentService();
        $this->assertInstanceOf(\App\Imap\Service\AttachmentService::class, $service);
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetAttachmentsFor_exception() {

        $this->expectException(AttachmentServiceException::class);

        $imapStub = \Mockery::mock('overload:'.\Horde_Imap_Client_Socket::class);

        $imapStub->shouldReceive('listMailboxes')
                 ->andThrow(new \Exception("This exception should be caught properly by the test"));

        $service = new DefaultAttachmentService();
        $service->getAttachmentsFor($this->getTestUserStub()->getImapAccount(), "1", "1");
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetAttachmentsFor_buildAttachmentsIsCalled() {
        $account = $this->getTestUserStub()->getImapAccount();

        $mockService = $this->setupMocks(
            $account, "INBOX", "123", ["isFileAttachment", 'buildAttachment']);

        $mockService->expects($this->once())
                    ->method('buildAttachment');

        $mockService->getAttachmentsFor($account, "INBOX", "123");
    }


    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testGetAttachmentsFor()
    {
        $account = $this->getTestUserStub()->getImapAccount();

        $mockService = $this->setupMocks(
            $account, "INBOX", "123", ["isFileAttachment"]);


        $attachments = $mockService->getAttachmentsFor($account, "INBOX", "123");

        $this->assertSame(count($attachments), 1);

        $structure = [
            "id", "mailAccountId", "mailFolderId", "parentMessageItemId",
            "type", "text", "size",
            "downloadUrl", "previewImgSrc"
        ];

        $i = 0;
        foreach ($attachments as $attachment) {
            foreach ($structure as $key) {
                $i++;
                $this->assertArrayHasKey($key, $attachment);
            }
        }

        $this->assertSame($i, count($structure));
    }

    
    protected function setupMocks($account, $mailFolderId, $messageItemId, array $methods) {

        $imapStub = \Mockery::mock('overload:' . \Horde_Imap_Client_Socket::class);

        $fetchResults = new \Horde_Imap_Client_Fetch_Results();
        $fetchResults[$messageItemId] = new \Horde_Imap_Client_Data_Fetch();
        $fetchResults[$messageItemId]->setUid($messageItemId);

        $imapStub->shouldReceive('fetch')->with($mailFolderId, \Mockery::any(), [
            "ids" => new \Horde_Imap_Client_Ids($messageItemId)
        ])->andReturn($fetchResults);


        $mockService = $this->getMockBuilder(DefaultAttachmentService::class)
            ->setMethods($methods)
            ->getMock();
        $mockService->method("isFileAttachment")->willReturn(true);

        return $mockService;
    }


}