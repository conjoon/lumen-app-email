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
use Conjoon\jsonApi\Resource\ObjectDescriptionList;

/**
 * ResourceDescription for a MailAccount.
 *
 */
class MailAccount extends ObjectDescription
{
    /**
     * @return string
     */
    public function getType(): string
    {
        return "MailAccount";
    }


    /**
     * @return ObjectDescriptionList
     */
    public function getRelationships(): ObjectDescriptionList
    {
        return new ObjectDescriptionList();
    }


    /**
     * Returns all fields the entity exposes.
     *
     * @return string[]
     */
    public function getFields(): array
    {
        return [
            "name",
            "folderType",
            "from",
            "replyTo",
            "inbox_address",
            "inbox_port",
            "inbox_user",
            "inbox_password",
            "inbox_ssl",
            "outbox_address",
            "outbox_port",
            "outbox_user",
            "outbox_password",
            "outbox_secure"
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
            "name" => true,
            "folderType" => true,
            "from" => true,
            "replyTo" => true,
            "inbox_address" => true,
            "inbox_port" => true,
            "inbox_user" => true,
            "inbox_password" => true,
            "inbox_ssl" => true,
            "outbox_address" => true,
            "outbox_port" => true,
            "outbox_user" => true,
            "outbox_password" => true,
            "outbox_secure" => true
        ];
    }
}
