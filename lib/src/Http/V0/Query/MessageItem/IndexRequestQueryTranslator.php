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

namespace App\Http\V0\Query\MessageItem;

use Conjoon\Core\ParameterBag;
use Conjoon\Http\Query\InvalidParameterResourceException;
use Conjoon\Http\Query\InvalidQueryException;
use Conjoon\Http\Query\QueryTranslator;
use Conjoon\Util\ArrayUtil;
use Illuminate\Http\Request;

/**
 * Class IndexRequestQueryTranslator
 * @package App\Http\V0\Query\MessageItem
 */
class IndexRequestQueryTranslator extends QueryTranslator
{

    /**
     * @inheritdoc
     * @noinspection PhpUndefinedFieldInspection
     */
    protected function translateParameters(ParameterBag $source): MessageItemListResourceQuery
    {

        $bag = new ParameterBag($source->toJson());

        $attributes = $this->parseAttributes($bag);
        $attributeOptions = $this->parseAttributeOptions($bag);

         $bag->attributes = $this->mapConfigToAttributes(
             $attributes,
             $attributeOptions,
             $this->getDefaultAttributes()
         );

        if ($bag->getString("filter")) {
            $bag->filter = json_decode($bag->filter);
            if (!$bag->filter) {
                throw new InvalidQueryException(
                    "parameter \"filter\" must be JSON decodable"
                );
            }
        }

        if (!$bag->getString("ids")) {
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
        } else {
            $bag->ids = explode(",", $bag->getString("ids"));
        }

        if (!$bag->sort) {
            $bag->sort = $this->getDefaultSort();
        } elseif (is_string($bag->sort)) {
            $bag->sort = json_decode($bag->sort);
        }


        return new MessageItemListResourceQuery($bag);
    }

    /**
     * Maps required configurations to passed attributes not available in the target
     * entity.
     *
     * @param $target
     * @param $parsed
     * @param $default
     * @return array
     */
    protected function mapConfigToAttributes($target, $parsed, $default): array
    {

        $result = [];
        foreach ($target as $attributeName) {
            $result[$attributeName] = $parsed[$attributeName] ??
                ($default[$attributeName] ?? true);
        }

        if (isset($result["previewText"])) {
            if (!is_array($result["previewText"])) {
                $result["html"]  = $this->getDefaultAttributes()["html"];
                $result["plain"]  = $this->getDefaultAttributes()["plain"];
            } else {
                $result["html"]  = ArrayUtil::unchain("previewText.html", $result, $result["previewText"]);
                $result["plain"] = ArrayUtil::unchain("previewText.plain", $result, $result["previewText"]);
            }

            unset($result["previewText"]);
        }

        return $result;
    }


    /**
     * Parses json-encoded options.
     *
     * @param ParameterBag $bag
     * @return array
     * @noinspection PhpUndefinedFieldInspection
     */
    protected function parseAttributeOptions(ParameterBag $bag): array
    {
        $options = $bag->options;
        unset($bag->options);
        if (!$options) {
            return [];
        }
        return json_decode($options, true);
    }


    /**
     * Parses and builds up the attribute list.
     *
     * @param ParameterBag $bag
     * @return string[]
     *
     * @noinspection PhpUndefinedFieldInspection
     */
    protected function parseAttributes(ParameterBag $bag): array
    {
        if (!$bag->attributes) {
            return $this->getAttributes();
        }
        $attributes = explode(",", $bag->attributes);
        if (in_array("*", $attributes) !== false) {
            $excludes   = array_filter($attributes, fn ($item) => $item !== "*");
            $attributes = array_filter($this->getAttributes(), fn ($item) => !in_array($item, $excludes));
        } else {
            $notAllowed = $this->hasOnlyAllowedAttributes($attributes ?? []);
            if (!empty($notAllowed)) {
                throw new InvalidQueryException(
                    "parameter \"attributes\" has unknown entries: " . implode(",", $notAllowed)
                );
            }

            if (in_array("previewText", $attributes)) {
                $attributes = array_filter($attributes, fn($item) => $item !== "previewText");
                $attributes[] = "html";
                $attributes[] = "plain";
            }
        }

        return $attributes;
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
     * @inheritdocs
     */
    protected function extractParameters($parameterResource): array
    {
        if (!($parameterResource instanceof Request)) {
            throw new InvalidParameterResourceException(
                "Expected \"parameterResource\" to be instance of {Illuminate::class}"
            );
        }
        return $parameterResource->only($this->getExpectedParameters());
    }


    /**
     * @inheritdoc
     */
    protected function getExpectedParameters(): array
    {
        return [
            "limit",
            "ids",
            "start",
            "sort",
            "attributes",
            "options",
            "filter"
        ];
    }


    /**
     * Default attributes to pass to the lower level api.
     *
     * @return array
     */
    protected function getDefaultAttributes(): array
    {
        return [
            "from" => true,
            "to" => true,
            "subject" => true,
            "date" => true,
            "seen" => true,
            "answered" => true,
            "draft" => true,
            "flagged" => true,
            "recent" => true,
            "charset" => true,
            "references" => true,
            "messageId" => true,
            "html" =>  ["length" => 200, "trimApi" => true, "precedence" => true],
            "plain" => ["length" => 200, "trimApi" => true]
        ];
    }


    /**
     * Checks if there are attributes which are not part of the attributes defined for the
     * entity the translator should process the parameters for.
     *
     * @param $received
     * @return array
     */
    protected function hasOnlyAllowedAttributes($received): array
    {
        return array_diff($received, $this->getAttributes());
    }


    /**
     * Returns all attributes the entity exposes.
     *
     * @return string[]
     */
    protected function getAttributes(): array
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
            "previewText"
        ];
    }
}
