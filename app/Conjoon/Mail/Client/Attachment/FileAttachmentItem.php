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
use Conjoon\Util\Jsonable;
use InvalidArgumentException;

/**
 * FileAttachmentItem models a downloadable  email message file-attachment,
 * providing a preview-src and a download url.
 *
 * @package Conjoon\Mail\Client\Attachment
 *
 * @method string getPreviewImgSrc()
 * @method string getDownloadUrl()
 */
class FileAttachmentItem extends AbstractAttachment implements Jsonable
{

    /**
     * @var string
     */
    protected string $downloadUrl;

    /**
     * @var string
     */
    protected string $previewImgSrc;

    /**
     * DownloadableAttachment constructor.
     *
     * @param AttachmentKey $attachmentKey
     * @param array|null $data
     *
     * @throws InvalidArgumentException if text, type, downloadUrl, size or previewImgSrc
     * in $data is missing
     */
    public function __construct(AttachmentKey $attachmentKey, array $data)
    {

        $this->attachmentKey = $attachmentKey;

        $missing = "";
        if (!isset($data["downloadUrl"])) {
            $missing = "downloadUrl";
        } elseif (!isset($data["previewImgSrc"])) {
            $missing = "previewImgSrc";
        }

        if ($missing) {
            throw new InvalidArgumentException(
                "value for property \"" . $missing . "\" missing"
            );
        }

        parent::__construct($attachmentKey, $data);
    }


// +---------------------------
// | Jsonable
// +---------------------------

    /**
     * @inheritdoc
     */
    public function toJson(): array
    {

        return array_merge($this->getAttachmentKey()->toJson(), [
            "type" => $this->getType(),
            "text" => $this->getText(),
            "size" => $this->getSize(),
            "downloadUrl" => $this->getDownloadUrl(),
            "previewImgSrc" => $this->getPreviewImgSrc()
        ]);
    }
}
