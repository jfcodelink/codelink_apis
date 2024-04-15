<?php
// App\Mail\LeaveApplied.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeaveApplied extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $data;

    public function __construct($subject, $data)
    {
        $this->subject = $subject;
        $this->data = $data;
    }

    public function build()
    {
        return $this->subject($this->subject)
                    ->view('mail.leave_apply')
                    ->with('data', $this->data);
    }
}
