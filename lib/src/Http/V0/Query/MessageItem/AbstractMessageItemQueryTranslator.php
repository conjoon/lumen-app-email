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

use App\Http\V0\Resource\MessageItemDescription;
use Conjoon\Core\ParameterBag;
use Conjoon\Http\Query\JsonApiQueryTranslator;
use Conjoon\Http\Resource\ResourceObjectDescription;
use Conjoon\Util\ArrayUtil;

;

/**
 * Class AbstractMessageItemQueryTranslator
 * @package App\Http\V0\Query\MessageItem
 */
abstract class AbstractMessageItemQueryTranslator extends JsonApiQueryTranslator
{
    /**
     * @return ResourceObjectDescription
     */
    public function getResourceTarget(): ResourceObjectDescription
    {
        return new MessageItemDescription();
    }


    /**
     * @param ParameterBag $bag
     * @param string $type
     * @return array
     */
    protected function extractFieldOptions(ParameterBag $bag, string $type): array
    {
        if ($type !== "MessageItem") {
            return [];
        }
        $options = $bag->options;
        unset($bag->options);
        if (!$options) {
            return [];
        }

        return json_decode($options, true);
    }


    /**
     * @inheritdoc
     */
    protected function mapConfigToFields(array $fields, array $fieldOptions, string $type): array
    {
        $default = $this->getDefaultFields($type);

        $result = [];

        if ($type === "MessageItem") {
            // merge config from previewText into $parsed
            $previewText = $fieldOptions["previewText"] ?? [];
            if (isset($previewText["html"]) || isset($previewText["plain"])) {
                $fieldOptions = array_merge($fieldOptions, $previewText);
                unset($fieldOptions["previewText"]);

                if (in_array("previewText", $fields)) {
                    isset($fieldOptions["html"]) && ($fields[] = "html");
                    isset($fieldOptions["plain"]) && ($fields[] = "plain");
                    $fields = array_values(array_filter($fields, fn ($item) => $item !== "previewText"));
                }
            }
        }

        foreach ($fields as $fieldName) {
            $result[$fieldName] = $fieldOptions[$fieldName] ?? ($default[$fieldName] ?? true);
        }

        if ($type === "MessageItem" && isset($result["previewText"])) {
            if (!is_array($result["previewText"])) {
                $result["html"]  = $default["html"];
                $result["plain"]  = $default["plain"];
            } else {
                $result["html"]  = ArrayUtil::unchain("previewText.html", $result, $result["previewText"]);
                $result["plain"] = ArrayUtil::unchain("previewText.plain", $result, $result["previewText"]);
            }

            unset($result["previewText"]);
        }

        return $result;
    }
}
