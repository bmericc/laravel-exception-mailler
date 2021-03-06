<?php

namespace App\Exceptions;

use \Exception;
use \ErrorException;
use \RuntimeException;

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
use Illuminate\Support\Facades\Request;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    protected $shouldCapture = [
        FatalThrowableError::class,
        FatalErrorException::class,
        CommandNotFoundException::class,
        DummyException::class,
        ErrorException::class,
        RuntimeException::class,
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
            $request = (object)[
                'url' => Request::url(),
                'inputs' => Request::all(),
                'ip' => $this->getClientIp()
            ];
            $this->sendEmail($exception, $request); // sends an email
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

    private function isAuth($exception)
    {
        return $exception instanceof \Illuminate\Auth\AuthenticationException
            || $exception instanceof \Illuminate\Auth\Access\AuthorizationException;
    }

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
        if ($this->is404($exception)) return false;
        if ($this->isAuth($exception)) return false;

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

    public function sendEmail(Exception $exception, $request)
    {

        try {
            $e = FlattenException::create($exception);

            $handler = new SymfonyExceptionHandler();
            $html = $handler->getHtml($e);
            $error_type = get_class($e);

            Mail::to('bahri@tarti.com')->send(new ExceptionOccured($error_type, $html, $request));
            Log::info('hata bilgisi gönderildi');

        } catch (Exception $ex) {
            dd($ex);
        }

    }

    public function getClientIp() {
        
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if(getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if(getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if(getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if(getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if(getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';

        return $ipaddress; 
    }

    /////////////////////////////////////////////////////////////////////////////////////////    

    public function unauthenticated($request=false, $exception=false)
    {
        // return ''; // use redirect('/login') or something if you want to redirect to login.
        return redirect('/login');
    }
  
}
