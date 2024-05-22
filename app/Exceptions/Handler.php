<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Auth\AuthenticationException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        if($exception instanceof ValidationException){

            if(request()->ajax() || request()->wantsJson()){

                $error  = array_values($exception->errors())[0][0];

                return response()->json([

                    'status'    => 'Failed',
                    'message'   => 'Validation Error',
                    'data'      => $error

                ], 422);

            }
            
        }

        if($exception instanceof AuthenticationException){

            if(request()->ajax() || request()->wantsJson()){

                return response()->json([

                    'status'    => 'Failed',
                    'message'   => 'Unauthorized! Contact Admin', 
                    'data'      => null

                ], 401);

            }
            
        }

        return parent::render($request, $exception);
    }
}
