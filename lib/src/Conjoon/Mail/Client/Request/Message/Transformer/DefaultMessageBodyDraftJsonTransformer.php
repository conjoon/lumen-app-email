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
use Conjoon\Mail\Client\Message\MessageBodyDraft;
use Conjoon\Mail\Client\Message\MessagePart;
use Conjoon\Util\Jsonable;
use Conjoon\Util\JsonDecodeException;
use Exception;

/**
 * Class DefaultMessageBodyDraftJsonTransformer
 *
 * @package Conjoon\Mail\Client\Message\Request\Transformer
 */
class DefaultMessageBodyDraftJsonTransformer implements MessageBodyDraftJsonTransformer
{


    /**
     * @inheritdoc
     * @param string $value
     * @return MessageBodyDraft|Jsonable
     *
     * @throws Exception
     * @see fromArray
     */
    public static function fromString(string $value): MessageBodyDraft
    {
        $value = json_decode($value, true);

        if (!$value) {
            throw new JsonDecodeException("cannot decode from string");
        }
        return self::fromArray($value);
    }


    /**
     * @inheritdoc
     */
    public static function fromArray(array $arr): MessageBodyDraft
    {

        $textHtml = null;
        $textPlain = null;

        if (isset($arr["textPlain"])) {
            $textPlain = new MessagePart($arr["textPlain"], "UTF-8", "text/plain");
        }

        if (isset($arr["textHtml"])) {
            $textHtml = new MessagePart($arr["textHtml"], "UTF-8", "text/html");
        }

        $messageKey = null;

        if (isset($arr["mailAccountId"]) && isset($arr["mailFolderId"]) && isset($arr["id"])) {
            $messageKey = new MessageKey($arr["mailAccountId"], $arr["mailFolderId"], $arr["id"]);
        }

        $messageBodyDraft = new MessageBodyDraft($messageKey);
        $textHtml && $messageBodyDraft->setTextHtml($textHtml);
        $textPlain && $messageBodyDraft->setTextPlain($textPlain);

        return $messageBodyDraft;
    }


}
