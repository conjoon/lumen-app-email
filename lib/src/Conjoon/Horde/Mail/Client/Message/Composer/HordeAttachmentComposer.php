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

namespace Conjoon\Horde\Mail\Client\Message\Composer;

use Conjoon\Mail\Client\Attachment\FileAttachmentList;
use Conjoon\Mail\Client\Message\Composer\AttachmentComposer;
use Horde_Mime_Exception;
use Horde_Mime_Headers;
use Horde_Mime_Part;

/**
 * Class HordeBodyComposer
 *
 * @package Conjoon\Horde\Mail\Client\Message\Composer
 */
class HordeAttachmentComposer implements AttachmentComposer
{


    /**
     * @inheritdoc
     * @throws Horde_Mime_Exception
     */
    public function compose(string $target, FileAttachmentList $fileAttachmentList): string
    {

        $message = Horde_Mime_Part::parseMessage($target);
        $headers = Horde_Mime_Headers::parseHeaders($target);

        foreach ($fileAttachmentList as $attachment) {
            $part = new Horde_Mime_Part();
            $part->setType($attachment->getType());
            $part->setCharset("us-ascii");
            $part->setDisposition("attachment");
            $part->setContents($attachment->getContent(), ["encoding" => $attachment->getEncoding()]);
            $part->setBytes($attachment->getSize());
            $part->setName($attachment->getText());
            $message[] = $part;
        }

        return trim($headers->toString()) . "\n\n" . trim($message->toString());
    }
}
