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

/**
 *
 */
class ConfigureUrlCommand extends BaseConfigurationCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'configure:url';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Configure the URL for this instance.";


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->prepare();
        $defaultUrls = $this->getDefaultUrlOptions();

        $url = $this->requireInstanceUrl($defaultUrls);


        $this->updateEnvSettings("APP_URL", $url);
        $this->flushEnv();
    }


    private function requireInstanceUrl(array $defaultUrls)
    {

        $url = $this->choice(
            "Please select the URL where this instance will be located, or type in manually",
            $defaultUrls,
            $defaultUrls[0]
        );

        if ($url === $this->manualOption()) {
            $url = $this->ask("Please type in the URL");
        }

        if (!$this->validateUrl($url)) {
            $this->error(($url ? $url : "This") . " does not seem to be a valid URL. Please try again.");
            return $this->requireInstanceUrl($defaultUrls);
        }

        if ($this->choice("Using $url for this instance, is that okay?", ["yes", "no"], "yes") === "no") {
            return $this->requireInstanceUrl($defaultUrls);
        }


        return $url;
    }


    private function validateUrl(?string $url)
    {

        $parts = $url ? parse_url($url) : null;

        if ($parts) {
            if (isset($parts["scheme"]) && isset($parts["host"])) {
                return true;
            }
        }

        return false;
    }


    private function getDefaultUrlOptions()
    {
        $defaultUrls = [$this->appSettings["url"]];
        $serverUrl = $this->getServerUrl();
        if ($serverUrl && !array_filter($defaultUrls, fn ($url) => strtolower($url) === strtolower($serverUrl))) {
            $defaultUrls[] = $serverUrl;
        }
        $defaultUrls[] = $this->manualOption();

        return $defaultUrls;
    }

    private function getServerUrl(): ?string
    {
        return isset($_SERVER["SERVER_NAME"])
                ? (isset($_SERVER["HTTPS"])  ? "https://" : "http://") . $_SERVER["SERVER_NAME"]
                : null;
    }
}
