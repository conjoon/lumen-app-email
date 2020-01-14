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

use Conjoon\Mail\Client\Message\MessageBodyDraft,
    Conjoon\Mail\Client\Message\Composer\BodyComposer;

/**
 * Class HordeBodyComposer
 *
 * @package Conjoon\Horde\Mail\Client\Message\Composer
 */
class HordeBodyComposer implements BodyComposer {


    /**
     * @inheritdoc
     */
    public function compose(string $target, MessageBodyDraft $messageBodyDraft) :string {


        $headers = \Horde_Mime_Headers::parseHeaders($target);

        $plain = $messageBodyDraft->getTextPlain();
        $html  = $messageBodyDraft->getTextHtml();

        $basepart = new \Horde_Mime_Part();
        $basepart->setType('multipart/alternative');
        $basepart->isBasePart(true);

        $htmlBody = new \Horde_Mime_Part();
        $htmlBody->setType('text/html');
        $htmlBody->setCharset($html->getCharset());
        $htmlBody->setContents($html->getContents());

        $plainBody = new \Horde_Mime_Part();
        $plainBody->setType('text/plain');
        $plainBody->setCharset($plain->getCharset());
        $plainBody->setContents($plain->getContents());

        $basepart[] = $htmlBody;
        $basepart[] = $plainBody;

        $headers = $basepart->addMimeHeaders(["headers" => $headers]);

        $txt = trim($headers->toString()) .
               "\n\n" .
               trim($basepart->toString());

        return $txt;

    }


}