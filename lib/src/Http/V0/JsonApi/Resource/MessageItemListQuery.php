<?php

/**
 * conjoon
 * lumen-app-email
 * Copyright (C) 2021-2022 Thorsten Suckow-Homberg https://github.com/conjoon/lumen-app-email
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

use Conjoon\Core\Data\SortDirection;
use Conjoon\Core\Data\SortInfo;
use Conjoon\Core\Data\SortInfoList;
use Conjoon\Mail\Client\Data\Resource\MessageItemListQuery as BaseMessageItemListQuery;

/**
 * Class MessageItemListResourceQuery
 * @package App\Http\V0\Query\MessageItem
 */
class MessageItemListQuery extends BaseMessageItemListQuery
{
    /**
     * Returns the int-value of "page[start]".
     *
     * @return int
     */
    public function getStart(): int
    {
        return $this->getInt("page[start]") ?? 0;
    }


    /**
     * Returns the limit specified for this query.
     * Returns "null" if no limit was specified.
     *
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->getInt("page[limit]");
    }


    /**
     * Returns the fields that should be queried. If no fields where specified, this implementation
     * will return the default fields of the resource target for this query.
     *
     * @return array
     */
    public function getFields(): array
    {
        $defaultFields = $this->getResourceTarget()->getDefaultFields();

        $relfields = $this->{"relfield:fields[MessageItem]"};
        $fields    = $this->{"fields[MessageItem]"};

        if (!$relfields) {
            return $fields ? explode(",", $fields) : $defaultFields;
        }

        $relfields = explode(",", $relfields);

        foreach ($relfields as $relfield) {
            $prefix    = substr($relfield, 0, 1);
            $fieldName = substr($relfield, 1);

            if ($prefix === "-") {
                $defaultFields = array_filter($defaultFields, fn ($field) => $field !== $fieldName);
            } else {
                if (!in_array($fieldName, $defaultFields)) {
                    $defaultFields[] = $fieldName;
                }
            }
        }

        return $defaultFields;
    }


    /**
     * Returns sort information for this query.
     *
     * @return SortInfoList
     */
    public function getSort(): SortInfoList
    {
        $sortField = explode(",", $this->sort ?? "");

        $sortOrderList = new SortInfoList();
        foreach ($sortField as $field) {
            if (!$field) {
                continue;
            }
            $dir = SortDirection::ASC;
            if (str_starts_with($field, "-")) {
                $field = substr($field, 1);
                $dir = SortDirection::DESC;
            }
            $sort = new SortInfo($field, $dir);

            $sortOrderList[] = $sort;
        }

        return $sortOrderList;
    }


    /**
     * Returns the resource target of this query.
     *
     * @return MessageItem
     */
    function getResourceTarget(): MessageItem
    {
        return new MessageItem();
    }
}
