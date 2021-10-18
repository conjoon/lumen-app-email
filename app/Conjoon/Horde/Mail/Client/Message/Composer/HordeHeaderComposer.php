<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2020 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

use Conjoon\Mail\Client\Message\Composer\HeaderComposer;
use Conjoon\Mail\Client\Message\MessageItemDraft;
use Conjoon\Util\Stringable;
use DateTime;
use Horde_Mime_Exception;
use Horde_Mime_Headers;
use Horde_Mime_Headers_MessageId;
use Horde_Mime_Part;

/**
 * Class HordeHeaderComposer
 *
 * @package Conjoon\Horde\Mail\Client\Message\Composer
 */
class HordeHeaderComposer implements HeaderComposer
{


    /**
     * @inheritdoc
     * @throws Horde_Mime_Exception
     */
    public function compose(string $target, MessageItemDraft $source = null): string
    {

        $part = Horde_Mime_Part::parseMessage($target);
        $headers = Horde_Mime_Headers::parseHeaders($target);

        $normalizedHeaders = [
            "xCnDraftInfo" => "X-Cn-Draft-Info",
            "replyTo" => "Reply-To",
            "inReplyTo" => "In-Reply-To",
            "userAgent" => "User-Agent",
            "messageId" => "Message-ID"
        ];
        $cnHeaders = array_flip($normalizedHeaders);

        // write a default header-array with all the header values
        // from the original message headers into an array with
        // key / value pairs that a MessageItemDraft understands
        $headerData = [];
        $tmpHeaderData = $headers->toArray();
        foreach ($tmpHeaderData as $key => $value) {
            if (isset($cnHeaders[$key])) {
                $headerData[$cnHeaders[$key]] = $value;
            } else {
                // we make sure that we override to, cc, or bcc
                // for any possible spelling (e.g. BCC or cC)
                $tKey = strtolower($key);
                if (in_array($tKey, ["to", "cc", "bcc"])) {
                    $key = $tKey;
                }
                $headerData[lcfirst($key)] = $value;
            }
        }

        $headerData["userAgent"] = "php-conjoon";


        if (!$source || !$source->getDate()) {
            $date = new DateTime();
            $headerData["date"] = $date->format("r");
        }


        // get all values from source and override the data in
        // $headerData with these values
        if ($source) {
            $modified = $source->getModifiedFields();

            foreach ($modified as $field) {
                if (!MessageItemDraft::isHeaderField($field)) {
                    continue;
                }
                switch ($field) {
                    case "date":
                        $headerData["date"] = $source->getDate()->format("r");
                        break;

                    case "replyTo":
                        $retrieved = $source->getReplyTo();
                        if ($retrieved) {
                            $headerData["replyTo"] = $retrieved->toString();
                        }
                        break;


                    default:
                        $getter = "get" . ucfirst($field);
                        $retrieved = $source->{$getter}();

                        if ($retrieved instanceof Stringable) {
                            $headerData[$field] = $retrieved->toString();
                        } else {
                            $headerData[$field] = $retrieved;
                        }

                        break;
                }
            }
        }

        // if our X-CN-DRAFT-INFO header field is not set, we will set it here
        if (
            $source && (!$headers->getHeader("X-Cn-Draft-Info") &&
                $source->getXCnDraftInfo())
        ) {
            $headerData["xCnDraftInfo"] = $source->getXCnDraftInfo();
        }

        // if we do not have a Message Id, we will generate one here and
        // apply it to the draft, if available
        $messageId = strval($headers->getHeader("Message-ID"));
        if (!$messageId) {
            $messageId = strval(Horde_Mime_Headers_MessageId::create("cn"));
        }
        $headerData["messageId"] = $messageId;
        if ($source) {
            $source->setMessageId($messageId);
        }

        // transform all data to regular message header fields
        // and write the contents back in
        $orderedHeaders = $this->sortHeaderFields(array_keys($headerData));
        foreach ($orderedHeaders as $headerField) {
            $value = $headerData[$headerField];

            $finalField = $normalizedHeaders[$headerField] ?? ucfirst($headerField);

            $headers->removeHeader($finalField);
            if ($value !== null && $value !== "") {
                $headers->addHeader($finalField, $value);
            }
        }


        return trim($headers->toString()) . "\n\n" . trim($part->toString());
    }


// +---------------------------------
// | Helper
// +---------------------------------

    /**
     * Tries to sort $fields after a specific order for Header-fields in messages,
     * will only return the fields that are also available in $fields.
     * Proposed order is
     *
     *  - date
     *  - subject
     *  - from
     *  - sender
     *  - replyTo
     *  - to
     *  - cc
     *  - bcc
     *  - inReplyTo
     *  - messageId
     *  - references
     *  - userAgent
     *  - xCnDraftInfo
     *
     * Additional header fields specified in $fields not matching order-fields
     * will be appended in no particular order to the returned array.
     *
     *
     * @param array $fields
     * @return array
     */
    public function sortHeaderFields(array $fields): array
    {

        $order = [
            "date",
            "subject",
            "from",
            "sender",
            "replyTo",
            "to",
            "cc",
            "bcc",
            "messageId",
            "inReplyTo",
            "references",
            "userAgent",
            "xCnDraftInfo"
        ];

        $intersect = array_intersect($order, $fields);

        $diff = array_diff($fields, $order);

        return array_merge($intersect, $diff);
    }
}
