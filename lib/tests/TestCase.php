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

namespace Tests;

use App\Exceptions\Handler;
use Conjoon\Mail\Client\Data\MailAccount;
use Conjoon\Mail\Client\Service\AuthService;
use Exception;
use Laravel\Lumen\Application;
use RuntimeException;
use Illuminate\Http\Response;
use Throwable;

/**
 * Class TestCase
 * @package Tests
 */
abstract class TestCase extends \Laravel\Lumen\Testing\TestCase
{
    protected bool $useFakeAuth = true;

    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication(): Application
    {
        $app = require __DIR__ . "/../../app/bootstrap/app.php";

        if ($this->useFakeAuth) {
            $app->singleton(AuthService::class, function () {
                return new class implements AuthService {
                    public function authenticate(MailAccount $mailAccount): bool
                    {
                        return true;
                    }
                };
            });
        }
        return $app;
    }


    /**
     * Returns the string used as prefix for the services.
     *
     * @param string $type
     * @param string $version
     *
     * @return string The relative path to the endpoints used with this api.
     *
     * @throws RuntimeException $type is neither "imap" nor "imapuser"
     */
    public function getServicePrefix(string $type, string $version): string
    {

        $mapping = [
            "imap" => config("app.api.service.email"),
            "imapuser"  => config("app.api.service.imapuser")
        ];

        $type = strtolower($type);
        if (!in_array($type, ["imap", "imapuser"])) {
            throw new RuntimeException("\"$type\" is not valid");
        }

        return $mapping[$type]["path"] . (($version === "latest") ? "" : "/" . $version);
    }


    /**
     * Returns the relative path to the rest-imapuser endpoint.
     *
     * @param string $endpoint
     * @param string $version
     * @return string The relative path to the endpoint according to the api version used with this
     * tests.
     *
     * @see #getServicePrefix
     */
    public function getImapUserEndpoint(string $endpoint, string $version): string
    {
        return $this->getServicePrefix("imapuser", $version) . "/" . $endpoint;
    }


    /**
     * Returns the relative path to the rest-api-email endpoint.
     *
     * @param string $endpoint
     * @param string $version
     * @return string The relative path to the endpoint according to the api version used with this
     * tests.
     *
     * @see #getServicePrefix
     */
    public function getImapEndpoint(string $endpoint, string $version): string
    {
        return $this->getServicePrefix("imap", $version) . "/" . $endpoint;
    }


    /**
     * Set an expected exception.
     *
     * @param string $exception
     *
     * @return void
     *
     * @see @see https://laracasts.com/discuss/channels/testing/testing-that-exception-was-thrown
     */
    public function expectException(string $exception): void
    {

        $this->app->instance(Handler::class, new class extends Handler {
            public function __construct()
            {
            }

            public function render($request, Throwable $e): Response
            {
                throw $e;
            }
        });

        parent::expectException($exception);
    }
}
