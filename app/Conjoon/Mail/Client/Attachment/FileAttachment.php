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
declare(strict_types=1);

namespace Conjoon\Mail\Client\Attachment;

use Conjoon\Mail\Client\Data\CompoundKey\AttachmentKey;

/**
 * FileAttachment models a file email message attachment.
 *
 * @package Conjoon\Mail\Client\Attachment
 */
class FileAttachment extends AbstractAttachment {

    /**
     * @var string
     */
    protected $content;

    /**
     * @var string
     */
    protected $encoding;

    /**
     * Atatchment constructor.
     *
     * @param AttachmentKey $attachmentKey
     * @param array|null $data
     *
     * @throws \InvalidArgumentException if content or encoding  in $data is missing
     */
    public function __construct(AttachmentKey $attachmentKey, array $data) {

        $this->attachmentKey = $attachmentKey;

        $missing = "";
        if (!isset($data["content"])) {
            $missing = "content";
        } else if (!isset($data["encoding"])) {
            $missing = "encoding";
        }

        if ($missing) {
            throw new \InvalidArgumentException(
                "value for property \"" . $missing . "\" missing"
            );
        }

        parent::__construct($attachmentKey, $data);
    }



}