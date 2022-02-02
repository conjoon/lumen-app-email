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

namespace Conjoon\Horde\Mail\Client\Imap;

use Conjoon\Mail\Client\Attachment\FileAttachment;
use Conjoon\Mail\Client\Attachment\FileAttachmentList;
use Conjoon\Mail\Client\Data\CompoundKey\AttachmentKey;
use Conjoon\Mail\Client\Data\CompoundKey\MessageKey;
use Conjoon\Mail\Client\Imap\ImapClientException;
use Conjoon\Mail\Client\Message\Flag\DraftFlag;
use Conjoon\Mail\Client\Message\Flag\FlagList;
use Exception;
use Horde_Imap_Client_Fetch_Query;
use Horde_Imap_Client_Ids;
use Horde_Mime_Exception;
use Horde_Mime_Headers;
use Horde_Mime_Part;
use RuntimeException;

/**
 * Helper-Trait for attachment operations
 *
 * Trait AttachmentTrait
 * @package Conjoon\Horde\Mail\Client\Imap
 */
trait AttachmentTrait
{

    /**
     * Builds an attachment from the specified data.
     *
     * @param MessageKey $key
     * @param Horde_Mime_Part $part
     * @param string $fileName
     * @return FileAttachment
     */
    protected function buildAttachment(
        MessageKey $key,
        Horde_Mime_Part $part,
        string $fileName
    ): FileAttachment {

        $mimeType = $part->getType();

        $attachment = new FileAttachment([
            "type" => $mimeType,
            "text" => $fileName,
            "size" => $part->getBytes(),
            "content" =>  base64_encode($part->getContents()),
            "encoding" => "base64"
        ]);

        return $attachment->setAttachmentKey(new AttachmentKey($key, $this->generateAttachmentId($attachment)));
    }


    /**
     * @inheritdoc
     */
    public function createAttachments(MessageKey $messageKey, FileAttachmentList $attachments): FileAttachmentList
    {
        try {
            $mailFolderId = $messageKey->getMailFolderId();
            $mailAccountId = $messageKey->getMailAccountId();
            $client = $this->connect($messageKey);

            $target = $this->getFullMsg($messageKey, $client);

            $rawMessage = $this->getAttachmentComposer()->compose($target, $attachments);

            $ids = $client->append($mailFolderId, [["data" => $rawMessage]]);
            $newMessageKey = new MessageKey($mailAccountId, $mailFolderId, (string)$ids->ids[0]);
            $newList = new FileAttachmentList();
            foreach ($attachments as $attachment) {
                $newList[] = $attachment->setAttachmentKey(
                    new AttachmentKey($newMessageKey, $this->generateAttachmentId($attachment))
                );
            }

            $flagList = new FlagList();
            $flagList[] = new DraftFlag(true);
            $this->setFlags($newMessageKey, $flagList);

            // delete the previous draft
            $this->deleteMessage($messageKey);

            return $newList;
        } catch (Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }
    }


    /**
     * @inheritdoc
     */
    public function getFileAttachmentList(MessageKey $messageKey): FileAttachmentList
    {
        try {
            $client = $this->connect($messageKey);
            $target = $this->getFullMsg($messageKey, $client);
            $message = $this->parseMessage($target);

            $attachmentList = new FileAttachmentList();

            foreach ($message as $part) {
                $isAttachment = $part->isAttachment();
                $name = $part->getName();

                if ($isAttachment && !!$name) {
                    $attachmentList[] = $this->buildAttachment(
                        $messageKey,
                        $part,
                        $name
                    );
                }
            }

            return $attachmentList;
        } catch (Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }
    }


    /**
     * @inheritdoc
     */
    public function deleteAttachment(AttachmentKey $attachmentKey): MessageKey
    {
        try {
            $messageKey = $attachmentKey->getMessageKey();
            $mailFolderId = $attachmentKey->getMailFolderId();
            $mailAccountId = $attachmentKey->getMailAccountId();
            $client = $this->connect($messageKey);

            $target = $this->getFullMsg($messageKey, $client);

            $message = $this->parseMessage($target);
            $headers = $this->parseHeaders($target);

            $basePart = $this->createBasePart();


            foreach ($message as $part) {
                if (
                    $part->isAttachment() &&
                    !!$part->getName() &&
                    $this->buildAttachment(
                        $messageKey,
                        $part,
                        $part->getName()
                    )->getAttachmentKey()->equals($attachmentKey)
                ) {
                    continue;
                }
                $basePart[] = $part;
            }

            $headers = $basePart->addMimeHeaders(["headers" => $headers]);

            $rawMessage = trim($headers->toString()) .
                "\n\n" .
                trim($basePart->toString());


            $ids = $client->append($mailFolderId, [["data" => $rawMessage]]);
            $newMessageKey = new MessageKey($mailAccountId, $mailFolderId, (string)$ids->ids[0]);

            $flagList = new FlagList();
            $flagList[] = new DraftFlag(true);
            $this->setFlags($newMessageKey, $flagList);

            // delete the previous draft
            $this->deleteMessage($messageKey);

            return $newMessageKey;
        } catch (Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }
    }


    /**
     * Computes the id that should be used for the $attachment.
     * This method is guaranteed to return one and the same id based on the base64-encoded content of
     * the attachment and the "text" ot the attachment.
     * @param FileAttachment $attachment
     *
     * @return string
     *
     * @throws RuntimeException if the encoding of the attachment is not base64
     */
    protected function generateAttachmentId(FileAttachment $attachment): string
    {
        if ($attachment->getEncoding() !== "base64") {
            throw new RuntimeException("encoding must be \"base64\"");
        }

        return md5($attachment->getText() . $attachment->getContent());
    }


    /**
     * Returns the parsed message.
     *
     * @param string $target
     *
     * @return Horde_Mime_Part
     *
     * @throws Horde_Mime_Exception
     */
    protected function parseMessage(string $target): Horde_Mime_Part
    {
        return Horde_Mime_Part::parseMessage($target);
    }


    /**
     * Returns the parsed headers.
     *
     * @param string $target
     *
     * @return Horde_Mime_Headers
     *
     */
    protected function parseHeaders(string $target): Horde_Mime_Headers
    {
        return Horde_Mime_Headers::parseHeaders($target);
    }


    /**
     * Returns a base part for a message.
     *
     * @return Horde_Mime_Part
     */
    protected function createBasePart(): Horde_Mime_Part
    {
        $basePart = new Horde_Mime_Part();
        $basePart->setType('multipart/mixed');
        $basePart->isBasePart(true);

        return $basePart;
    }
}
