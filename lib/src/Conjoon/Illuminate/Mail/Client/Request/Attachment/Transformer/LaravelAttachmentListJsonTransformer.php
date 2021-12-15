<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2021 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

namespace Conjoon\Illuminate\Mail\Client\Request\Attachment\Transformer;

use Conjoon\Mail\Client\Attachment\FileAttachment;
use Conjoon\Mail\Client\Attachment\FileAttachmentList;
use Conjoon\Mail\Client\Request\Attachment\Transformer\AttachmentListJsonTransformer;
use RuntimeException;

/**
 * Class LaravelAttachmentListJsonTransformer
 * @package Conjoon\Illuminate\Mail\Client\Request\Attachment\Transformer
 */
class LaravelAttachmentListJsonTransformer implements AttachmentListJsonTransformer
{

    /**
     * @inheritdoc
     * We don't support fromString right now
     */
    public static function fromString(string $value): FileAttachmentList
    {
        throw new RuntimeException("\"fromString\" is not supported");
    }


    /**
     *
     * @param array[] $arr A numeric array with the following key value pairs:
     *  string text Arbitrary text to use as a description for the file, e.g. thge file name
     *  string type The mime type of the file
     *  string mailAccountId The id of the MailAccount of the owning message
     *  string mailFolderId The id of the mailFolder of the owning message
     *  string parentMessageItemId The id of the owining message
     *  Illuminate\Http\UploadedFile blob The data of the FileInput as sent by the client
     *
     * @inheritdoc
     */
    public static function fromArray(array $arr): FileAttachmentList
    {
        $list = new FileAttachmentList();

        foreach ($arr as $file) {
             $uploadedFile = $file["blob"];

            $transfer = ["text" => $file["text"]];
            $transfer["type"] = $uploadedFile->getMimeType();
            $transfer["size"] = $uploadedFile->getSize();
            $transfer["content"] = $uploadedFile->get();
            // encoding is usually only of interest when sending the file back to the client.
            $encoding = mb_detect_encoding($transfer["content"]);
            $transfer["encoding"] = $encoding !== false ? $encoding : "";

            $att = new FileAttachment($transfer);
            $list[] = $att;
        }

        return $list;
    }
}
