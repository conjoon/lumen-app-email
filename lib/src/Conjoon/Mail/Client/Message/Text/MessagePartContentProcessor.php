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

use Conjoon\Mail\Client\Message\MessagePart;

/**
 * Interface MessagePartContentProcessor.
 * Contract for converting the contents of a MessagePart to a target charset.
 * Implementing classes are free to add any additional functionality for converting the
 * contents to a readable version required by the client.
 *
 * @package Conjoon\Mail\Client\Message\Text
 */
interface MessagePartContentProcessor
{

    /**
     * Converts the contents of the MessagePart to the specified charset.
     *
     * @param MessagePart $messagePart
     * @param string $toCharset
     * @param array|null $opts an arbitrary set of options the Processor should consider
     *
     * @return MessagePart
     *
     * @throws ProcessorException
     */
    public function process(MessagePart $messagePart, string $toCharset = "UTF-8", ?array $opts = null): MessagePart;
}
