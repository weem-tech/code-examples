<?php

namespace App\Models\Fragments\User;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

trait Mailer
{
    public function sendMail(Mailable $mailable): void
    {
        Mail::to($this->email)->queue($mailable);
    }
}
