<?php

/**
* This file is part of the conjoon/lumen-app-email project.
*
* (c) 2020-2024 Thorsten Suckow-Homberg <thorsten@suckow-homberg.de>
*
* For full copyright and license information, please consult the LICENSE-file distributed
* with this source code.
*/

declare(strict_types=1);

namespace Tests\V0;

trait RoutesTrait
{
    protected function apiRoutesV0()
    {

        $routes = $this->app->router->getRoutes();

        $versions = ["v0", "latest"];
        $latest   = config("app.api.service.email.latest");
        $messageItemsEndpoint = "MailAccounts/{mailAccountId}/MailFolders/{mailFolderId:.*}/MessageItems";

        foreach ($versions as $version) {
            $testAuthsFor = [
                "GET/" . $this->getImapEndpoint("MailAccounts", $version),
                "GET/" . $this->getImapEndpoint("MailAccounts/{mailAccountId}/MailFolders", $version),
                "GET/" . $this->getImapEndpoint($messageItemsEndpoint, $version),
                "POST/" . $this->getImapEndpoint($messageItemsEndpoint, $version),
                "GET/" . $this->getImapEndpoint($messageItemsEndpoint . "/{messageItemId}/MessageBody", $version),
                "PATCH/" . $this->getImapEndpoint($messageItemsEndpoint . "/{messageItemId}/MessageBody", $version),
                "GET/" . $this->getImapEndpoint($messageItemsEndpoint . "/{messageItemId}", $version),
                "POST/" . $this->getImapEndpoint($messageItemsEndpoint . "/{messageItemId}", $version),
                "PATCH/" . $this->getImapEndpoint($messageItemsEndpoint . "/{messageItemId}/MessageItem", $version),
                "PATCH/" . $this->getImapEndpoint($messageItemsEndpoint . "/{messageItemId}/MessageDraft", $version),
                "DELETE/" . $this->getImapEndpoint($messageItemsEndpoint . "/{messageItemId}", $version),
                "GET/" . $this->getImapEndpoint(
                    $messageItemsEndpoint . "/{messageItemId}/Attachments",
                    $version
                ),
                "POST/" . $this->getImapEndpoint(
                    $messageItemsEndpoint . "/{messageItemId}/Attachments",
                    $version
                ),
                "DELETE/" . $this->getImapEndpoint(
                    $messageItemsEndpoint . "/{messageItemId}/Attachments/{id}",
                    $version
                )
            ];

            foreach ($testAuthsFor as $route) {
                $this->assertArrayHasKey($route, $routes);

                // "latest"-string will fall back to the current version being used
                $postfix = $version === "latest" ? ucfirst($latest) : ucfirst($version);
                $this->assertSame("auth_" . $postfix, $routes[$route]["action"]["middleware"][0]);
            }
        }
    }
}
