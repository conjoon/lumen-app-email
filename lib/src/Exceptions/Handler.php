<?php

/**
 * This file is part of the conjoon/lumen-app-email project.
 *
 * (c) 2019-2024 Thorsten Suckow-Homberg <thorsten@suckow-homberg.de>
 *
 * For full copyright and license information, please consult the LICENSE-file distributed
 * with this source code.
 */

namespace App\Exceptions;

use Conjoon\Http\Json\Problem\ProblemFactory;
use Conjoon\Http\Exception\HttpException as ConjoonHttpException;
use Throwable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];


    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     * @param Throwable $e
     *
     * @return Response|JsonResponse
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof ConjoonHttpException) {
            return response()->json(
                ...ProblemFactory::makeJson(
                    $e->getCode(),
                    null,
                    $e->getMessage()
                )
            );
        }
        return parent::render($request, $e);
    }
}
