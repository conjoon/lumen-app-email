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
use Conjoon\Net\Uri\Component\Path\Template;
use Conjoon\Net\Url;
use Conjoon\JsonApi\Query\Validation\Validator;

 /**
  * The ValidatorFactory creates Validator-objects based on the specified configuration.
  *
  * @example
  *
  *   $cfg = [
  *       "urlPatterns" => [
  *         "MessageItems" => [
  *             "single" => "/MailAccounts/{mailAccountId}/MailFolders/{mailFolderId}/MessageItems/{messageItem}",
  *             "collection" => "/MailAccounts/{mailAccountId}/MailFolders/{mailFolderId}/MessageItems",
  *         ]
  *     ],
  *     "repositoryPatterns" => [
  *         "validations" => [
  *          "single" => "App\\Http\\V0\\JsonApi\\Query\\Validation\\{0}Validator",
  *          "collection" => "App\\Http\\V0\\JsonApi\\Query\\Validation\\{0}CollectionValidator"
  *       ]
  *     ]
  *  ];
  *
  *   // resolved to classname "App\Http\V0\JsonApi\Query\Validation\MessageItemValidator"
  *   // If an attempt to create an object fails, an exception is thrown.
  *  ValidatorFactory::getValidator(
  *     Url::make("http://localhost:8080/MailAccounts/1/MailFolders/2/MessageItems/3"),
  *     $cfg
  *  );
  *
  *  // ... same for MessageItemCollectionValidator:
  *  ValidatorFactory::getValidator(
  *      Url::make("http://localhost:8080/MailAccounts/1/MailFolders/2/MessageItems"),
  *      $cfg
  *   );
  */
class ValidatorFactory {

    public static function getValidator(Url $url, array $config): ?Validator
    {

        $classLoader = new ClassLoader();
        $singleRepository = $config["repositoryPatterns"]["validations"]["single"];
        $multiRepository = $config["repositoryPatterns"]["validations"]["collection"];

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

            if ($isCollection) {
                return $classLoader->create(
                    str_replace("{0}", $resource, $multiRepository),
                    Validator::class);
            }

            return $classLoader->create(
                str_replace("{0}", $resource, $singleRepository),
                Validator::class
            );

        }

        return null;
    }

}
