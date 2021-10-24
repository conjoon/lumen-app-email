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

namespace Conjoon\Http\Query;

use Conjoon\Core\ParameterBag;
use Conjoon\Core\ResourceQuery;

/**
 * Abstract class providing API for translating parameters of an
 * arbitrary origin into a ResourceQuery.
 *
 * Class QueryTranslator
 * @package Conjoon\Http
 */
abstract class QueryTranslator
{

    /**
     * Returns the parameters this class expects and understands for
     * translating.
     *
     * @return array
     */
    abstract protected function getExpectedParameters(): array;


    /**
     * Returns the translated Parameters in a Parameter Bag.
     * Throws if the query was not correct according to the rules
     * this Translator uses.
     * The ParameterBag returned does not reference the same ParameterBag
     * that was passed as an argument.
     *
     * @param ParameterBag $parameters
     *
     * @return ResourceQuery
     *
     * @throws InvalidQueryException
     */
    abstract protected function translateParameters(ParameterBag $parameters): ResourceQuery;


    /**
     * Extracts parameters from the specified resource and returns it as an array.
     * This method is called internally to make sure an arbitrary resource type can be
     * used for applying translation rules.
     *
     * @param $parameterResource
     * @return array
     *
     * @throws InvalidParameterResourceException
     */
    abstract protected function extractParameters($parameterResource): array;


    /**
     * Method to be called to ensure Parameters extracted from the given resource
     * are understood by this translator. Invokes translation afterwards.
     *
     * @param mixed $parameterResource an arbitrary type of resource of which parameters can get
     * extracted.
     *
     */
    public function translate($parameterResource): ResourceQuery
    {
        $parameters = $this->validateParameters(
            $this->extractParameters($parameterResource)
        );

        return $this->translateParameters(new ParameterBag($parameters));
    }


    /**
     * Validates the specified list of parameters against the list of
     * expected Parameters. If there are parameters in the submitted list
     * this translator does not expect, an exception is thrown.
     *
     * @param array $parameters
     * @return array
     *
     * @see #getExpectedParameters
     *
     * @throws InvalidQueryParameterException
     */
    protected function validateParameters(array $parameters): array
    {
        $allowed = $this->getExpectedParameters();

        $spill = array_diff(array_keys($parameters), $allowed);
        if (count($spill) > 0) {
            throw new InvalidQueryParameterException(
                "found additional parameters " .
                "\"" . implode("\", \"", $spill) . "\""
            );
        }

        return $parameters;
    }
}
