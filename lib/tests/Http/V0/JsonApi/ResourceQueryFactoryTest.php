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

namespace Tests\App\Http\V0\JsonApi;

;

use Conjoon\Core\Util\ClassLoader;
use App\Http\V0\JsonApi\Resource\Query as QueryPool;
use Conjoon\Data\ParameterBag;
use Conjoon\JsonApi\Request\Request as JsonApiRequest;
use Conjoon\MailClient\Data\Resource\Query as QueryParent;
use App\Http\V0\JsonApi\ResourceQueryFactory;
use Conjoon\Data\Resource\ObjectDescription;
use Conjoon\JsonApi\Query\Query as JsonApiQuery;
use Conjoon\JsonApi\Request\Url as JsonApiUrl;
use ReflectionException;
use Tests\TestCase;

/**
 * Test ResourceQueryFactory
 */
class ResourceQueryFactoryTest extends TestCase
{

    /**
     * Tests class functionality
     * @return void
     */
    public function testClass()
    {
        $classLoader = new ClassLoader();
        $resourceQueryFactory = new ResourceQueryFactory($classLoader);
        $this->assertSame($classLoader, $resourceQueryFactory->getClassLoader());
    }


    /**
     * Tests createQueryFromRequest()
     *
     * @return void
     * @throws ReflectionException
     */
    public function testCreateQueryFromRequest()
    {
        $loadList = [
            "MessageItem_single" => [
                QueryPool\MessageItemQuery::class, QueryParent\MessageItemQuery::class, new ParameterBag()
            ],
            "MessageItem_collection" => [
                QueryPool\MessageItemListQuery::class, QueryParent\MessageItemListQuery::class, new ParameterBag()
            ]
        ];
        $runs = count(array_keys($loadList));
        $currentKey = "";

        $query = $this->getMockBuilder(JsonApiQuery::class)
                ->disableOriginalConstructor()
                ->onlyMethods(["getParameterBag"])->getMock();

        $query->expects($this->any())->method("getParameterBag")->willReturnCallback(
            function () use ($loadList, &$currentKey) {
                return $loadList[$currentKey][2];
            }
        );

        $url = $this->getMockBuilder(JsonApiUrl::class)
            ->disableOriginalConstructor()
            ->onlyMethods(["getQuery"])
            ->getMock();
        $url->expects($this->any())->method("getQuery")
            ->willReturn($query);


        $request = $this->getMockBuilder(JsonApiRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(["getUrl", "getResourceTarget", "targetsResourceCollection"])
            ->getMock();


        $request->expects($this->exactly($runs))->method("getUrl")->willReturn($url);

        $request->expects($this->exactly($runs))->method("getResourceTarget")->willReturnCallback(
            function () use (&$currentKey) {
                $resourceTarget = $this->createMockForAbstract(ObjectDescription::class, ["getType"]);
                $resourceTarget->expects($this->once())->method("getType")->willReturn(
                    explode("_", $currentKey)[0]
                );
                return $resourceTarget;
            }
        );
        $request->expects($this->exactly($runs))->method("targetsResourceCollection")->willReturnCallback(
            function () use (&$currentKey) {
                return str_contains($currentKey, "collection");
            }
        );


        $resourceQueryFactory = new ResourceQueryFactory(new ClassLoader());
        foreach ($loadList as $index => $item) {
            $currentKey = $index;
            $result = $resourceQueryFactory->createQueryFromRequest($request);
            $this->assertInstanceOf($item[0], $result);
            $this->assertInstanceOf($item[1], $result);
            $pb = $this->makeAccessible($result, "parameterBag", true);
            $this->assertSame($item[2], $pb->getValue($result));
        }
    }
}
