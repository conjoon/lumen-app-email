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

namespace Conjoon\Mail\Client\Data;


/**
 * Class PreviewableMessageItem is a MessageItem containing body text for previewing the
 * message's contents.
 *
 * @example
 *
 *    $item = new PreviewableMessageItem(
 *              new MessageKey("INBOX", "232"),
 *              ["previewText" => "foobar"]
 *            );
 *
 *    $item->getPreviewText(); // "foobar"
 *
 * @package Conjoon\Mail\Client\Data
 */
class PreviewableMessageItem extends MessageItem  {


    /**
     * @var string
     */
    protected $previewText = "";


    /**
     * Sets the previewText for this MessageItem. The text should be UTF-8
     * encoded and stripped from all HTML tags.
     *
     * @param string $text
     */
    public function setPreviewText(string $text) {
        $this->previewText = $text;
    }


    /**
     * Returns the previewText for this MessageItem.
     * @return string
     */
    public function getPreviewText() {
        return $this->previewText;
    }



// --------------------------------
//  Jsonable interface
// --------------------------------

    /**
     * @inheritdoc
     */
    public function toJson() :array{

        return array_merge(
            parent::toJson(),
            ["previewText" => $this->getPreviewText()]
        );
    }


}