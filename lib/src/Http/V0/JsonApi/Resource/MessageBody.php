<?php

/**
 * conjoon
 * lumen-app-email
 * Copyright (C) 2022 Thorsten Suckow-Homberg https://github.com/conjoon/lumen-app-email
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

namespace App\Http\V0\JsonApi\Resource;

use Conjoon\JsonApi\Resource\ObjectDescription;
use Conjoon\JsonApi\Resource\ObjectDescriptionList;

/**
 * ResourceDescription for a MessageBody.
 *
 */
class MessageBody extends ObjectDescription
{
    /**
     * @return string
     */
    public function getType(): string
    {
        return "MessageBody";
    }


    /**
     * @return ObjectDescriptionList
     */
    public function getRelationships(): ObjectDescriptionList
    {
        $list = new ObjectDescriptionList();
        $list[] = new MailFolder();
        $list[] = new MessageItem();

        // MessageBody.MailFolder
        // MessageBody.MessageItem
        // MessageBody.MailFolder.MailAccount

        return $list;
    }


    /**
     * Returns all fields the entity exposes.
     *
     * @return string[]
     */
    public function getFields(): array
    {
        return [
            "textHtml",
            "textPlain"
        ];
    }


    /**
     * Default fields to pass to the lower level api.
     *
     * @return array
     */
    public function getDefaultFields(): array
    {
        return [
            "textPlain" => true,
            "textHtml" => true
        ];
    }
}
