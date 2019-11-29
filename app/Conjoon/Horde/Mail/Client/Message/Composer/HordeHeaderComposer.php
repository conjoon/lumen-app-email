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
    Conjoon\Mail\Client\Message\MessageItemDraft;

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
            // set headers
            if ($mid->getSubject()) {
                 $headers->removeHeader("Subject");
                 $headers->addHeader("Subject", $mid->getSubject());
            }

            if ($mid->getFrom()) {
                $headers->removeHeader("From");
                $headers->addHeader("From", $mid->getFrom()->toString());
            }

            if ($mid->getReplyTo()) {
                $headers->removeHeader("Reply-To");
                $headers->addHeader("Reply-To", $mid->getReplyTo()->toString());
            }

            if ($mid->getTo()) {
                $headers->removeHeader("To");
                $headers->addHeader("To", $mid->getTo()->toString());
            }

            if ($mid->getCc()) {
                $headers->removeHeader("Cc");
                $headers->addHeader("Cc", $mid->getCc()->toString());
            }

            if ($mid->getBcc()) {
                $headers->removeHeader("Bcc");
                $headers->addHeader("Bcc", $mid->getBcc()->toString());
            }

            if ($mid->getDate()) {
                $headers->removeHeader("Date");
                $headers->addHeader("Date", $mid->getDate()->format("r"));
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
