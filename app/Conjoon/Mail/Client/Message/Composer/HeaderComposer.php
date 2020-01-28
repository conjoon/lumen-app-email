<?php
/**
 * conjoon
 * php-cn_imapuser
 * Copyright (C) 2020 Thorsten Suckow-Homberg https://github.com/conjoon/php-cn_imapuser
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

use Conjoon\Mail\Client\Message\MessageItemDraft;

/**
 * Interface HeaderComposer
 *
 * @package Conjoon\Mail\Client\Message\Composer
 */
interface HeaderComposer {


    /**
     * Writes the header fields in source to $target which is assumed to be the
     * raw full text representing an email message. The resulting header text must be
     * RFC 822/2822/3490/5322 compliant.
     * If $source is null, no header information is available for $target. Implementing
     * APIs are advised to update the header information in $target with default values, then,
     * such as the Date.
     * Existing fields should not be removed if they are not available in $source.
     * Existing fields should be removed if fields are available in $source, but represent
     * empty values (e.g.: "", [], null)
     *
     * @param string $target
     * @param MessageItemDraft|null $source
     *
     * @return string
     */
    public function compose(string $target, MessageItemDraft $source = null) :string;


}
