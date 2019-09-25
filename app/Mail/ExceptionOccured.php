<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ExceptionOccured extends Mailable
{
    use Queueable, SerializesModels;
    /**
     * The body of the message.
     *
     * @var string
     */
    public $content;
    public $subject;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject, $content)
    {
        $this->content = $content;
        $this->subject = $subject;
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.exception')
            ->subject(env('APP_NAME', "Site")." - " . $this->subject)
            ->with(['subject' => $this->subject, 'content' => $this->content]);
    }
}