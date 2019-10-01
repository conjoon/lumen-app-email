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

namespace Conjoon\Mail\Client\Attachment\Processor;

use Conjoon\Mail\Client\Attachment\FileAttachment,
    Conjoon\Mail\Client\Attachment\FileAttachmentItem;


/**
 * Interface FileAttachmentProcessor.
 * Contract for converting a FileAttachment to a FileAttachmentItem to provide
 * a download and preview for the contents of a FileAttachment.
 *
 * @package Conjoon\Mail\Client\Attachment\Processor
 */
interface FileAttachmentProcessor {

    /**
     * Processes the FileAttachment ad returns a FileAttachmentItem
     * with the properties previewImgSrc and downloadUrl computed by the content and
     * encoding of the FileAttachment.
     * Both entities need to share the same AttachmentKey-informations.
     *
     * @param FileAttachment $fileAttachment
     *
     * @return FileAttachmentItem
     *
     * @throws ProcessorException if any error occurs.
     */
    public function process(FileAttachment $fileAttachment) : FileAttachmentItem;


}