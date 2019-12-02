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

namespace Conjoon\Horde\Mail\Client\Message\Composer;

use Conjoon\Mail\Client\Message\Composer\HeaderComposer,
    Conjoon\Mail\Client\Message\MessageItemDraft,
    Conjoon\Util\Stringable;

/**
 * Class HordeHeaderComposer
 *
 * @package Conjoon\Horde\Mail\Client\Message\Composer
 */
class HordeHeaderComposer implements HeaderComposer {


    /**
     * @inheritdoc
     */
    public function compose(string $target, MessageItemDraft $source = null) :string {

        $part    = \Horde_Mime_Part::parseMessage($target);
        $headers = \Horde_Mime_Headers::parseHeaders($target);

        $mid = $source;

        if ($mid) {

            $modified = $mid->getModifiedFields();

            foreach ($modified as $field) {

                if (!MessageItemDraft::isHeaderField($field)) {
                    continue;
                }

                switch ($field) {

                    case "subject":
                        $headers->removeHeader("Subject");
                        $headers->addHeader("Subject", $mid->getSubject());
                        break;

                    case "date":
                        $headers->removeHeader("Date");
                        $headers->addHeader("Date", $mid->getDate()->format("r"));
                        break;

                    case "replyTo":
                        $headers->removeHeader("Reply-To");
                        $retrieved = $mid->getReplyTo();
                        if ($retrieved) {
                            $headers->addHeader("Reply-To", $retrieved->toString());
                        }
                        break;

                    default:
                        $getter      = "get" . ucfirst($field);
                        $headerField = ucfirst($field);
                        $headers->removeHeader($headerField);
                        $retrieved = $mid->{$getter}();

                        if ($retrieved === null) {
                            break;
                        }

                        if ($retrieved instanceof Stringable) {
                            $headers->addHeader($headerField, $retrieved->toString());
                        } else {
                            $headers->addHeader($headerField, $retrieved);
                        }

                        break;
                }
            }
        }

        if (!$mid || !$mid->getDate()) {
            $date = new \DateTime();
            $headers->addHeader("Date", $date->format("r"));
        }

        $headers->addHeader("User-Agent", "php-conjoon");


        return trim($headers->toString()) . "\n\n" . trim($part->toString());
    }


}
