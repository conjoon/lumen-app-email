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

namespace Conjoon\Mail\Client\Message\Text;

use Conjoon\Text\Converter,
    Conjoon\Mail\Client\Message\AbstractMessageItem;

/**
 * Class DefaultMessageItemFieldsProcessor
 * @package Conjoon\Mail\Client\Message\Text
 */
class DefaultMessageItemFieldsProcessor implements MessageItemFieldsProcessor {

    /**
     * @var Converter
     */
    protected $converter;


    /**
     * DefaultMessageItemFieldsProcessor constructor.
     *
     * @param Converter $converter
     * @param HtmlReadableStrategy $htmlReadableStrategy
     */
    public function __construct(Converter $converter)  {
        $this->converter = $converter;
    }

// +--------------------------------------
// | MessageItemFieldsProcessor
// +--------------------------------------
    /**
     * @inheritdoc
     */
    public function process(AbstractMessageItem $messageItem, string $toCharset = "UTF-8") : AbstractMessageItem {
        // Item needs charset information
        $messageItem->setSubject(
            $this->converter->convert(
                $messageItem->getSubject(),
                $messageItem->getCharset(),
                $toCharset
            )
        );

        $messageItem->setCharset($toCharset);

        return $messageItem;
    }


}