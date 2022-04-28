<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2022 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

namespace Tests\Conjoon\Horde\Mail\Client\Imap;

use Conjoon\Mail\Client\Attachment\FileAttachment;
use Conjoon\Mail\Client\Attachment\FileAttachmentList;
use Conjoon\Mail\Client\Data\CompoundKey\AttachmentKey;
use Conjoon\Mail\Client\Data\CompoundKey\MessageKey;
use Conjoon\Mail\Client\Message\Flag\DraftFlag;
use Conjoon\Mail\Client\Message\Flag\FlagList;
use Horde_Imap_Client_Socket;
use Horde_Mime_Headers;
use Horde_Mime_Part;
use Mockery;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Tests\TestCase;
use Tests\TestTrait;

/**
 * Class FilterTraitTest
 * @package Tests\Conjoon\Horde\Mail\Client\Imap
 */
class AttachmentTraitTest extends TestCase
{
    use TestTrait;
    use ClientGeneratorTrait;

    /**
     * Test getFileAttachmentList()
     *
     */
    public function testGetFileAttachmentList()
    {
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $mailFolderId = "INBOX";
        $messageItemId = "123";

        $messageKey = new MessageKey($account, $mailFolderId, $messageItemId);

        $parsedMessage = new Horde_Mime_Part();
        $parsedMessage[] = new Horde_Mime_Part();
        $parsedMessage[] = new Horde_Mime_Part();

        $name = 0;
        foreach ($parsedMessage as $part) {
            $part->setName("filename_" . $name++);
            $part->setDisposition("attachment");
        }
        $part = new Horde_Mime_Part();
        $part->setType("application/ms-tnef");
        $parsedMessage[] = $part;
        $parsedMessage->buildMimeIds();


        $trait = $this->getMockedTrait();
        $socket = $this->getMockedSocket();

        $trait->expects($this->once())
            ->method("connect")
            ->with(
                $messageKey
            )->willReturn($socket);

        $trait->expects($this->once())
            ->method("getFullMsg")
            ->with(
                $messageKey,
                $socket
            )->willReturn($trait->rawMsg);

        $trait->expects($this->once())
            ->method("parseMessage")
            ->with(
                $trait->rawMsg
            )->willReturn($parsedMessage);

        $fileAttachments = [
            new FileAttachment(
                new AttachmentKey($messageKey, "0"),
                ["content" => "", "encoding" => "", "text" => "", "type" => "", "size" => 0]
            ),
            new FileAttachment(
                new AttachmentKey($messageKey, "1"),
                ["content" => "", "encoding" => "", "text" => "", "type" => "", "size" => 0]
            )
        ];

        $trait->expects($this->exactly(2))
            ->method("buildAttachment")
            ->withConsecutive(
                [
                    $messageKey,
                    $parsedMessage[1],
                    $parsedMessage[1]->getName()
                ],
                [
                    $messageKey,
                    $parsedMessage[2],
                    $parsedMessage[2]->getName()
                ]
            )->willReturnOnConsecutiveCalls(
                $fileAttachments[0],
                $fileAttachments[1]
            );


        $list = $trait->getFileAttachmentList($messageKey);

        $this->assertSame($fileAttachments[0], $list[0]);
        $this->assertSame($fileAttachments[1], $list[1]);
    }


    /**
     * Test createAttachments
     */
    public function testCreateAttachments()
    {
        $mailAccountId = "dev";
        $mailFolderId = "INBOX";
        $messageItemId = "123";

        $messageKey = new MessageKey($mailAccountId, $mailFolderId, $messageItemId);

        $attachment = new FileAttachment([
            "type" => "text/plain",
            "text" => "no text",
            "size" => 7,
            "content" =>  base64_encode("no text"),
            "encoding" => "base64"
        ]);
        $fileAttachmentList = new FileAttachmentList();
        $fileAttachmentList[] = $attachment;

        $trait = $this->getMockedTrait();
        $composer = $this->getMockedComposer();
        $socket = $this->getMockedSocket();

        $trait->expects($this->once())
            ->method("connect")
            ->willReturn($socket);

        $trait->expects($this->once())
            ->method("getFullMsg")
            ->with(
                $messageKey,
                $this->anything()
            )->willReturn($trait->rawMsg);

        $trait->expects($this->once())
            ->method("getAttachmentComposer")
            ->willReturn($composer);

        $composer->expects($this->once())
                ->method("compose")
                ->with(
                    $trait->rawMsg,
                    $fileAttachmentList
                )->willReturn($trait->rawMsg);

        $obj = new stdClass();
        $obj->ids = ["321"];
        $socket->expects($this->once())
            ->method("append")
            ->with("INBOX", [["data" => $trait->rawMsg]])
            ->willReturn($obj);

        $flagList = new FlagList();
        $flagList[] = new DraftFlag(true);
        $trait->expects($this->once())
            ->method("setFlags")
            ->with(
                new MessageKey($mailAccountId, $mailFolderId, "321"),
                $flagList
            );

        $trait->expects($this->once())
            ->method("deleteMessage")
            ->with($messageKey);

        $list = $trait->createAttachments($messageKey, $fileAttachmentList);
        $this->assertSame(1, count($list));
        $this->assertSame("text/plain", $list[0]->getType());
        $this->assertSame("no text", $list[0]->getText());
        $this->assertSame(7, $list[0]->getSize());
        $this->assertSame(base64_encode("no text"), $list[0]->getContent());
        $this->assertSame("base64", $list[0]->getEncoding());
    }


    /**
     * Test deleteAttachment
     */
    public function testDeleteAttachment()
    {
        $account = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");

        $mailFolderId = "INBOX";
        $messageItemId = "123";

        $messageKey = new MessageKey($account, $mailFolderId, $messageItemId);
        $attachmentKey = new AttachmentKey($messageKey, "1");

        // parsed message
        $parsedMessage = new Horde_Mime_Part();
        $parsedMessage[] = new Horde_Mime_Part();
        $parsedMessage[] = new Horde_Mime_Part();

        $name = 0;
        foreach ($parsedMessage as $part) {
            $part->setName("filename_" . $name++);
            $part->setDisposition("attachment");
        }
        $parsedMessage->buildMimeIds();

        // parsed header
        $parsedHeaders = new Horde_Mime_Headers();

        $fileAttachments = [
            new FileAttachment(
                new AttachmentKey($messageKey, "0"),
                ["content" => "", "encoding" => "", "text" => "", "type" => "", "size" => 0]
            ),
            new FileAttachment(
                $attachmentKey,
                ["content" => "", "encoding" => "", "text" => "", "type" => "", "size" => 0]
            )
        ];


        $trait = $this->getMockedTrait();
        $socket = $this->getMockedSocket();

        $trait->expects($this->once())
            ->method("connect")
            ->with(
                $messageKey
            )->willReturn($socket);

        $trait->expects($this->once())
            ->method("getFullMsg")
            ->with(
                $messageKey,
                $socket
            )->willReturn($trait->rawMsg);

        $trait->expects($this->once())
            ->method("parseMessage")
            ->with(
                $trait->rawMsg
            )->willReturn($parsedMessage);

        $trait->expects($this->once())
            ->method("parseHeaders")
            ->with(
                $trait->rawMsg
            )->willReturn($parsedHeaders);

        $basePartMock = new Horde_Mime_Part();
        $basePartMock->setType('multipart/mixed');
        $basePartMock->isBasePart(true);
        $basePartMock->setContentTypeParameter("boundary", "forTesting");

        $trait->expects($this->once())
            ->method("createBasePart")
            ->willReturn($basePartMock);

        $trait->expects($this->exactly(2))
            ->method("buildAttachment")
            ->withConsecutive(
                [
                    $messageKey,
                    $parsedMessage[1],
                    $parsedMessage[1]->getName()
                ],
                [
                    $messageKey,
                    $parsedMessage[2],
                    $parsedMessage[2]->getName()
                ]
            )->willReturnOnConsecutiveCalls(
                $fileAttachments[0],
                $fileAttachments[1]
            );

        $basePart = new Horde_Mime_Part();
        $basePart->setType('multipart/mixed');
        $basePart->isBasePart(true);
        $basePart->setContentTypeParameter("boundary", "forTesting");

        $parsedHeaders = $basePart->addMimeHeaders(["headers" => $parsedHeaders]);

        $basePart[] = $parsedMessage[1];

        $newRawMessage = trim($parsedHeaders->toString())  .
            "\n\n" .
            trim($basePart->toString());

        $newId = "321";
        $obj = new stdClass();
        $obj->ids = [$newId];
        $socket->expects($this->once())
            ->method("append")
            ->with("INBOX", [["data" => $newRawMessage]])
            ->willReturn($obj);

        $newMessageKey = new MessageKey($account, $mailFolderId, $newId);

        $flagList = new FlagList();
        $flagList[] = new DraftFlag(true);

        $trait
            ->expects($this->once())
            ->method("setFlags")
            ->with($newMessageKey, $flagList);

        $result = $trait->deleteAttachment($attachmentKey);

        $this->assertEquals($newMessageKey->toJson(), $result->toJson());
    }


    /**
     * @return MockObject|AttachmentTraitForTesting
     */
    protected function getMockedTrait()
    {
        return $this->getMockBuilder(AttachmentTraitForTesting::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                "setFlags",
                "parseMessage",
                "parseHeaders",
                "deleteMessage",
                "createBasePart",
                "getFullMsg",
                "connect",
                "getAttachmentComposer",
                "buildAttachment"
            ])
            ->getMock();
    }


    /**
     * @return MockObject|SocketForTesting
     */
    public function getMockedSocket()
    {
        return $this->getMockBuilder(SocketForTesting::class)
            ->disableOriginalConstructor()
            ->onlyMethods(["connect", "append"])
            ->getMock();
    }


    /**
     * @return MockObject|AttachmentComposerForTesting
     */
    public function getMockedComposer()
    {
        return $this->getMockBuilder(AttachmentComposerForTesting::class)
            ->disableOriginalConstructor()
            ->onlyMethods(["compose"])
            ->getMock();
    }
}
