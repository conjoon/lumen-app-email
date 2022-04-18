<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2021-2022 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

namespace Conjoon\Horde\Mail\Client\Imap;

use Horde_Imap_Client;
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Search_Query;

/**
 * Trait for building SearchQueries based on filter options submitted by the client.
 * Filter configurations are treated as OR-queries.
 *
 *
 * @example
 *
 *    $options = [
 *      ["property" => "recent", "value" => true, "operator" => "="],
 *      ["property" => "id", "value" => 1000, "operator" => ">="]
 *    ]
 *
 *    $this->getSearchQueryFromFilterTrait($options)->toString(); // returns "OR (UID 1000:*) (RECENT)"
 *
 *
 * Trait FilterTrait
 * @package Conjoon\Horde\Mail\Client\Imap
 */
trait FilterTrait
{

    /**
     * Looks up filter information from the passed array and creates a Horde_Imap_Client_Search_Query
     * from it.
     *
     * @param array $clientFilter
     *
     * @return Horde_Imap_Client_Search_Query
     */
    public function getSearchQueryFromFilter(array $clientFilter): Horde_Imap_Client_Search_Query
    {
        $searchQuery = new Horde_Imap_Client_Search_Query();

        // check if we have filter here
        if (!empty($clientFilter)) {
            $clientSearches = [];

            foreach ($clientFilter as $filter) {
                if ($filter["property"] === "id") {
                    if ($filter["operator"] === ">=") {
                        $filterId = $filter["value"] . ":*";
                        $latestQuery = new Horde_Imap_Client_Search_Query();
                        $latestQuery->ids(new Horde_Imap_Client_Ids([$filterId]));
                        $clientSearches[] = $latestQuery;
                    } elseif (strtolower($filter["operator"]) === "in") {
                        $latestQuery = new Horde_Imap_Client_Search_Query();
                        $latestQuery->ids(new Horde_Imap_Client_Ids($filter["value"]));
                        $clientSearches[] = $latestQuery;
                    }
                }
                if ($filter["property"] === "recent" && $filter["operator"] === "=" && $filter["value"] === true) {
                    $recentQuery = new Horde_Imap_Client_Search_Query();
                    $recentQuery->flag(Horde_Imap_Client::FLAG_RECENT, true);
                    $clientSearches[] = $recentQuery;
                }
            }

            $searchQuery->orSearch($clientSearches);
        }

        return $searchQuery;
    }
}
