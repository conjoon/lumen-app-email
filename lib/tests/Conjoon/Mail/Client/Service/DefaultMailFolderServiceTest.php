<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2019-2021 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

namespace Tests\Conjoon\Mail\Client\Service;

use Conjoon\Mail\Client\Folder\MailFolderChildList;
use Conjoon\Mail\Client\Folder\MailFolderList;
use Conjoon\Mail\Client\Folder\Tree\MailFolderTreeBuilder;
use Conjoon\Mail\Client\MailClient;
use Conjoon\Mail\Client\Service\DefaultMailFolderService;
use Conjoon\Mail\Client\Service\MailFolderService;
use Mockery;
use Tests\TestCase;
use Tests\TestTrait;

class DefaultMailFolderServiceTest extends TestCase
{
    use TestTrait;


    /**
     * Test the instance
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testInstance()
    {

        $service = $this->createService();
        $this->assertInstanceOf(MailFolderService::class, $service);
    }


    /**
     * Test getMailFolderChildList()
     *
     * Test expects the list returned by the MailFolderTreeBuilder to be
     * returned by the Service without changing anything.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @noinspection PhpUndefinedMethodInspection
     */
    public function testGetMailFolderChildList()
    {

        $mailAccount = $this->getTestMailAccount("dev");

        $service = $this->createService();

        $mailFolderList = new MailFolderList();

        $service->getMailClient()
                ->shouldReceive("getMailFolderList")
                ->with($mailAccount)
                ->andReturn($mailFolderList);


        $resultList = new MailFolderChildList();

        $service->getMailFolderTreeBuilder()
                ->shouldReceive("listToTree")
                ->with($mailFolderList, ["INBOX"])
                ->andReturn($resultList);

        $this->assertSame($resultList, $service->getMailFolderChildList($mailAccount));
    }


// +--------------------------------------
// | Helper
// +--------------------------------------
    /**
     * Helper function for creating the service.
     * @return DefaultMailFolderService
     */
    protected function createService(): DefaultMailFolderService
    {
        return new DefaultMailFolderService(
            $this->getMailClientMock(),
            $this->getMailFolderTreeBuilderMock()
        );
    }


    /**
     * Helper function for creating the client Mock.
     * @return mixed
     */
    protected function getMailClientMock()
    {

        return Mockery::mock("overload:" . MailClient::class);
    }


    /**
     * Helper function for creating the client Mock.
     * @return mixed
     */
    protected function getMailFolderTreeBuilderMock()
    {

        return Mockery::mock("overload:" . MailFolderTreeBuilder::class);
    }
}
