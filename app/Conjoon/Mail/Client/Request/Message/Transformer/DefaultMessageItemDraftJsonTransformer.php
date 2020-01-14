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
    Conjoon\Mail\Client\Data\CompoundKey\MessageKey,
    Conjoon\Mail\Client\Request\JsonTransformerException,
    Conjoon\Mail\Client\Message\MessageItemDraft;

/**
 * Class DefaultMessageItemDraftJsonTransformer
 * This implementation will at least set the "date" field to the value of NOW if the
 * field is missing in the $data-array. All other fields will only be set if they are
 * available in $data.
 * This implementation considers the Modifiable state of a MessageItemDraft and
 * will not pass the values to the constructor to make sure the data changed here is marked as
 * modified.
 *
 * @package Conjoon\Mail\Client\Message\Request\Transformer
 */
class DefaultMessageItemDraftJsonTransformer implements MessageItemDraftJsonTransformer {


    /**
     * @inheritdoc
     */
    public function transform(array $data) : MessageItemDraft {

        if (!isset($data["mailAccountId"]) || !isset($data["mailFolderId"]) || !isset($data["id"])) {
            throw new JsonTransformerException(
                "Missing compound key information. " .
                "Fields \"mailAccountId\", \"mailFolderId\", and \"id\" must be set."
            );
        }

        // consider Modifiable and do not pass to constructor
        $messageKey = new MessageKey($data["mailAccountId"], $data["mailFolderId"], $data["id"]);
        $draft      = new MessageItemDraft($messageKey);

        foreach($data as $field => $value) {

            if (in_array($field, ["mailAccountId", "mailFolderId", "id"])) {
                continue;
            }

            $setter = "set" . ucfirst($field);

            switch ($field) {

                case "from":
                case "replyTo":
                    $add = MailAddress::fromJsonString($value);
                    $draft->{$setter}($add);
                    break;

                case "to":
                case "cc":
                case "bcc":
                    $add = MailAddressList::fromJsonString($value);
                    $draft->{$setter}($add);
                    break;

                case "date":
                    $draft->{$setter}(new \DateTime("@" . $data["date"]));
                    break;

                default:
                    $draft->{$setter}($value);
                    break;
            }
        }

        if (!$draft->getDate()) {
            $draft->setDate(new \DateTime());
        }

        return $draft;
    }

}