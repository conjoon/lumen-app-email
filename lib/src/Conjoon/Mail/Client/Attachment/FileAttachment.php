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

declare(strict_types=1);

namespace Conjoon\Mail\Client\Attachment;

use Conjoon\Mail\Client\Data\CompoundKey\AttachmentKey;
use Conjoon\Util\ArrayUtil;
use InvalidArgumentException;

/**
 * FileAttachment models a file email message attachment.
 * A FileAttachment may or may not have an attachment key that identifies it.
 * If the AttachmentKey is missing, it is assumed that the FileAttachment is in draft-state, i.e.
 * getting created for an owning message.
 * Setting the AttachmentKey for the FileAttachment later on will create a copy of the original FileAttachment
 * and return it.
 *
 * @example
 *   $attachment = new FileAttachment(new AttachmentKey("dev", "INBOX", "1234", "1"));
 *
 *   $keyedAttachment = $attachment->setAttachmentKey(new AttachmentKey("x", "y", "z", "2"));
 *   $keyedAttachment !== $attachment; // true
 *
 * @package Conjoon\Mail\Client\Attachment
 *
 * @method string getContent()
 * @method string getEncoding()
 */
class FileAttachment extends AbstractAttachment
{

    /**
     * @var string
     */
    protected string $content;

    /**
     * @var string
     */
    protected string $encoding;

    /**
     * Attachment constructor.
     * Expects at least one parameter that is either an AttachmentKey or an array with the
     * default data for the attachment.
     *
     * @param AttachmentKey|array|null $attachmentKey
     * @param array|null $data
     *
     * @throws InvalidArgumentException if $attachmentKey and $data are both missing
     */
    public function __construct($attachmentKey, array $data = [])
    {
        if (!$attachmentKey && !$data) {
            throw new InvalidArgumentException("both \"attachmentKey\" and \"data\" must not be missing");
        }

        if ($attachmentKey instanceof AttachmentKey) {
            $this->attachmentKey = $attachmentKey;
        } elseif (is_array($attachmentKey)) {
            $data = $attachmentKey;
        }


        $missing = "";
        if (!isset($data["content"])) {
            $missing = "content";
        } elseif (!isset($data["encoding"])) {
            $missing = "encoding";
        }

        if ($missing) {
            throw new InvalidArgumentException(
                "value for property \"" . $missing . "\" missing"
            );
        }

        parent::__construct(is_array($attachmentKey) ? null :  $attachmentKey, $data);
    }


    /**
     * Sets the AttachmentKey for this Attachment and returns a new instance with the
     * existing data copied.
     *
     * @param AttachmentKey $attachmentKey
     *
     * @return FileAttachment
     */
    public function setAttachmentKey(AttachmentKey $attachmentKey): FileAttachment
    {
        return new self(
            $attachmentKey,
            ArrayUtil::intersect($this->toJson(), ["content", "encoding", "text", "size", "type"])
        );
    }


    /**
     * @inheritdoc
     */
    public function toJson(): array
    {
        return array_merge(parent::toJson(), [
            "content" => $this->getContent(),
            "encoding" => $this->getEncoding()
        ]);
    }
}
