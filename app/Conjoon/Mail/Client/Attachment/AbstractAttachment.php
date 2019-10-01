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

namespace Conjoon\Mail\Client\Attachment;

use Conjoon\Mail\Client\Data\CompoundKey\AttachmentKey;


/**
 * AbstractAttachment models an abstract base email message attachment.
 *
 * @package Conjoon\Mail\Client\Attachment
 */
abstract class AbstractAttachment {

    /**
     * @var AttachmentKey
     */
    protected $attachmentKey;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $text;

    /**
     * @var integer
     */
    protected $size;


    /**
     * Attachment constructor.
     *
     * @param AttachmentKey $attachmentKey
     * @param array|null $data
     *
     * @throws \InvalidArgumentException if text, type or size in $data is missing
     */
    public function __construct(AttachmentKey $attachmentKey, array $data) {

        $this->attachmentKey = $attachmentKey;

        $missing = "";
        if (!isset($data["text"])) {
            $missing = "text";
        } else if (!isset($data["type"])) {
            $missing = "type";
        } else if (!isset($data["size"])) {
            $missing = "size";
        }

        if ($missing) {
            throw new \InvalidArgumentException(
                "value for property \"" . $missing . "\" missing"
            );
        }

        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

    }


    /**
     * Returns the atatchment key for this attachment.
     *
     * @return AttachmentKey
     */
    public function getAttachmentKey() :AttachmentKey {
        return $this->attachmentKey;
    }


    /**
     * Makes sure defined properties in this class are accessible via getter method calls.
     * If needed, camelized methods are resolved to their underscored representations in this
     * class.
     *
     * @param String $method
     * @param Mixed $arguments
     *
     * @return mixed
     *
     * @throws \BadMethodCallException if a method is called for which no property exists
     */
    public function __call($method, $arguments) {

        if (strpos($method, 'get') === 0) {
            $property = lcfirst(substr($method, 3));

            if (property_exists($this, $property)) {
                return $this->{$property};
            }
        }

        throw new \BadMethodCallException("no method \"".$method."\" found.");
    }


    /**
     * Sets the size (in bytes) of this attachment.
     *
     * @param int $size
     */
    public function setSize(int $size) {
        $this->size = $size;
    }


}