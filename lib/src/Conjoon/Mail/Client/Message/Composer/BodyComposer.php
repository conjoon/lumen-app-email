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

namespace Conjoon\Mail\Client\Message\Composer;

use Conjoon\Mail\Client\Message\MessageBodyDraft;

/**
 * Interface BodyComposer
 *
 * Contract for transforming a MessageBodyDraft to a full text email message.
 *
 *
 * @package Conjoon\Mail\Client\Message\Composer
 */
interface BodyComposer
{


    /**
     * Processes the $messageBodyDraft and returns a raw Email Message,
     * properly set up with required multipart/alternative headers and the body. The resulting body
     * should contain all parts defined in $messageBodyDraft.
     * The created header should be RFC 822/2822/3490/5322 compliant.
     *
     * @param string $target The raw email message for which the MessageBody should be composed
     * @param MessageBodyDraft $messageBodyDraft
     *
     * @return string A valid MIME message text
     */
    public function compose(string $target, MessageBodyDraft $messageBodyDraft): string;
}
