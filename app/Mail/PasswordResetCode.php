<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetCode extends Mailable
{
    use Queueable, SerializesModels;

    public $code;
    public $userName;

    public function __construct(string $code, string $userName)
    {
        $this->code = $code;
        $this->userName = $userName;
    }

    public function build()
    {
        return $this->subject('كود إعادة تعيين كلمة المرور')
                    ->markdown('emails.password-reset', [
                        'code' => $this->code,
                        'userName' => $this->userName,
                    ]);
    }
}
