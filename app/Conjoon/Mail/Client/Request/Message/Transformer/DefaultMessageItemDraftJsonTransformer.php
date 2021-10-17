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

namespace Conjoon\Mail\Client\Request\Message\Transformer;

use Conjoon\Mail\Client\Data\CompoundKey\MessageKey;
use Conjoon\Mail\Client\Data\MailAddress;
use Conjoon\Mail\Client\Data\MailAddressList;
use Conjoon\Mail\Client\Message\MessageItemDraft;
use Conjoon\Util\Jsonable;
use Conjoon\Util\JsonDecodeException;
use DateTime;
use Exception;

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
class DefaultMessageItemDraftJsonTransformer implements MessageItemDraftJsonTransformer
{

    /**
     * @inheritdoc
     * @param string $value
     * @return MessageItemDraft|Jsonable
     *
     * @throws Exception
     * @see fromArray
     */
    public static function fromString(string $value): MessageItemDraft
    {
        $value = json_decode($value, true);

        if (!$value) {
            throw new JsonDecodeException("cannot decode from string");
        }
        return self::fromArray($value);
    }


    /**
     * @inheritdoc
     * @throws Exception
     */
    public static function fromArray(array $arr): MessageItemDraft
    {

        if (!isset($arr["mailAccountId"]) || !isset($arr["mailFolderId"]) || !isset($arr["id"])) {
            throw new JsonDecodeException(
                "Missing compound key information. " .
                "Fields \"mailAccountId\", \"mailFolderId\", and \"id\" must be set."
            );
        }

        // consider Modifiable and do not pass to constructor
        $messageKey = new MessageKey($arr["mailAccountId"], $arr["mailFolderId"], $arr["id"]);
        $draft = new MessageItemDraft($messageKey);

        foreach ($arr as $field => $value) {
            if (in_array($field, ["mailAccountId", "mailFolderId", "id"])) {
                continue;
            }

            $setter = "set" . ucfirst($field);

            switch ($field) {
                case "from":
                case "replyTo":
                    try {
                        $add = MailAddress::fromString($value);
                    } catch (JsonDecodeException $e) {
                        $add = null;
                    }
                    $draft->{$setter}($add);
                    break;

                case "to":
                case "cc":
                case "bcc":
                    try {
                        $add = MailAddressList::fromString($value);
                    } catch (JsonDecodeException $e) {
                        $add = null;
                    }
                    $draft->{$setter}($add);
                    break;

                case "date":
                    $draft->{$setter}(new DateTime("@" . $arr["date"]));
                    break;

                default:
                    $draft->{$setter}($value);
                    break;
            }
        }

        if (!$draft->getDate()) {
            $draft->setDate(new DateTime());
        }

        return $draft;
    }
}
