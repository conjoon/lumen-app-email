<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2022 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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
use Conjoon\Util\ArrayUtil;
use Illuminate\Http\Request;

/**
 * Class GetRequestQueryTranslator
 * @package App\Http\V0\Query\MessageItem
 */
class GetRequestQueryTranslator extends AbstractMessageItemQueryTranslator
{

    /**
     * @inheritdoc
     * @noinspection PhpUndefinedFieldInspection
     */
    protected function translateParameters(ParameterBag $source): MessageItemListResourceQuery
    {

        $bag = new ParameterBag($source->toJson());
        $attributes = $this->parseAttributes($bag);

        $bag->attributes = $this->mapConfigToAttributes(
            $attributes,
            [],
            $this->getDefaultAttributes()
        );

        $bag->filter = [["property" => "id",
            "value" => [$bag->getString("messageItemId")],
            "operator" => "in"
        ]];

        unset($bag->messageItemId);

        return new MessageItemListResourceQuery($bag);
    }


    /**
     * @inheritdocs
     */
    protected function extractParameters($parameterResource): array
    {

        $data = parent::extractParameters($parameterResource);

        // use the one messageItemId available with the path parameter
        unset($data["messageItemId"]);

        return array_merge(
            $data,
            ["messageItemId" => $parameterResource->route("messageItemId")]
        );
    }


    /**
     * @inheritdoc
     */
    protected function getExpectedParameters(): array
    {
        return [
            "attributes",
            "messageItemId"
        ];
    }
}
