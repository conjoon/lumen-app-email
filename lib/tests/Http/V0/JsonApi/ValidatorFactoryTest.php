<?php

/**
 * This file is part of the conjoon/lumen-app-email project.
 *
 * (c) 2024 Thorsten Suckow-Homberg <thorsten@suckow-homberg.de>
 *
 * For full copyright and license information, please consult the LICENSE-file distributed
 * with this source code.
 */

declare(strict_types=1);

namespace Tests\App\Http\V0\JsonApi;

use App\Http\V0\JsonApi\ValidatorFactory;
use Conjoon\JsonApi\Query\Validation\CollectionValidator;
use Conjoon\JsonApi\Query\Validation\Validator;
use Conjoon\Net\Url;
use Tests\TestCase;

/**
 * Test ValidatorFactory
 */
class ValidatorFactoryTest extends TestCase
{


    /**
     * @return void
     */
    public function testGetValidator(): void
    {
        self::assertInstanceOf(Validator::class, ValidatorFactory::getValidator(
            Url::make("http://localhost:8080/MailAccounts/1/MailFolders/2/MessageItems/3"),
            $this->getConfig()
        ));

        self::assertInstanceOf(CollectionValidator::class, ValidatorFactory::getValidator(
            Url::make("http://localhost:8080/MailAccounts/1/MailFolders/2/MessageItems"),
            $this->getConfig()
        ));
    }


    protected function getConfig(): array
    {
        return [
            "urlPatterns" => [
                "MessageItem" => [
                    "single" => "/MailAccounts/{mailAccountId}/MailFolders/{mailFolderId}/MessageItems/{messageItem}",
                    "collection" => "/MailAccounts/{mailAccountId}/MailFolders/{mailFolderId}/MessageItems",
                ]
            ],
            "repositoryPatterns" => [
                "single" => "App\\Http\\V0\\JsonApi\\Query\\Validation\\{0}Validator",
                "collection" => "App\\Http\\V0\\JsonApi\\Query\\Validation\\{0}CollectionValidator"
            ]

        ];
    }
}
