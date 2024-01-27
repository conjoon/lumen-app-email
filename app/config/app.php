<?php

/**
 * This file is part of the conjoon/lumen-app-email project.
 *
 * (c) 2019-2024 Thorsten Suckow-Homberg <thorsten@suckow-homberg.de>
 *
 * For full copyright and license information, please consult the LICENSE-file distributed
 * with this source code.
 */

declare(strict_types=1);

/**
 * @return array
 */
return [

/*
|--------------------------------------------------------------------------
| Api
|--------------------------------------------------------------------------
|
| Supported API versions by this installation of rest-api-email, along
| with the latest version that should be used as the version property
| if a client does not request a specific version.
|
*/
"api" => [
    "service" => [
        "email" => env("APP_EMAIL_PATH", "rest-api-email"),
        "auth"  => env("APP_AUTH_PATH", "rest-imapuser")
    ],
    "versionRegex" => "/\/(v[0-9]+)/mi",
    "versions" => ["v0"],
    "latest" => "v0",
    /**
     * ResourceQueryFactory should be able to handle this,
     * so no need to configure this upfront - 26.01.2024
     */
    "resourceUrls" => [
        ["regex" => "/(MailAccounts)(\/)?[^\/]*$/m", "nameIndex" => 1, "singleIndex" => 2],
        ["regex" => "/MailAccounts\/.+\/MailFolders\/.+\/(MessageBodies)(\/*.*$)/m", "nameIndex" => 1, "singleIndex" => 2],
        ["regex" => "/MailAccounts\/.+\/MailFolders\/.+\/(MessageItems)(\/*.*$)/m", "nameIndex" => 1, "singleIndex" => 2],
        ["regex" => "/MailAccounts\/.+\/(MailFolders)(\/)?[^\/]*$/m", "nameIndex" => 1, "singleIndex" => 2]
    ],
    "resourceDescriptionTpl" => "App\\Http\\{apiVersion}\\JsonApi\\Resource\\{0}",
    "resourceTpl" => [
        "urlPatterns" => [
            "MessageItem" => [
                "single" => "MailAccounts/{mailAccountId}/MailFolders/{mailFolderId}/MessageItems/{messageItem}",
                "collection" => "MailAccounts/{mailAccountId}/MailFolders/{mailFolderId}/MessageItems",
            ],
            "MessageBody" => [
                "single" => "MailAccounts/{mailAccountId}/MailFolders/{mailFolderId}/MessageBodies/{messageItem}",
                "collection" => "MailAccounts/{mailAccountId}/MailFolders/{mailFolderId}/MessageBodies",
            ],
            "MailFolder" => [
                "single" => "MailAccounts/{mailAccountId}/MailFolders/{mailFolderId}",
                "collection" => "MailAccounts/{mailAccountId}/MailFolders",
            ],
            "MailAccount" => [
                "single" => "MailAccounts/{mailAccountId}",
                "collection" => "MailAccounts",
            ]
        ],
        "repositoryPatterns" => [
            "validations" => [
                "single" => "App\\Http\\{apiVersion}\\JsonApi\\Query\\Validation\\{0}Validator",
                "collection" => "App\\Http\\{apiVersion}\\JsonApi\\Query\\Validation\\{0}CollectionValidator"
            ],
            "descriptions" =>  [
                "single" => "App\\Http\\{apiVersion}\\JsonApi\\Resource\\{0}",
                "collection" => "App\\Http\\{apiVersion}\\JsonApi\\Resource\\{0}List",
            ]
        ]
    ]
],


/*
|--------------------------------------------------------------------------
| Application Name
|--------------------------------------------------------------------------
|
| This value is the name of your application. This value is used when the
| framework needs to place the application's name in a notification or
| any other location as required by the application or its packages.
|
*/

"name" => env("APP_NAME", "lumen-app-email"),

/*
|--------------------------------------------------------------------------
| Application Environment
|--------------------------------------------------------------------------
|
| This value determines the "environment" your application is currently
| running in. This may determine how you prefer to configure various
| services the application utilizes. Set this in your ".env" file.
|
*/

"env" => env("APP_ENV", "production"),

/*
|--------------------------------------------------------------------------
| Application Debug Mode
|--------------------------------------------------------------------------
|
| When your application is in debug mode, detailed error messages with
| stack traces will be shown on every error that occurs within your
| application. If disabled, a simple generic error page is shown.
|
*/

"debug" => env("APP_DEBUG", false),

/*
|--------------------------------------------------------------------------
| Application URL
|--------------------------------------------------------------------------
|
| This URL is used by the console to properly generate URLs when using
| the Artisan command line tool. You should set this to the root of
| your application so that it is used when running Artisan tasks.
| The value of this property is also used throughout the application when
| controllers need to refer to the scheme and authority of the server
| this application is used with.
*/

"url" => env("APP_URL", "https://ddev-ms-email.ddev.site"),

/*
|--------------------------------------------------------------------------
| Application Timezone
|--------------------------------------------------------------------------
|
| Here you may specify the default timezone for your application, which
| will be used by the PHP date and date-time functions. We have gone
| ahead and set this to a sensible default for you out of the box.
|
*/

"timezone" => "UTC",

/*
|--------------------------------------------------------------------------
| Application Locale Configuration
|--------------------------------------------------------------------------
|
| The application locale determines the default locale that will be used
| by the translation service provider. You are free to set this value
| to any of the locales which will be supported by the application.
|
*/

"locale" => env("APP_LOCALE", "en"),

/*
|--------------------------------------------------------------------------
| Application Fallback Locale
|--------------------------------------------------------------------------
|
| The fallback locale determines the locale to use when the current one
| is not available. You may change the value to correspond to any of
| the language folders that are provided through your application.
|
*/

"fallback_locale" => env("APP_FALLBACK_LOCALE", "en"),

/*
|--------------------------------------------------------------------------
| Encryption Key
|--------------------------------------------------------------------------
|
| This key is used by the Illuminate encrypter service and should be set
| to a random, 32 character string, otherwise these encrypted strings
| will not be safe. Please do this before deploying an application!
|
*/

"key" => env("APP_KEY"),

"cipher" => "AES-256-CBC"

];
