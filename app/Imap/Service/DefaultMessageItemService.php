<?php
/**
 * conjoon
 * php-cn_imapuser
 * Copyright (C) 2019 Thorsten Suckow-Homberg https://github.com/conjoon/php-cn_imapuser
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

namespace App\Imap\Service;

use App\Imap\ImapAccount,
    App\Imap\Service\MessageItemServiceException;

/**
 * Class DefaultMessageItemService.
 * Default implementation of a MessageItemService, using \Horde_Imap_Client to communicate with
 * Imap Servers.
 *
 * @package App\Imap\Service
 */
class DefaultMessageItemService implements MessageItemService {

    use ImapTrait;

    /**
     * Number of chars for the previewText of a MessageItem.
     * @var int
     */
    protected const PREVIEW_LENGTH = 200;


    /**
     * @inheritdoc
     */
    public function getMessageItemsFor(ImapAccount $account, string $mailFolderId, array $options) :array {

        try {

            $client = $this->connect($account);

            $results  = $this->queryItems($client, $mailFolderId, $options);
            $total = count($results["match"]);
            $fetchedItems    = $this->fetchMessageItems($client, $results["match"], $mailFolderId, $options);
            $messageItems = $this->buildMessageItems($client, $account->getId(), $mailFolderId, $fetchedItems);

            $status = $client->status(
                $mailFolderId,
                \Horde_Imap_Client::STATUS_UNSEEN
            );

            $response = [
                "total" => $total,
                "meta"  => [
                    "cn_unreadCount" => $status["unseen"],
                    "mailFolderId"   => $mailFolderId,
                    "mailAccountId"  => $account->getId()
                ],
                "data"  => $messageItems
            ];


        } catch (\Exception $e) {
            throw new MessageItemServiceException($e->getMessage(), 0, $e);
        }

        return $response;
    }


    /**
     * @inheritdoc
     */
    public function getMessageItemFor(ImapAccount $account, string $mailFolderId, string $messageItemId) :array {

        try {

            $client = $this->connect($account);

            $fetchedItems = $this->fetchMessageItems($client, new \Horde_Imap_Client_Ids($messageItemId), $mailFolderId,[]);
            $messageItems = $this->buildMessageItems($client, $account->getId(), $mailFolderId, $fetchedItems, false);

            return $messageItems[0];

        } catch (\Exception $e) {
            throw new MessageItemServiceException($e->getMessage(), 0, $e);
        }

        return $response;

    }


    /**
     * @inheritdoc
     */
    public function getMessageBodyFor(ImapAccount $account, string $mailFolderId, string $messageItemId) :array {

        try {

            $client = $this->connect($account);

            $query = new \Horde_Imap_Client_Fetch_Query();
            $query->structure();

            $uid = new \Horde_Imap_Client_Ids($messageItemId);

            $list = $client->fetch($mailFolderId, $query, array(
                'ids' => $uid
            ));

            $serverItem = $list->first();

            $messageStructure = $serverItem->getStructure();
            $typeMap          = $messageStructure->contentTypeMap();
            $bodyQuery        = new \Horde_Imap_Client_Fetch_Query();
            foreach ($typeMap as $part => $type) {
                $bodyQuery->bodyPart($part, array(
                    'decode' => true,
                    'peek'   => true
                ));
            }

            $textHtml  = "";
            $textPlain = "";

            $messageData = $client->fetch(
                $mailFolderId, $bodyQuery, ['ids' => new \Horde_Imap_Client_Ids($messageItemId)]
            )->first();
            if ($messageData) {
                $plainPartId = $messageStructure->findBody('plain');
                $htmlPartId  = $messageStructure->findBody('html');
                foreach ($typeMap as $part => $type) {

                    if ($part == $htmlPartId) {
                        $body = $messageStructure->getPart($htmlPartId);
                        $textHtml = $messageData->getBodyPart($part);
                        if (!$messageData->getBodyPartDecode($part)) {
                            $body->setContents($textHtml);
                            $textHtml = $body->getContents();
                        }

                       // var_dump($body->getCharset());

                    } else if ($part == $plainPartId) {
                        $body = $messageStructure->getPart($plainPartId);
                        $textPlain = $messageData->getBodyPart($part);
                        if (!$messageData->getBodyPartDecode($part)) {
                            $body->setContents($textPlain);
                            $textPlain = $body->getContents();
                        }
                        //var_dump($body->getCharset());
                    }

                }
            }

            if (!$textHtml) {
                $textHtml = $textPlain;
            }


        } catch (\Exception $e) {
            throw new MessageItemServiceException($e->getMessage(), 0, $e);
        }


        return [
            "id" => (string)$serverItem->getUid(),
            "mailFolderId" => $mailFolderId,
            "mailAccountId" => $account->getId(),
            "textPlain" => $textPlain,
            "textHtml" => $textHtml
        ];

    }


    /**
     * Helper function for building a list of message items found in the mailbox with the global name
     * $mailFolderId given the specified options.
     *
     * @param \Horde_Imap_Client_Socket $client
     * @param string $accountId
     * @param string $mailFolderId
     * @param array $items
     * @param bool $withPreview
     *
     * @return array
     *
     * @see queryItems
     */
    protected function buildMessageItems(
        \Horde_Imap_Client_Socket $client, string $accountId, string $mailFolderId, array $items, bool $withPreview = true
    ) :array {


        $bodyQuery = new \Horde_Imap_Client_Fetch_Query();

        $messageItems = [];

        foreach ($items as $item) {

            $envelope = $item->getEnvelope();
            $flags    = $item->getFlags();

            $hasAttachments = false;

            $froms = [];
            foreach ($envelope->from as $from) {
                $froms["name"]    = $from->personal ? $from->personal : $from->bare_address;
                $froms["address"] = $from->bare_address;
            }

            $tos = [];
            foreach ($envelope->to as $to) {
                $tos[] = [
                    "name"    => $to->personal ? $to->personal : $to->bare_address,
                    "address" => $to->bare_address
                ];
            }


            // parse body
            $messageStructure = $item->getStructure();
            $typeMap          = $messageStructure->contentTypeMap();
            if ($withPreview !== false) {
                foreach ($typeMap as $part => $type) {
                    // The body of the part - attempt to decode it on the server.
                    $bodyQuery->bodyPart($part, array(
                        'decode' => true,
                        'peek'   => true,
                        'length' => self::PREVIEW_LENGTH
                    ));
                }
            }


            $textPlain = "";

            $messageData = $client->fetch(
                $mailFolderId, $bodyQuery, ['ids' => new \Horde_Imap_Client_Ids($item->getUid())]
            )->first();
            if ($messageData) {
                $plainPartId = $messageStructure->findBody('plain');
                $htmlPartId  = $messageStructure->findBody('html');

                foreach ($typeMap as $part => $type) {

                    $body = $messageStructure->getPart($part);

                    if ($part == $plainPartId) {
                        $textPlain = $messageData->getBodyPart($part);
                        if (!$messageData->getBodyPartDecode($part)) {
                            $body->setContents($textPlain);
                            $textPlain = $body->getContents();
                        }
                        //var_dump($body->getCharset());
                    } else if ($part != $htmlPartId && $filename = $body->getName($part)) {
                        $hasAttachments = true;
                    }
                }
            }


            $messageItem = [
                "id"             => (string)$item->getUid(),
                "mailAccountId"  => $accountId,
                "mailFolderId"   => $mailFolderId,
                "from"           => $froms,
                "to"             => $tos,
                "size"           => $item->getSize(),
                "subject"        => $envelope->subject,
                "date"           => $envelope->date->format("Y-m-d H:i"),
                "seen"           => array_search(\Horde_Imap_Client::FLAG_SEEN, $flags) !== false,
                "answered"       => array_search(\Horde_Imap_Client::FLAG_ANSWERED, $flags) !== false,
                "draft"          => array_search(\Horde_Imap_Client::FLAG_DRAFT, $flags) !== false,
                "flagged"        => array_search(\Horde_Imap_Client::FLAG_FLAGGED, $flags) !== false,
                "recent"         => array_search(\Horde_Imap_Client::FLAG_RECENT, $flags) !== false,
                "hasAttachments" => $hasAttachments
            ];

            if ($withPreview !== false) {
                $messageItem["previewText"] =  mb_convert_encoding($textPlain, 'UTF-8', 'UTF-8');
            }


            $messageItems[] = $messageItem;
        }

        return $messageItems;
    }



    /**
     * Sends a query against the currently conected IMAP server for retrieving
     * a list of messages based on the specified $options.
     *
     * @param \Horde_Imap_Client_Socket $client
     * @param string $mailFolderId The global name of the mailbox to search in
     * @param array $options An array with the following options
     * - start (integer) The start position from where to return the results
     * - limit (integer) The number of items to return.
     *
     * @return array
     */
    protected function queryItems(\Horde_Imap_Client_Socket $client, string $mailFolderId, array $options) :array {

        $sort = isset($options["sort"]) ? $options["sort"] : [["property" => "date", "direction" => "DESC"]];
        $sort = $sort[0];

        $sortInfo = [];

        if ($sort["direction"] === "DESC") {
            $sortInfo[] = \Horde_Imap_Client::SORT_REVERSE;
        }

        switch ($sort["property"]) {
            case 'subject':
                $sortInfo[] = \Horde_Imap_Client::SORT_SUBJECT;
                break;
            case 'to':
                $sortInfo[] = \Horde_Imap_Client::SORT_TO;
                break;
            case 'from':
                $sortInfo[] = \Horde_Imap_Client::SORT_FROM;
                break;
            case 'date':
                $sortInfo[] = \Horde_Imap_Client::SORT_DATE;
                break;
            case 'size':
                $sortInfo[] = \Horde_Imap_Client::SORT_SIZE;
                break;
        }

        // search and narrow down list
        $searchQuery = new \Horde_Imap_Client_Search_Query();
        $results = $client->search($mailFolderId, $searchQuery, ["sort" => $sortInfo]);

        return $results;
    }


    /**
     *
     * @param \Horde_Imap_Client_Socket $client
     * @param \Horde_Imap_Client_Ids $searchResultIds
     * @param string $mailFolderId
     * @param array $options
     * @return array
     */
    protected function fetchMessageItems(\Horde_Imap_Client_Socket $client, \Horde_Imap_Client_Ids $searchResultIds, string $mailFolderId, array $options) {

        $start = isset($options["start"]) ? intval($options["start"]) : -1;
        $limit = isset($options["limit"])  ? intval($options["limit"]) : -1;

        if ($start >= 0 && $limit > 0) {
            $rangeList = new \Horde_Imap_Client_Ids();
            foreach ($searchResultIds as $key => $entry) {
                if ($key >= $start && $key < $start + $limit) {
                    $rangeList->add($entry);
                }
            }

            $orderedList = $rangeList->ids;
        } else {
            $orderedList = $searchResultIds->ids;
            $rangeList = $searchResultIds;
        }


        // fetch IMAP
        $fetchQuery = new \Horde_Imap_Client_Fetch_Query();
        $fetchQuery->flags();
        $fetchQuery->size();
        $fetchQuery->envelope();
        $fetchQuery->structure();

        $fetchResult = $client->fetch($mailFolderId, $fetchQuery, ['ids' => $rangeList]);

        $final = [];
        foreach ($orderedList as $id) {
            $final[] = $fetchResult[$id];
        }

        return $final;
    }

}