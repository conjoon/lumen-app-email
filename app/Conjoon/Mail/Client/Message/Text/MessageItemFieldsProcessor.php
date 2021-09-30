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

namespace Conjoon\Mail\Client\Message\Text;

use Conjoon\Mail\Client\Message\AbstractMessageItem;

/**
 * Interface MessageItemFieldsProcessor.
 * Contract for converting header fields of a MessageItem (such as "subject") to a target charset.
 *
 * @package Conjoon\Mail\Client\Message\Text
 */
interface MessageItemFieldsProcessor {

    /**
     * Converts field values of of the AbstractMessageItem to the specified charset.
     * Fields converted include
     *  - subject
     *
     * @param AbstractMessageItem $messageItem
     * @param string $toCharset
     *
     * @return AbstractMessageItem
     *
     * @throws ProcessorException
     */
    public function process(AbstractMessageItem $messageItem, string $toCharset = "UTF-8") : AbstractMessageItem;


}
