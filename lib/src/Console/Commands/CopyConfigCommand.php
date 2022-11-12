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

/**
 *
 */
class CopyConfigCommand extends BaseConfigurationCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'copyconfig';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Copies relevant default configuration files necessary for running the instance.";


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->prepare();

        $files = [
            ["template" => "cors.php.example", "target" => "cors.php"],
            ["template" => "imapserver.php.example", "target" => "imapserver.php"],
        ];

        $this->traverseConfigurationFiles($files);
    }


    private function traverseConfigurationFiles(array $files)
    {

        foreach ($files as $fileCfg) {
            $template = $fileCfg["template"];
            $target = $fileCfg["target"];

            $targetPath = $this->getConfigPath() . "/$target";
            $templatePath = $this->getConfigPath() . "/$template";

            if (!file_exists($templatePath)) {
                $this->warn(
                    "I was looking for <fg=white;bg=red>$template</>, but could not find the file. Skipping..."
                );
                continue;
            }

            if (file_exists($targetPath)) {
                if (
                    $this->choice(
                        "The configuration file for <fg=white;bg=green>$target</> already exists. Do you want me to " .
                        "override it with the default configuration file (<fg=white;bg=green>$template</>)?",
                        ["yes", "no"],
                        "no"
                    ) === "yes"
                ) {
                    $this->copyFile($templatePath, $targetPath, true);
                }
            } else {
                $this->copyFile($templatePath, $targetPath);
            }
        }
    }


    private function copyFile($source, $target, $override = false)
    {
        if (file_exists($target) && !$override) {
            $this->warn(
                "<fg=white;bg=red>$target</> exists, and I am not allowed to override it. Skipping..."
            );
        }
        $this->info(
            "Copying <fg=white;bg=green>$source</> to <fg=white;bg=green>$target</>."
        );
        copy($source, $target);
    }
}
