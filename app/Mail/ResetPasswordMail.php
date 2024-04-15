<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $link;

    public function __construct($user, $link)
    {
        $this->user = $user;
        $this->link = $link;
    }

    public function build()
    {
        return $this->view('mail.reset-password-mail')
            ->subject("Reset Password")
            ->with([
                'userName' => $this->user->first_name . ' ' . $this->user->last_name,
                'passwordResetLink' => $this->link
            ]);
    }
}
