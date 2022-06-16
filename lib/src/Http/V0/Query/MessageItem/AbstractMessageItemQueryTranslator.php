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
     * Parses and builds up the field list.
     *
     * @param ParameterBag $bag
     * @return string[]
     *
     * @noinspection PhpUndefinedFieldInspection
     */
    protected function parseFields(ParameterBag $bag, string $type): array
    {

        // must be set before parseFields is called to make sure it does not fall back to default
        if (!$bag->getString("fields[$type]")) {
            return $this->getFields($type);
        }
        $fields = explode(",", $bag->getString("fields[$type]"));
        if (in_array("*", $fields) !== false) {
            $excludes   = array_filter($fields, fn ($item) => $item !== "*");
            $fields = array_filter($this->getFields($type), fn ($item) => !in_array($item, $excludes));
        } else {
            $notAllowed = $this->hasOnlyAllowedFields($fields ?? [], $type);
            if (!empty($notAllowed)) {
                throw new InvalidQueryException(
                    "parameter \"fields[$type]\" has unknown entries: " . implode(",", $notAllowed)
                );
            }

            if (in_array("previewText", $fields)) {
                $fields = array_filter($fields, fn($item) => $item !== "previewText");
                $fields[] = "html";
                $fields[] = "plain";
            }
        }

        return $fields;
    }


    /**
     * Maps required configurations to passed fields not available in the target
     * entity.
     *
     * @param $target
     * @param $parsed
     * @param $default
     * @param string $type
     *
     *
     * @return array
     */
    protected function mapConfigToFields($target, $parsed, $default, $type): array
    {

        $result = [];

        if ($type === "MessageItem") {
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
        }

        foreach ($target as $fieldName) {
            $result[$fieldName] = $parsed[$fieldName] ?? ($default[$fieldName] ?? true);
        }

        if ($type === "MessageItem" && isset($result["previewText"])) {
            if (!is_array($result["previewText"])) {
                $result["html"]  = $this->getDefaultFields($type)["html"];
                $result["plain"]  = $this->getDefaultFields($type)["plain"];
            } else {
                $result["html"]  = ArrayUtil::unchain("previewText.html", $result, $result["previewText"]);
                $result["plain"] = ArrayUtil::unchain("previewText.plain", $result, $result["previewText"]);
            }

            unset($result["previewText"]);
        }

        return $result;
    }

    /**
     * Checks if there are fields which are not part of the fields defined for the
     * entity the translator should process the parameters for.
     *
     * @param $received
     * @param string $type
     *
     * @return array
     */
    protected function hasOnlyAllowedFields($received, $type): array
    {
        return array_diff($received, $this->getFields($type));
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
     * Returns all fields the entity exposes.
     *
     * @param string $type The entity type for which the fields should be returned.
     *
     * @return string[]
     */
    protected function getFields(string $type): array
    {
        $fieldsets = [
            "MessageItem" => [
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
            ],
            "MailFolder" => [
                "name",
                "folderType",
                "unreadMessages",
                "totalMessages"
            ]
        ];

        return $fieldsets[$type];
    }


    /**
     * Default fields to pass to the lower level api.
     *
     * @return array
     */
    protected function getDefaultFields(string $type): array
    {
        $fieldsets = [
            "MessageItem" => [
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
            ],
            "MailFolder" => [
                "name" => true,
                "folderType" => true,
                "unreadMessages" => true,
                "totalMessages" => true
            ]
        ];

        return $fieldsets[$type];
    }
}
