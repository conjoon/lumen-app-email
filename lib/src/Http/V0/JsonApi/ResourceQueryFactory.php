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

namespace App\Http\V0\JsonApi;

use Conjoon\Core\Util\ClassLoader;
use App\Http\V0\JsonApi\Resource\Query as QueryPool;
use Conjoon\Data\ParameterBag;
use Conjoon\Data\Resource\ResourceQuery;
use Conjoon\JsonApi\Request\Request as JsonApiRequest;
use Conjoon\MailClient\Data\Resource\Query as QueryParent;

/**
 * ResourceQueryFactory responsible for dynamically loading ResourceQueries based on the
 * JsonApiRequest-$request submitted to #createQueryFromRequest().
 */
class ResourceQueryFactory
{
    /**
     * @var ClassLoader
     */
    protected readonly ClassLoader $classLoader;

    /**
     * @param ClassLoader $classLoader
     */
    public function __construct(ClassLoader $classLoader)
    {
        $this->classLoader = $classLoader;
    }


    /**
     * Returns the ResourceQuery that can be used for forwarding to sub-systems
     * expecting a declarative API.
     *
     * @param JsonApiRequest $request
     *
     * @return ResourceQuery|null
     */
    public function createQueryFromRequest(JsonApiRequest $request): ?ResourceQuery
    {
        $loader = $this->getClassLoader();

        $target = $request->getResourceTarget();
        $resourceType = $target->getType();
        $targetsResourceCollection = $request->targetsResourceCollection();

        $parameters = $request->getUrl()->getQuery()->getParameterBag() ?? new ParameterBag();

        $type     = $targetsResourceCollection ? "collection" : "single";
        $loadList = [
            "MessageItem" => [
                "single"     => [QueryPool\MessageItemQuery::class, QueryParent\MessageItemQuery::class],
                "collection" => [QueryPool\MessageItemListQuery::class, QueryParent\MessageItemListQuery::class]
            ]
        ];

        return $loader->load(...array_merge($loadList[$resourceType][$type], [[$parameters]]));
    }


    /**
     * @return ClassLoader
     */
    public function getClassLoader(): ClassLoader
    {
        return $this->classLoader;
    }
}
