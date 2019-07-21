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
    public function testGetAttachmentsFor()
    {

        $account = $this->getTestUserStub()->getImapAccount();

        $imapStub = \Mockery::mock('overload:' . \Horde_Imap_Client_Socket::class);

        $fetchResults = new \Horde_Imap_Client_Fetch_Results();
        $fetchResults[123] = new \Horde_Imap_Client_Data_Fetch();
        $fetchResults[123]->setUid(123);

        $imapStub->shouldReceive('fetch')->with("INBOX", \Mockery::any(), [
            "ids" => new \Horde_Imap_Client_Ids("123")
        ])->andReturn($fetchResults);


        $mockService = $this->getMockBuilder(DefaultAttachmentService::class)
            ->setMethods(["isFileAttachment"])
            ->getMock();
        $mockService->method("isFileAttachment")->willReturn(true);

        $attachments = $mockService->getAttachmentsFor($account, "INBOX", "123");


        $this->assertSame($attachments, [[
            "id" => "1",
            "mailAccountId" => $account->getId(),
            "mailFolderId" => "INBOX",
            "parentMessageItemId" => "123",
            "type" => "application/octet-stream",
            "text" => "",
            "size" => 0,
            "downloadUrl" => "data:application/octet-stream;base64,",
            "previewImgSrc" => NULL
        ]]);

    }

}