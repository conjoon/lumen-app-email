<?php

/**
 * conjoon
 * lumen-app-email
 * Copyright (c) 2019-2022 Thorsten Suckow-Homberg https://github.com/conjoon/lumen-app-email
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

use App\Exceptions\Handler;
use Conjoon\Mail\Client\Data\MailAccount;
use Conjoon\Mail\Client\Service\AuthService;
use Exception;
use Illuminate\Support\Facades\Config;
use Laravel\Lumen\Application;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use RuntimeException;
use Illuminate\Http\Response;
use Laravel\Lumen\Testing\TestCase as LaravelTestCase;

/**
 * Class TestCase
 * @package Tests
 */
abstract class TestCase extends LaravelTestCase
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


        Config::set("imapserver", require __DIR__ . "/config/imapserver.php");
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
            "imapuser"  => config("app.api.service.auth")
        ];

        $type = strtolower($type);
        if (!in_array($type, ["imap", "imapuser"])) {
            throw new RuntimeException("\"$type\" is not valid");
        }
        return implode("", [
            $mapping[$type] .
            "/api",
            ($version === "latest") ? "" : "/" . $version
        ]);
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
            public function report(Exception $e)
            {
            }
            public function render($request, Exception $e): Response
            {
                throw $e;
            }
        });

        parent::expectException($exception);
    }


    protected function createMockForAbstract(string $originalClassName, array $mockedMethods = [], array $args = []): MockObject
    {
        return parent::getMockForAbstractClass(
            $originalClassName,
            $args,
            '',
            true,
            true,
            true,
            $mockedMethods
        );
    }


    /**
     * @param mixed $inst
     * @param $name
     * @param bool $isProperty
     *
     * @return ReflectionMethod|ReflectionProperty
     *
     * @throws ReflectionException
     */
    protected function makeAccessible($inst, $name, bool $isProperty = false)
    {
        $refl = new ReflectionClass($inst);

        $name = match ($isProperty) {
            true => $refl->getProperty($name),
            default => $refl->getMethod($name),
        };

        $name->setAccessible(true);

        return $name;
    }
}
