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

use App\Http\V0\JsonApi\ObjectDescriptionFactory;
use Conjoon\Data\Resource\ObjectDescription;
use Conjoon\Data\Resource\ObjectDescriptionList;
use Conjoon\Net\Url;
use Tests\TestCase;

/**
 * Test ObjectDescription
 */
class ObjectDescriptionFactoryTest extends TestCase
{


    /**
     * @return void
     */
    public function testGetObjectDescription(): void
    {
        self::assertInstanceOf(ObjectDescription::class, ObjectDescriptionFactory::getObjectDescription(
            Url::make("http://localhost:8080/MailAccounts/1/MailFolders/2/MessageItems/3"),
            $this->getConfig()
        ));

        self::assertInstanceOf(ObjectDescriptionList::class, ObjectDescriptionFactory::getObjectDescription(
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
                "descriptions" => [
                    "single" => "App\\Http\\V0\\JsonApi\\Resource\\{0}",
                    "collection" => "App\\Http\\V0\\JsonApi\\Resource\\{0}List"
                ]
            ]
        ];
    }
}
