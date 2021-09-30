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

use Conjoon\Mail\Client\Request\JsonTransformer,
    Conjoon\Mail\Client\Request\JsonTransformerException,
    Conjoon\Mail\Client\Message\MessageBodyDraft;

/**
 * Interface provides contract for processing an associative array containing
 * plain data to a MessageBodyDraft.
 *
 * @package Conjoon\Mail\Client\Request\Message\Transformer
 */
interface MessageBodyDraftJsonTransformer extends JsonTransformer {

    /**
     * Returns a MessageBodyDraft that was created from the data found in $data.
     * If id, mailAccountId and mailFolderId are available, a MessageKey will be set for
     * the MessageBodyDraft.
     *
     * @param array $data
     * @return MessageBodyDraft
     */
    public function transform(array $data) : MessageBodyDraft;

}
