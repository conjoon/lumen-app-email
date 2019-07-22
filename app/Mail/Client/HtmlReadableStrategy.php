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

namespace App\Mail\Client;


class HtmlReadableStrategy {


    /**
     * Purifies the text from untrusted HTML/CSS/Script contents.
     *
     * @param string $text
     *
     * @return string
     */
    public function process(string $text) :string {

        // require_once 'HTMLPurifier.auto.php';
        $htmlPurifierConfig = \HTMLPurifier_Config::createDefault();
        $htmlPurifierConfig->set('HTML.Trusted', false);
        $htmlPurifierConfig->set('CSS.AllowTricky', false);
        $htmlPurifierConfig->set('CSS.AllowImportant', false);
        $htmlPurifierConfig->set('CSS.Trusted', false);

        $inst =  new \HTMLPurifier($htmlPurifierConfig);

        return $inst->purify($text);

    }



}
