<?php

/**
 * This file is part of the conjoon/php-lib-conjoon project.
 *
 * (c) 2019-2024 Thorsten Suckow-Homberg <thorsten@suckow-homberg.de>
 *
 * For full copyright and license information, please consult the LICENSE-file distributed
 * with this source code.
 */

declare(strict_types=1);

namespace Tests;

use Conjoon\Mail\Client\Data\MailAccount;
use Conjoon\Mail\Client\Data\MailAccountList;
use Illuminate\Contracts\Auth\Authenticatable;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use ReflectionMethod;

/**
 * Trait TestTrait
 * @package Tests
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

        return $this->getMockBuilder(Authenticatable::class)
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

        $userStub = $this->getMockBuilder(Authenticatable::class)
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

        $mailAccountList = new MailAccountList();
        $mailAccountList[] = $this->getTestMailAccount("dev_sys_conjoon_org");
        $userStub->method("getMailAccounts")
                 ->willReturn($mailAccountList);

        return $userStub;
    }


    /**
     * @param $accountId
     * @return MailAccount|null
     */
    public function getTestMailAccount($accountId): ?MailAccount
    {

        if ($accountId === "testFail") {
            return null;
        }

        return new MailAccount([
            "id"              => $accountId,
            "name"            => "conjoon developer",
            "from"            => ["name" => "John Smith", "address" => "dev@conjoon.org"],
            "replyTo"         => ["name" => "John Smith", "address" => "dev@conjoon.org"],
            "inbox_type"      => "IMAP",
            "inbox_address"   => "server.inbox.com",
            "inbox_port"      => 993,
            "inbox_user"      => "inboxUser",
            "inbox_password"  => "inboxPassword",
            "inbox_ssl"       => true,
            "outbox_address"  => "server.outbox.com",
            "outbox_port"     => 993,
            "outbox_user"     => "outboxUser",
            "outbox_password" => "outboxPassword",
            "outbox_secure"   => "ssl"
        ]);
    }
}
