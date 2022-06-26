<?php

/**
 * conjoon
 * lumen-app-email
 * Copyright (c) 2022 Thorsten Suckow-Homberg https://github.com/conjoon/lumen-app-email
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

namespace App;

use Conjoon\Mail\Client\Data\CompoundKey\CompoundKey;

class ControllerUtil
{
    /**
     * returns the uri for the specified type. the type can be one
     * - messageitem
     *
     * @param string $type
     * @param compoundkey $key
     * @param $calleruri the uri that triggered a call to this method, and from
     * which the current version of the api being used should be extracted.
     *
     * @return string
     */
    public function getResourceUrl(string $type, compoundkey $key, $calleruri): string
    {
        preg_match("/\/(v\d*)\//mi", $calleruri, $matches);

        $version = $matches[1] ?? config("app.api.latest");

        $baseUrl = implode(
            "/",
            [config("app.url"), config("app.api.service.email"), $version]
        );

        switch ($type) {
            case "MessageItem":
                $path = implode("/", [
                    "MailAccounts",
                    $key->getMailAccountId() ,
                    "MailFolders",
                    $key->getMailFolderId() ,
                    "MessageItems",
                    $key->getId() ,
                ]);
                return $baseUrl . "/" . $path;
        }

        return $baseUrl;
    }
}
