<?php

/**
 * conjoon
 * php-ms-imapuser
 * Copyright (C) 2022 Thorsten Suckow-Homberg https://github.com/conjoon/php-ms-imapuser
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

namespace Tests\Conjoon\Horde\Mail\Client\Imap;

use Conjoon\Horde\Mail\Client\Imap\HordeClient;
use Conjoon\Mail\Client\Attachment\FileAttachmentList;
use Conjoon\Mail\Client\Message\Composer\AttachmentComposer;
use Conjoon\Mail\Client\Message\Composer\BodyComposer;
use Conjoon\Mail\Client\Message\Composer\HeaderComposer;
use Conjoon\Mail\Client\Message\MessageBodyDraft;
use Conjoon\Mail\Client\Message\MessageItemDraft;

/**
 * Trait ClientGeneratorTrait
 * @package Tests\Conjoon\Horde\Mail\Client\Imap
 */
trait ClientGeneratorTrait
{

    /**
     * @return BodyComposer
     */
    protected function createBodyComposer(): BodyComposer
    {

        return new class () implements BodyComposer {

            public function compose(string $target, MessageBodyDraft $messageBodyDraft): string
            {
                return trim($target) . "\n\n" . "FULL_TXT_MSG";
            }
        };
    }


    /**
     * @return AttachmentComposer
     */
    protected function createAttachmentComposer(): AttachmentComposer
    {

        return new class () implements AttachmentComposer {

            public function compose(string $target, FileAttachmentList $fileAttachmentList): string
            {
                return trim($target) . "\n\nAttachments[" . count($fileAttachmentList) . "]";
            }
        };
    }


    /**
     * @return HeaderComposer
     */
    protected function createHeaderComposer(): HeaderComposer
    {

        return new class () implements HeaderComposer {

            public function compose(string $target, MessageItemDraft $source = null): string
            {
                return "__HEADER__" . "\n\n" . trim($target);
            }
        };
    }


    /**
     * Creates an instance of HordeClient.
     *
     * @param null $mailAccount
     * @param null $bodyComposer
     * @param null $headerComposer
     * @param null $attachmentComposer
     *
     * @return HordeClient
     */
    protected function createClient(
        $mailAccount = null,
        $bodyComposer = null,
        $headerComposer = null,
        $attachmentComposer = null
    ): HordeClient {

        if (!$mailAccount) {
            $mailAccount = $this->getTestUserStub()->getMailAccount("dev_sys_conjoon_org");
        }

        if (!$bodyComposer) {
            $bodyComposer = $this->createBodyComposer();
        }

        if (!$headerComposer) {
            $headerComposer = $this->createHeaderComposer();
        }

        if (!$attachmentComposer) {
            $attachmentComposer = $this->createAttachmentComposer();
        }

        return new HordeClient($mailAccount, $bodyComposer, $headerComposer, $attachmentComposer);
    }
}
