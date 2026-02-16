<?php

namespace App\Jobs;

use App\Mail\ForgotPasswordMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendForgotPasswordMailJob implements ShouldQueue
{
    use Queueable;

    //public $tries = 3;
    //public $backoff = 60; //Tiempo entre intentos
    //public $queue = 'emails'; //Cola por defecto

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $email, 
        public string $code, 
    )
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->email)->send(new ForgotPasswordMail($this->code));
    }

    /* public function failed(Exception $exception)
    {
        Log::error($exception);
    } */
}
