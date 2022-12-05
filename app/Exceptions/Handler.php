<?php

namespace App\Exceptions;

use Exception;
use Throwable;
use App\Http\Controllers\DialogFlowController;
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
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param Throwable $exception
     *
     * @return void
     * @throws Exception
     */
    public function report(Throwable $exception): void
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     * @param Throwable $exception
     *
     * @return Response|JsonResponse
     */
    public function render($request, Throwable $exception)
    {
        error_log($exception);
        return DialogFlowController::createTextToSpeechResponse(null, 'I could not fulfill your request. Someone maybe forgot a comma somewhere. The exact problem lies in ' . $exception->getFile() . ': ' . $exception->getMessage() . '.');
    }
}
