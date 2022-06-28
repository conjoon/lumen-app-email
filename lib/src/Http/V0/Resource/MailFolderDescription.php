<?php

/**
 * conjoon
 * php-lib-conjoon
 * Copyright (C) 2022 Thorsten Suckow-Homberg https://github.com/conjoon/php-lib-conjoon
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

namespace App\Http\V0\Resource;

use Conjoon\Http\Resource\ResourceObjectDescription;
use Conjoon\Http\Resource\ResourceObjectDescriptionList;

/**
 * ResourceDescription for a MailFolder.
 *
 */
class MailFolderDescription extends ResourceObjectDescription
{
    /**
     * @return string
     */
    public function getType(): string
    {
        return "MailFolder";
    }


    /**
     * @return ResourceObjectDescriptionList
     */
    public function getRelationships(): ResourceObjectDescriptionList
    {
        $list = new ResourceObjectDescriptionList();
        $list[] = new MailAccountDescription();

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
            "name",
            "data",
            "folderType",
            "unreadMessages",
            "totalMessages"
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
            "data" => true,
            "folderType" => true,
            "unreadMessages" => true,
            "totalMessages" => true
        ];
    }
}
