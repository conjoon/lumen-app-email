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

namespace App\Text;


/**
 * Class CharsetDecoder.
 *
 *
 * @package App\Text
 */
class CharsetConverter implements Converter {


    /**
     * @var boolean
     */
    protected $lastIconvError;


    /**
     * Returns the converted version of the text.
     *
     * @param string $text
     * @param string $fromCharset if no value at all or a falsy value is passed, the
     * charset of $text will be guessed
     * @param string $targetCharset defaults to UTF-8 if omitted
     *
     * @return string
     */
    public function convert(string $text, string $fromCharset = "", string $targetCharset = "UTF-8") :string {
        

        // try to replace those curved quotes with their correct entities!
        // see http://en.wikipedia.org/wiki/Quotation_mark_glyphs
        // [quote]
        // A few mail clients send curved quotes using the windows-1252 codes,
        // but mark the text as ISO-8859-1, causing problems for decoders that
        // do not make the dubious assumption that C1 control codes in ISO-8859-1
        // text were meant to be windows-1252 printable characters
        // [/quote]
        if (strtolower($fromCharset) == 'iso-8859-1') {
            $fromCharset = 'windows-1252';
        }
        $this->setIconvErrorHandler();
        if ($fromCharset != "") {
            $conv = iconv($fromCharset, $targetCharset, $text);
            // first off, check if the charset is windows-1250 if  encoding fails
            // broaden to windows-1252 then
            if (($conv === false || $this->lastIconvError) && strtolower($fromCharset) == 'windows-1250') {
                $this->lastIconvError = false;
                $conv = iconv('windows-1252', $targetCharset, $text);
            }
            // check if the charset is us-ascii and broaden to windows-1252
            // if encoding attempt fails
            if (($conv === false || $this->lastIconvError) && strtolower($fromCharset) == 'us-ascii') {
                $this->lastIconvError = false;
                $conv = iconv('windows-1252', $targetCharset, $text);
            }
            // fallback! if we have mb-extension installed, we'll try to detect the encoding, if
            // first try with iconv didn't work
            if (($conv === false || $this->lastIconvError) && function_exists('mb_detect_encoding')) {
                $this->lastIconvError = false;
                $peekEncoding = mb_detect_encoding($text, $this->getEncodingList(), true);
                $conv = iconv($peekEncoding, $targetCharset, $text);
            }
            if ($conv === false || $this->lastIconvError) {
                $this->lastIconvError = false;
                $conv = iconv($fromCharset, $targetCharset . '//TRANSLIT', $text);
            }
            if ($conv === false || $this->lastIconvError) {
                $this->lastIconvError = false;
                $conv = iconv($fromCharset, $targetCharset . '//IGNORE', $text);
            }
            if ($conv !== false && !$this->lastIconvError) {
                $text = $conv;
            }
        } else {
            $conv = false;
            if (function_exists('mb_detect_encoding')) {
                $this->lastIconvError = false;
                $peekEncoding = mb_detect_encoding($text, $this->getEncodingList(), true);
                $conv = iconv($peekEncoding, $targetCharset, $text);
            }
            if ($conv === false || $this->lastIconvError) {
                $this->lastIconvError = false;
                $conv = iconv('UTF-8', $targetCharset . '//IGNORE', $text);
            }
            if ($conv !== false && !$this->lastIconvError) {
                $text = $conv;
            }
        }
        $this->restoreErrorHandler();

        return $text;
    }


    /**
     * Sets the error handler to this instance's iconvErrorHandler.
     * API needs to make sure to restore the original error handler later on.
     */
    protected function setIconvErrorHandler()
    {
        $this->lastIconvError = false;
        set_error_handler(array($this, 'iconvErrorHandler'));
    }


    /**
     * Restores the error handler.
     *
     * @see restore_error_handler
     */
    protected function restoreErrorHandler()
    {
        $this->lastIconvError = false;
        restore_error_handler();
    }


    /**
     * Error handler for iconv operations. Simply sets the lastIconvError flag.
     */
    protected function iconvErrorHandler()
    {
        $this->lastIconvError = true;
    }


    /**
     * Returns a list of charset encodings, comma-separated.
     * @return string
     */
    protected function getEncodingList()
    {
        return 'UCS-4, UCS-4BE, UCS-4LE, UCS-2, UCS-2BE, UCS-2LE, UTF-32, UTF-32BE, UTF-32LE, UTF-16, UTF-16BE, UTF-16LE, UTF-8, UTF-7, UTF7-IMAP,  ASCII, EUC-JP, SJIS, eucJP-win, CP51932, JIS, ISO-2022-JP,  ISO-2022-JP-MS, Windows-1252, ISO-8859-1, ISO-8859-2, ISO-8859-3, ISO-8859-4,  ISO-8859-5, ISO-8859-6, ISO-8859-7, ISO-8859-8, ISO-8859-9, ISO-8859-10, ISO-8859-13,  ISO-8859-14, ISO-8859-15, ISO-8859-16, EUC-CN, CP936, HZ, EUC-TW, BIG-5, EUC-KR,  UHC, ISO-2022-KR, Windows-1251, CP866, KOI8-R, ArmSCII-8';
    }



}