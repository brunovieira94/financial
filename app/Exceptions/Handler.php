<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;

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
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        //$path = $request->path() ?? 'not detected';
        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            //Log::alert('error route: ' . $path . ' => ' . $exception->getMessage());
            return response($exception, 404);
        } elseif ($exception instanceof \Illuminate\Auth\AuthenticationException){
            //Log::emergency('error route: ' . $path . ' => ' . 'user without permission');
            return response($exception, 401);
        }elseif ($exception instanceof \Exception && !($exception instanceof \Illuminate\Validation\ValidationException)){
            //Log::emergency('error route: ' . $path . ' => ' . $exception->getMessage());
            return response($exception, 500);
        }
        if(strpos($exception->getFile(), 'CangoorooService') && strpos($exception->getMessage(), 'Maximum execution time of') !== false){
            return response()->json([
                'error' => 'A API do Cangooroo parece não estar respondendo',
            ], 422);
        }

        //Log::error('error route: ' . $path . ' => ' . $exception->getMessage());
        return parent::render($request, $exception);
    }
}
