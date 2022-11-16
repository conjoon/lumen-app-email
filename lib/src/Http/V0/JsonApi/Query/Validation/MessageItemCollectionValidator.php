<?php

/**
 * conjoon
 * lumen-app-email
 * Copyright (c) 2022 Thorsten Suckow-Homberg https://github.com/conjoon/lumen-app-email
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

namespace App\Http\V0\JsonApi\Query\Validation;

use Conjoon\Http\Query\Validation\Parameter\IntegerValueRule;
use Conjoon\Http\Query\Validation\Parameter\ParameterRuleList;
use Conjoon\Http\Query\Validation\Query\ExclusiveGroupKeyRule;
use Conjoon\Http\Query\Validation\Query\OnlyParameterNamesRule;
use Conjoon\Http\Query\Validation\Query\QueryRuleList;
use Conjoon\Http\Query\Validation\Query\RequiredParameterNamesRule;
use Conjoon\JsonApi\Extensions\Query\Validation\Parameter\RelfieldRule;
use Conjoon\JsonApi\Query\Query;
use Conjoon\JsonApi\Query\Validation\CollectionValidator;
use Conjoon\Http\Query\Query as HttpQuery;
use Conjoon\JsonApi\Query\Validation\Parameter\PnFilterRule;

/**
 * Query Validator for MessageItem collection requests.
 *
 */
class MessageItemCollectionValidator extends CollectionValidator
{
    public function getParameterRules(HttpQuery $query): ParameterRuleList
    {
        $resourceTarget = $query->getResourceTarget();

        $include  = $query->getParameter("include");
        $includes = $include
            ? $this->unfoldInclude($include)
            : [];


        $list = parent::getParameterRules($query);
        $list[] = new IntegerValueRule("page[start]", ">=", 0);
        $list[] = new IntegerValueRule("page[limit]", ">=", 1);
        $list[] = new RelfieldRule(
            $resourceTarget->getAllRelationshipResourceDescriptions(true),
            $includes,
            false
        );
        $list[] = new PnFilterRule($this->getAvailableFields($resourceTarget));

        return $list;
    }


    public function getAllowedParameterNames(HttpQuery $query): array
    {
        $names = parent::getAllowedParameterNames($query);
        $res = ["filter", "page[start]", "page[limit]"];
        foreach ($names as $param) {
            if (substr($param, 0, 7) === "fields[") {
                $res[] = "relfield:$param";
            } else {
                $res[] = $param;
            }
        }

        return $res;
    }


    public function getQueryRules(HttpQuery $query): QueryRuleList
    {
        $list = parent::getQueryRules();
        $list[] = new ExclusiveGroupKeyRule(["fields", "relfield:fields"]);

        return $list;
    }
}
