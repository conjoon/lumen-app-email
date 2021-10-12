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

use App\Exceptions\Handler;
use Exception;
use Laravel\Lumen\Application;
use RuntimeException;

require_once __DIR__ . "/TestTrait.php";

abstract class TestCase extends \Laravel\Lumen\Testing\TestCase
{

    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication(): Application
    {
        return require __DIR__."/../bootstrap/app.php";
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
    public function getServicePrefix(string $type, string $version) : string {

        $type = strtolower($type);
        if (!in_array($type, ["imap", "imapuser"])) {
            throw new RuntimeException("\"$type\" is not valid");
        }
        return implode("", [
            "rest-",
            $type,
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
    public function getImapUserEndpoint(string $endpoint, string $version): string {
        return $this->getServicePrefix("imapuser", $version) . "/" . $endpoint;
    }


    /**
     * Returns the relative path to the rest-imap endpoint.
     *
     * @param string $endpoint
     * @param string $version
     * @return string The relative path to the endpoint according to the api version used with this
     * tests.
     *
     * @see #getServicePrefix
     */
    public function getImapEndpoint(string $endpoint, string $version) : string {
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
    public function expectException(string $exception) :void {

        $this->app->instance(Handler::class, new class extends Handler {
            public function __construct() {}
            public function report(Exception $exception) {}
            public function render($request, Exception $exception) {
                throw $exception;
            }
        });

        parent::expectException($exception);
    }

}
