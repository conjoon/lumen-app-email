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

namespace Conjoon\Mail\Client\Request\Message\Transformer;

use Conjoon\Mail\Client\Data\MailAddress,
    Conjoon\Mail\Client\Data\MailAddressList,
    Conjoon\Mail\Client\Message\MessageItemDraft;

/**
 * Class DefaultMessageItemDraftWriter
 *
 * @package Conjoon\Mail\Client\Message\Request\Transformer
 */
class DefaultMessageItemDraftJsonTransformer implements MessageItemDraftJsonTransformer {


    /**
     * @inheritdoc
     */
    public function transform(array $data) : MessageItemDraft {

        $draftData = [];

        $draftData["date"] = new \DateTime("@" . $data["date"]);

        $draftData["seen"] = $data["seen"];
        $draftData["flagged"] = $data["flagged"];

        if (isset($data["subject"])) {
            $draftData["subject"] = $data["subject"];
        }
        if (isset($data["from"]) && ($from = MailAddress::fromJsonString($data["from"]))) {
            $draftData["from"] = $from;
        }
        if (isset($data["replyTo"]) && ($replyTo = MailAddress::fromJsonString($data["replyTo"]))) {
            $draftData["replyTo"] = $replyTo;
        }
        if (isset($data["to"]) && ($to = MailAddressList::fromJsonString($data["to"]))) {
            $draftData["to"] = $to;
        }
        if (isset($data["cc"]) && ($cc = MailAddressList::fromJsonString($data["cc"]))) {
            $draftData["cc"] = $cc;
        }
        if (isset($data["bcc"]) && ($bcc = MailAddressList::fromJsonString($data["bcc"]))) {
            $draftData["bcc"] = $bcc;
        }

        return new MessageItemDraft($draftData);
    }

}