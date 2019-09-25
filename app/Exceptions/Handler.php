<?php

namespace App\Exceptions;

use \Exception;
use \ErrorException;

use Symfony\Component\Debug\Exception\FlattenException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\Debug\ExceptionHandler as SymfonyExceptionHandler;

use Symfony\Component\Debug\Exception\FatalThrowableError;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use App\Exceptions\DummyException;

use App\Mail\ExceptionOccured;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        ///
    ];

    protected $shouldCapture = [
        FatalThrowableError::class,
        FatalErrorException::class,
        CommandNotFoundException::class,
        DummyException::class,
        Exception::class,
        ErrorException::class,
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
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        if ($this->shouldReport($exception)) {
            $this->sendEmail($exception); // sends an email
        }       
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($this->is404($exception)) {
            $this->log404($request);
        }
        return parent::render($request, $exception);
    }

/////////////////////////////////////////////////////////////////////////////////////////

    private function is404($exception)
    {
        return $exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                || $exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;                                        
    }

    private function log404($request) 
    {
        $error = [
            'url'    => $request->url(),
            'method' => $request->method(),
            'data'   => $request->all(),
        ];

        $message = '404: ' . $error['url'] . "\n" . json_encode($error, JSON_PRETTY_PRINT);

        Log::debug($message);
    }

/////////////////////////////////////////////////////////////////////////////////////////

    public function shouldReport(Exception $exception)
    {
        
        if (!is_array($this->shouldCapture)) {
            return false;
        }
        if (in_array('*', $this->shouldCapture)) {
            return true;
        }
        foreach ($this->shouldCapture as $type) {
            if ($exception instanceof $type) {
                return true;
            }                                  
        }
        return false;
    }

    public function sendEmail(Exception $exception)
    {

        try {
            $e = FlattenException::create($exception);

            $handler = new SymfonyExceptionHandler();
            $html = $handler->getHtml($e);
            $error_type = get_class($e);

            Mail::to('bahri@genel.com')->send(new ExceptionOccured($error_type, $html));
            Log::info('hata bilgisi gönderildi');

        } catch (Exception $ex) {
            dd($ex);
        }

    }

/////////////////////////////////////////////////////////////////////////////////////////    


}
