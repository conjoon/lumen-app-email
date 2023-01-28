<?php

/**
 * conjoon
 * lumen-app-email
 * Copyright (C) 2022-2023 Thorsten Suckow-Homberg https://github.com/conjoon/lumen-app-email
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

namespace App\Console\Commands;

use Conjoon\Util\ArrayUtil;

/**
 *
 */
class ConfigureApiCommand extends BaseConfigurationCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'configure:api';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Configure the API paths for this service.";


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->prepare();

        $apis = [
            "email" => ["api.service.email.path" => "APP_EMAIL_PATH"],
            "imapuser"  => ["api.service.imapuser.path" => "APP_AUTH_PATH"]
        ];

        foreach ($apis as $serviceName => $config) {
            if ($serviceName === "imapuser") {
                $auth = $this->choice(
                    "Please provide the auth service for this instance.",
                    ["single-imap-user", "local-mail-account"],
                    "local-mail-account"
                );

                if ($auth === "local-mail-account") {
                    $this->updateEnvSettings("APP_AUTH_PATH", null);
                    $this->updateEnvSettings("AUTH_PROVIDER", "local-mail-account");
                    $this->flushEnv();
                    continue;
                }
            }

            $appConfigPath = array_keys($config)[0];
            $envKey = array_values($config)[0];

            $defaultPaths = [$this->getDefaultPathOption($appConfigPath)];
            $defaultPaths[] = $this->manualOption();

            $path = $this->requireApiPath(
                "Please provide the path where the <fg=white;bg=green>$serviceName service</> should be located",
                $serviceName,
                $defaultPaths
            );

            $this->updateEnvSettings($envKey, $path);
            if ($serviceName === "imapuser") {
                $this->updateEnvSettings("AUTH_PROVIDER", "single-imap-user");
            }
            $this->flushEnv();
        }
    }


    private function requireApiPath(string $message, string $serviceName, array $options)
    {
        $args = func_get_args();

        $path = $this->choice(
            $message,
            $options,
            $options[0]
        );

        if ($path === $this->manualOption()) {
            $path = $this->ask("Please type in the path");
        }

        $url = $this->envSettings["APP_URL"] . "/" . $path . "/{apiVersion}";

        if (!$path || trim((string)$path) == "" || !parse_url($url)) {
            $this->error(($path ? $path : "This") . " does not seem to be a valid path. Please try again.");
            return $this->requireApiPath(...$args);
        }

        if (
            $this->choice(
                "<fg=white;bg=blue>$serviceName service</> locatable " .
                "<fg=blue;options=underscore>$url</>, is that okay?",
                ["yes", "no"],
                "yes"
            ) === "no"
        ) {
            return $this->requireApiPath(...$args);
        }


        return $path;
    }


    private function getDefaultPathOption($appPathKey)
    {
        if (strpos($appPathKey, "imapuser") !== false) {
            return "rest-imapuser";
        }

        return ArrayUtil::unchain($appPathKey, $this->appSettings);
    }
}
