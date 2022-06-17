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

namespace App\Http\V0\Query\MessageItem;

use Conjoon\Core\ParameterBag;
use Conjoon\Http\Query\InvalidParameterResourceException;
use Conjoon\Http\Query\InvalidQueryException;
use Conjoon\Util\ArrayUtil;
use Illuminate\Http\Request;

/**
 * Class IndexRequestQueryTranslator
 * @package App\Http\V0\Query\MessageItem
 */
class IndexRequestQueryTranslator extends AbstractMessageItemQueryTranslator
{
    /**
     * @inheritdoc
     * @noinspection PhpUndefinedFieldInspection
     */
    protected function translateParameters(ParameterBag $source): MessageItemListResourceQuery
    {

        $bag = new ParameterBag($source->toJson());


        if ($bag->include && $bag->getString("include") !== "MailFolders") {
            throw new InvalidQueryException(
                "parameter \"include\" must be set to \"MailFolders\", or omitted"
            );
        }

        $types = ["MessageItem"];
        if ($bag->include === "MailFolders") {
            $types[] = "MailFolder";
        }
        $bag->fields = [];

        foreach ($types as $type) {
            $fieldOptions = [];

            $fields = $this->parseFields($bag, $type);
            if ($type === "MessageItem") {
                $fieldOptions = $this->parseMessageItemFieldOptions($bag);
            }

            $bag->fields = array_merge(
                $bag->fields,
                [
                $type => $this->mapConfigToFields(
                    $fields,
                    $fieldOptions,
                    $this->getDefaultFields($type),
                    $type
                )]
            );

            unset($bag->{"fields[$type]"});
        }

        $ids = null;
        if ($bag->getString("filter")) {
            $bag->filter = json_decode($bag->filter, true);
            if (!$bag->filter) {
                throw new InvalidQueryException(
                    "parameter \"filter\" must be JSON decodable"
                );
            }

            foreach ($bag->filter as $filterEntry) {
                if (
                    isset($filterEntry["property"]) && strtolower($filterEntry["property"]) === "id" &&
                    isset($filterEntry["operator"]) && strtolower($filterEntry["operator"]) === "in"
                ) {
                    $ids = $filterEntry["value"] ?? null;
                }
            }
        }



        if (!$ids) {
            if (!$bag->getInt("limit")) {
                throw new InvalidQueryException(
                    "parameter \"limit\" must not be omitted"
                );
            }

            if ($bag->getInt("limit") <= 0) {
                $bag->limit = -1;
            }

            if ($bag->getInt("start") < 0) {
                throw new InvalidQueryException(
                    "parameter \"start\" must not be < 0"
                );
            }

            $bag->start = $bag->getInt("start") ?? 0;
            $bag->limit = $bag->getInt("limit");
        }

        if (!$bag->sort) {
            $bag->sort = $this->getDefaultSort();
        } elseif (is_string($bag->sort)) {
            $bag->sort = json_decode($bag->sort);
        }


        return new MessageItemListResourceQuery($bag);
    }


    /**
     * Parses json-encoded options.
     *
     * @param ParameterBag $bag
     * @return array
     * @noinspection PhpUndefinedFieldInspection
     */
    protected function parseMessageItemFieldOptions(ParameterBag $bag): array
    {
        $options = $bag->options;
        unset($bag->options);
        if (!$options) {
            return [];
        }

        return json_decode($options, true);
    }


    /**
     * Returns the default sort to use, if not available via parameters.
     *
     * @return array
     */
    protected function getDefaultSort(): array
    {
        return [["property" => "date", "direction" => "DESC"]];
    }


    /**
     * @inheritdoc
     */
    protected function getExpectedParameters(): array
    {
        return [
            "limit",
            "start",
            "sort",
            "include",
            "fields[MessageItem]",
            "fields[MailFolder]",
            "options",
            "filter"
        ];
    }
}
