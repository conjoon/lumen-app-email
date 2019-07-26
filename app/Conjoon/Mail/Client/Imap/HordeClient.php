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

namespace Conjoon\Mail\Client\Imap;

use Conjoon\Mail\Client\MailClient,
    Conjoon\Mail\Client\Data\MailAccount,
    Conjoon\Text\Converter;

/**
 * Class HordeClient.
 * Default implementation of a HordeClient, using \Horde_Imap_Client to communicate with
 * Imap Servers.
 *
 * @package Conjoon\Mail\Client\Imap
 */
class HordeClient implements MailClient {


    /**
     * @var Converter
     */
    protected $converter;

    /**
     * Number of chars for the previewText of a MessageItem.
     * @var int
     */
    protected const PREVIEW_LENGTH = 200;


    public function __construct(Converter $converter) {

        $this->converter = $converter;
    }


    /**
     * Creates a \Horde_Imap_Client_Socket with the specified account informations.
     *
     * @param MailAccount $account
     *
     * @return \Horde_Imap_Client_Socket
     */
    public function connect(MailAccount $account) : \Horde_Imap_Client_Socket {


        return new \Horde_Imap_Client_Socket(array(
            'username' => $account->getInboxUser(),
            'password' => $account->getInboxPassword(),
            'hostspec' => $account->getInboxAddress(),
            'port'     => $account->getInboxPort(),
            'secure'   => $account->getInboxSsl() ? 'ssl' : null
        ));

    }


    /**
     * @inheritdoc
     */
    public function getMessageItemsFor(MailAccount $account, string $mailFolderId, array $options) :array {

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
            throw new ImapClientException($e->getMessage(), 0, $e);
        }

        return $response;
    }


    /**
     * @inheritdoc
     */
    public function getMessageItemFor(MailAccount $account, string $mailFolderId, string $messageItemId) :array {

        try {

            $client = $this->connect($account);

            $fetchedItems = $this->fetchMessageItems($client, new \Horde_Imap_Client_Ids($messageItemId), $mailFolderId,[]);
            $messageItems = $this->buildMessageItems($client, $account->getId(), $mailFolderId, $fetchedItems, false);

            return $messageItems[0];

        } catch (\Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
        }

        return $response;

    }


    /**
     * @inheritdoc
     */
    public function getMessageBodyFor(MailAccount $account, string $mailFolderId, string $messageItemId) :array {

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

            $d = $this->getContents($client, $messageStructure, $mailFolderId, (string)$messageItemId, ["plain", "html"]);

            $textHtml = $this->converter->convert($d["html"]["content"], $d["html"]["charset"]);
            $textPlain = $this->converter->convert($d["plain"]["content"], $d["plain"]["charset"]);

            if (!$textHtml) {
                $textHtml = $textPlain;
            }


        } catch (\Exception $e) {
            throw new ImapClientException($e->getMessage(), 0, $e);
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
     *
     * @param \Horde_Imap_Client_Socket $client
     * @param \Horde_Imap_Client_Ids $searchResultIds
     * @param string $mailFolderId
     * @param array $options
     * @return array
     */
    protected function fetchMessageItems(
        \Horde_Imap_Client_Socket $client, \Horde_Imap_Client_Ids $searchResultIds, string $mailFolderId, array $options) {

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




        $messageItems = [];

        foreach ($items as $item) {

            $envelope = $item->getEnvelope();
            $flags    = $item->getFlags();

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

            $d = $this->getContents($client, $messageStructure, $mailFolderId, (string)$item->getUid(), ["plain", "hasAttachments"]);

            $textPlain = $this->converter->convert($d["plain"]["content"], $d["plain"]["charset"]);
            $hasAttachments = $d["hasAttachments"];

            $messageItem = [
                "id"             => (string)$item->getUid(),
                "mailAccountId"  => $accountId,
                "mailFolderId"   => $mailFolderId,
                "from"           => $froms,
                "to"             => $tos,
                "size"           => $item->getSize(),
                "subject"        => $envelope->subject,
                "date"           => $envelope->date->format("Y-m-d H:i"),
                "seen"           => in_array(\Horde_Imap_Client::FLAG_SEEN, $flags),
                "answered"       => in_array(\Horde_Imap_Client::FLAG_ANSWERED, $flags),
                "draft"          => in_array(\Horde_Imap_Client::FLAG_DRAFT, $flags),
                "flagged"        => in_array(\Horde_Imap_Client::FLAG_FLAGGED, $flags),
                "recent"         => in_array(\Horde_Imap_Client::FLAG_RECENT, $flags),
                "hasAttachments" => $hasAttachments
            ];

            if ($withPreview !== false) {
                $messageItem["previewText"] = mb_substr(
                    $textPlain,0,200, $d["plain"]["charset"] ? $d["plain"]["charset"] : "UTF-8"
                );

                $messageItem["previewText"] = htmlentities($messageItem["previewText"]);
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
     * Returns contents of the mail. Possible return keys are based on the passed
     *$options "html" (string), "plain" (string) and/or "hasAttachments" (bool)
     *
     * @param \Horde_Imap_Client_Socket $client
     * @param $messageStructure
     * @param string $mailFolderId
     * @param string $messageItemId
     * @param array $options
     *
     * @return array
     */
    protected function getContents(
        \Horde_Imap_Client_Socket $client, $messageStructure, string $mailFolderId,
        string $messageItemId, array $options) :array {

        $typeMap          = $messageStructure->contentTypeMap();
        $bodyQuery        = new \Horde_Imap_Client_Fetch_Query();
        foreach ($typeMap as $part => $type) {
            $bodyQuery->bodyPart($part, array(
                'peek'   => true
            ));
        }

        $ret             = [];
        $findHtml        = in_array("html", $options);
        $findPlain       = in_array("plain", $options);
        $findAttachments = in_array("hasAttachments", $options);

        if ($findHtml) {
            $ret["html"] = ["content" => "", "charset" => ""];
        }
        if ($findPlain) {
            $ret["plain"] = ["content" => "", "charset" => ""];
        }
        if ($findAttachments) {
            $ret["hasAttachments"] = false;
        }

        $messageData = $client->fetch(
            $mailFolderId, $bodyQuery, ['ids' => new \Horde_Imap_Client_Ids($messageItemId)]
        )->first();

        if ($messageData) {
            $plainPartId = $messageStructure->findBody('plain');
            $htmlPartId  = $messageStructure->findBody('html');

            foreach ($typeMap as $part => $type) {

                $body = $messageStructure->getPart($part);

                if ($findAttachments && $body->isAttachment()) {
                    $ret["hasAttachments"] = true;
                    if (!$findHtml && !$findPlain) {
                        break;
                    }
                }

                if ($findHtml || $findPlain) {
                    $content = $messageData->getBodyPart($part);
                    if (!$messageData->getBodyPartDecode($part)) {
                        // Decode the content.
                        $body->setContents($content);
                        $content = $body->getContents();
                    }

                    if ($findHtml && (string)$part === $htmlPartId) {
                        $ret["html"] = ["content" => $content, "charset" => $body->getCharset()];
                    } else if ($findPlain && (string)$part === $plainPartId) {
                        $ret["plain"] = ["content" => $content, "charset" => $body->getCharset()];
                    }
                }

            }
        }

        return $ret;

    }


    /**
     * Returns the Converter used by this instance.
     *
     * @return Converter
     */
    public function getConverter() :Converter {
        return $this->converter;
    }



}