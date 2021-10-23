<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2021 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

namespace Conjoon\Http\Json\Problem;

use Conjoon\Http\Status\StatusCodes as Status;

/**
 * Class ProblemFactory
 * @package Conjoon\Http\Json\Problem
 */
class ProblemFactory
{

    /**
     * Returns an array containing of the json representative for the made Problem,
     * and the second index is the status used.
     *
     * @param int $status
     * @param string|null $title
     * @param string|null $detail
     * @return array
     */
    public static function makeJson(int $status, string $title = null, string $detail = null): array
    {
        $made = self::make($status, $title, $detail);

        return [
            $made->toJson(),
            $made->getStatus()
        ];
    }


    /**
     * Returns a new Problem based on the status submitted.
     * The concrete representative for the status  gets configured with title and detail,
     * if specified.
     * If no Http Status representative was found, the default Problem class will be used,
     * configured with the submitted status.
     *
     * @param int $status
     * @param string|null $title
     * @param string|null $detail
     * @return AbstractProblem
     */
    public static function make(int $status, string $title = null, string $detail = null)
    {
        $title = $title ?? Status::HTTP_STATUS[$status];

        switch ($status) {
            case Status::HTTP_400:
                return new BadRequestProblem($title, $detail);


            case Status::HTTP_405:
                return new MethodNotAllowedProblem($title, $detail);
        }

        return new Problem($status, $title, $detail);
    }
}
