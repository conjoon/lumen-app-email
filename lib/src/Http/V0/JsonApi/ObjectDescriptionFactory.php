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

namespace App\Http\V0\JsonApi;

use Conjoon\Core\Util\ClassLoader;
use Conjoon\Data\Resource\ObjectDescription;
use Conjoon\Data\Resource\ObjectDescriptionList;
use Conjoon\Net\Uri\Component\Path\Template;
use Conjoon\Net\Url;
use Conjoon\JsonApi\Query\Validation\Validator;

 /**
  * The ObjectDescriptionFactory creates ObjectDescription-objects based on the specified configuration.
  *
  * $cfg = [
  * "urlPatterns" => [
  *     "MessageItems" => [
  *      "single" => "/MailAccounts/{mailAccountId}/MailFolders/{mailFolderId}/MessageItems/{messageItem}",
  *      "collection" => "/MailAccounts/{mailAccountId}/MailFolders/{mailFolderId}/MessageItems",
  *     ]
  * ],
  * "repositoryPatterns" => [
  *     "descriptions" => [
  *     "single" => "App\\Http\\{apiVersion}\\JsonApi\\Resource\\{0}",
  *     "collection" => "App\\Http\\{apiVersion}\\JsonApi\\Resource\\{0}List",
  *     ]
  *     ]
  * ];
  *
  * // resolved to classname "App\Http\V0\JsonApi\Resource\MessageItem"
  * // If an attempt to create an object fails, an exception is thrown.
  * ObjectDescriptionFactory::getValidator(
  * Url::make("http://localhost:8080/MailAccounts/1/MailFolders/2/MessageItems/3"),
  * $cfg
  * );
  *
  * // ... same for MessageItemListr:
  * ObjectDescriptionFactory::getValidator(
  * Url::make("http://localhost:8080/MailAccounts/1/MailFolders/2/MessageItems"),
  * $cfg
  * );
  */
class ObjectDescriptionFactory {

    public static function getObjectDescription(Url $url, array $config): ObjectDescription|ObjectDescriptionList|null
    {

        $classLoader = new ClassLoader();
        $singleRepository = $config["repositoryPatterns"]["descriptions"]["single"];
        $collectionRepository = $config["repositoryPatterns"]["descriptions"]["collection"];

        foreach ($config["urlPatterns"] as $resource => $tplEntry) {

            $isCollection = false;
            $template = new Template($tplEntry["single"]);
            $matchValue = $template->match($url);

            if (empty($matchValue)) {
                $template = new Template($tplEntry["collection"]);
                $matchValue = $template->match($url);
                $isCollection = true;
            }

            if ($matchValue === null) {
                continue;
            }


           return $classLoader->create(
                str_replace(
                    "{0}",
                    $resource,
                    $isCollection ? $collectionRepository : $singleRepository
                ),
                $isCollection ? ObjectDescriptionList::class : ObjectDescription::class
            );

        }

        return null;
    }

}
