<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2021 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

namespace Conjoon\Horde\Mail\Client\Imap;

/**
 * Helper-Trait for defining attribute-related information.
 *
 * Trait AttributeTrait
 * @package Conjoon\Horde\Mail\Client\Imap
 */
trait AttributeTrait
{

    /**
     * Returns a list of default attributes to return with a MessageItem.
     *
     * @return string[]
     */
    protected function getDefAttr($additional = [], $exclude = []): array
    {

        $def = $this->getDefaultAttributes();
        $ret = [];
        foreach ($def as $attr) {
            if (in_array($attr, $exclude)) {
                continue;
            }
            $ret[$attr] = true; // true or array
        }

        foreach ($additional as $key => $fieldConf) {
            $chk = $this->getAttr($key, $additional);
            $chk && $ret[$key] = $chk;// true or array
        }

        return $ret;
    }


    /**
     * @return string[]
     */
    public function getSupportedAttributes(): array
    {
        return [
            "hasAttachments",
            "size",
            "plain", // \ __Preview
            "html",  // /   Text
            "cc",
            "bcc",
            "replyTo",
            "from",
            "to",
            "subject",
            "date",
            "seen",
            "answered",
            "draft",
            "flagged",
            "recent",
            "charset",
            "references",
            "messageId"
        ];
    }


    /**
     * @inheritdoc
     */
    public function getDefaultAttributes(): array
    {
        return [
            "from",
            "to",
            "subject",
            "date",
            "seen",
            "answered",
            "draft",
            "flagged",
            "recent",
            "charset",
            "references",
            "messageId",
            "plain",
            "size",
            "hasAttachments"
        ];
    }


    /**
     * Returns the target's value at "$key" if the value is truthy, otherwise
     * null or $default (if !null) is returned.
     *
     * @param $key
     * @param $target
     * @param null $default
     * @return mixed|null
     * @noinspection PhpSameParameterValueInspection
     */
    private function getAttr($key, $target, $default = null)
    {
        if (array_key_exists($key, $target)) {
            $val = $target[$key];
            if (is_array($val) && empty($val)) {
                $val = true;
            }

            return $val ?: $default;
        }

        return $default;
    }
}
