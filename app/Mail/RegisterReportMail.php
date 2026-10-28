<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegisterReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reportData;
    public $businessName;
    public $closedAt;

    public function __construct(array $reportData, string $businessName, string $closedAt)
    {
        $this->reportData   = $reportData;
        $this->businessName = $businessName;
        $this->closedAt     = $closedAt;
    }

    public function build()
    {
        return $this->subject($this->businessName . ' — Register Close Report (' . $this->closedAt . ')')
                    ->view('emails.register_report');
    }
}
