<?php

/**
 * conjoon
 * lumen-app-email
 * Copyright (C) 2022 Thorsten Suckow-Homberg https://github.com/conjoon/lumen-app-email
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

use Illuminate\Console\Command;
use Dotenv\Dotenv;

/**
 *
 */
abstract class BaseConfigurationCommand extends Command
{
    /**
     * Settings read from config/app.php
     * @var array
     */
    protected $appSettings = [];


    /**
     * env settings read out from the .env file, which might differ from those found in
     * the app settings from config/app.php
     * @var array
     */
    protected $envSettings = [];


    /**
     * Prepares configuration process by reading in configuration from .env and config/app.php.
     * @return void
     */
    protected function prepare()
    {
        $this->appSettings = include($this->getAppFile());

        if (file_exists($this->getEnvFile())) {
            $envFile = Dotenv::create($this->getEnvPath());
            $this->envSettings = $envFile->load();
        }
    }


    protected function updateEnvSettings($key, $value)
    {
        $this->envSettings[$key] = $value;
    }


    protected function flushEnv()
    {
        $lines = [];

        foreach ($this->envSettings as $key => $value) {
            $lines[] = "$key=$value";
        }

        $config = implode("\n", $lines);

        file_put_contents($this->getEnvFile(), $config);
        $this->line("Updating configuration file...");
    }


    protected function manualOption()
    {
        return "<fg=white;bg=blue;options=bold>[type in manually]</>";
    }


    private function getAppFile()
    {
        return $this->getRootDir() . "/app/config/app.php";
    }


    private function getEnvPath()
    {
        return $this->getRootDir();
    }


    protected function getEnvFile()
    {
        return $this->getRootDir() . "/.env";
    }


    protected function getConfigPath()
    {
        return $this->getRootDir() . "/app/config";
    }


    private function getRootDir()
    {
        return realpath(__DIR__ . "/../../../../");
    }
}
