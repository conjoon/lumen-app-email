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

namespace Tests;

use Conjoon\Mail\Client\Data\MailAccount;
use Illuminate\Contracts\Auth\Authenticatable;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use ReflectionMethod;

/**
 * Trait for having access to authenticated User-stub in tests.
 *
 * Trait TestTrait
 */
trait TestTrait
{

    /**
     * Returns a MockObject serving as the user stub.
     *
     * @param array $pMethods
     *
     * @return MockObject
     */
    public function getTemplateUserStub(array $pMethods): MockObject
    {
        $methods = array_map(function (ReflectionMethod $m) {
            return $m->getName();
        }, (new ReflectionClass(Authenticatable::class))->getMethods());

        return $this->getMockBuilder("\Illuminate\Contracts\Auth\Authenticatable")
                    ->onlyMethods($methods)
                    ->addMethods($pMethods)
                    ->getMock();
    }

    /**
     * @return mixed
     */
    public function getTestUserStub()
    {

        $onlyMethods = array_map(function (ReflectionMethod $m) {
            return $m->getName();
        }, (new ReflectionClass(Authenticatable::class))->getMethods());
        $methods = ["getMailAccount", "getMailAccounts"];

        $userStub = $this->getMockBuilder("\Illuminate\Contracts\Auth\Authenticatable")
                         ->addMethods($methods)
                         ->onlyMethods($onlyMethods)
                         ->getMock();

        $userStub->method("getMailAccount")
                 ->with($this->callback(function ($arg) {
                     return is_string($arg);
                 }))
                 ->will($this->returnCallback(function ($accountId) {
                     return $this->getTestMailAccount($accountId);
                 }));

        $userStub->method("getMailAccounts")
                 ->willReturn([$this->getTestMailAccount("dev_sys_conjoon_org")]);

        return $userStub;
    }


    /**
     * @param $accountId
     * @return MailAccount|null
     */
    public function getTestMailAccount($accountId): ?MailAccount
    {

        if ($accountId === "TESTFAIL") {
            return null;
        }

        return new MailAccount([
            "id"              => $accountId,
            "name"            => "conjoon developer",
            "from"            => ["name" => "John Smith", "address" => "dev@conjoon.org"],
            "replyTo"         => ["name" => "John Smith", "address" => "dev@conjoon.org"],
            "inbox_type"      => "IMAP",
            "inbox_address"   => "sfsffs.ffssf.sffs",
            "inbox_port"      => 993,
            "inbox_user"      => "inboxuser",
            "inbox_password"  => "inboxpassword",
            "inbox_ssl"       => true,
            "outbox_address"  => "sfsffs.ffssf.sffs",
            "outbox_port"     => 993,
            "outbox_user"     => "outboxuser",
            "outbox_password" => "outboxpassword",
            "outbox_ssl"      => true
        ]);
    }
}
