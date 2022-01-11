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

use Conjoon\Horde\Mail\Client\Imap\AttachmentTrait;

/**
 * Class AttachmentTraitForTesting
 * @package Tests\Conjoon\Horde\Mail\Client\Imap
 */
class AttachmentTraitForTesting
{
    use AttachmentTrait;

    public string $rawMsg = 'Date: Tue, 11 Jan 2022 08:00:00 +0000
            From: dev@conjoon.org
            Reply-To: dev@conjoon.org
            Message-ID: <20220111094623.cn.o4MoSlVH_DBgUFBpqU1gAGF@php-ms-imapuser.ddev.site>
                User-Agent: php-conjoon
            Content-Type: multipart/mixed; boundary="=_LKASMWd4cVYJAzOstX7sU4f"
            MIME-Version: 1.0

            This message is in MIME format.

                --=_LKASMWd4cVYJAzOstX7sU4f
            Content-Type: text/html; charset=utf-8


                --=_LKASMWd4cVYJAzOstX7sU4f
            Content-Type: text/plain; charset=utf-8


                --=_LKASMWd4cVYJAzOstX7sU4f
            Content-Type: application/x-empty; name="Neues Textdokument.txt"
            Content-Disposition: attachment; size=0; filename="Neues Textdokument.txt"
            Content-Transfer-Encoding: base64


                --=_LKASMWd4cVYJAzOstX7sU4f--';

    public function setFlags($messageKey, $flags)
    {
    }


    public function deleteMessage($messageKey)
    {
    }


    public function getAttachmentComposer()
    {
    }


    public function connect()
    {
    }


    public function getFullMsg(): string
    {
        return $this->rawMsg;
    }
}
