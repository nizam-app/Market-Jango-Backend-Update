<?php


namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VendorInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $tempPassword;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $tempPassword)
    {
        $this->user = $user;
        $this->tempPassword = $tempPassword;
    }

    public function build()
    {
        return $this->subject('Your Market Jango vendor account')
            ->view('emails.vendor_invite')
            ->with([
                'name' => $this->user->name,
                'email' => $this->user->email,
                'tempPassword' => $this->tempPassword,
                // frontend er login page link jodi dite chao
                'loginUrl' => config('app.frontend_url', 'http://localhost:5173/login'),
            ]);
    }
}
