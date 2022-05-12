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
use Conjoon\Http\Query\QueryTranslator;
use Conjoon\Util\ArrayUtil;
use Illuminate\Http\Request;

/**
 * Class AbstractMessageItemQueryTranslator
 * @package App\Http\V0\Query\MessageItem
 */
abstract class AbstractMessageItemQueryTranslator extends QueryTranslator
{
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

        // merge config from previewText into $parsed
        $previewText = $parsed["previewText"] ?? [];
        if (isset($previewText["html"]) || isset($previewText["plain"])) {
            $parsed = array_merge($parsed, $previewText);
            unset($parsed["previewText"]);

            if (in_array("previewText", $target)) {
                isset($parsed["html"]) && ($target[] = "html");
                isset($parsed["plain"]) && ($target[] = "plain");
                $target = array_filter($target, fn ($item) => $item !== "previewText");
            }
        }

        foreach ($target as $attributeName) {
            $result[$attributeName] = $parsed[$attributeName] ?? ($default[$attributeName] ?? true);
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
            "previewText",
            "size",
            "hasAttachments",
            "cc",
            "bcc",
            "replyTo"
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
            "size" => true,
            "hasAttachments" => true,
            "cc" => true,
            "bcc" => true,
            "replyTo" => true,
            "html" =>  ["length" => 200, "trimApi" => true, "precedence" => true],
            "plain" => ["length" => 200, "trimApi" => true]
        ];
    }
}
