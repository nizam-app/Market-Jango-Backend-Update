<?php


namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $tempPassword;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $tempPassword)
    {
        $this->user         = $user;
        $this->tempPassword = $tempPassword;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Your Market Jango admin account')
            ->view('emails.admin_invite')
            ->with([
                'name'         => $this->user->name,
                'email'        => $this->user->email,
                'tempPassword' => $this->tempPassword,
                // frontend er login page link jodi dite chao
                'loginUrl'     => config('app.frontend_url', 'http://localhost:5173/login'),
            ]);
    }

//    public User $user;
//
//    /**
//     * Create a new message instance.
//     */
//    public function __construct(User $user)
//    {
//        $this->user = $user;
//    }
//
//    /**
//     * Build the message.
//     */
//    public function build()
//    {
//        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');
//        $inviteLink = $frontendUrl . '/set-password/' . $this->user->invite_token;
//
//        return $this->subject('Your Market Jango admin account')
//            ->view('emails.admin_invite')
//            ->with([
//                'name' => $this->user->name,
//                'inviteLink' => $inviteLink,
//            ]);
//    }
}
//
//namespace App\Mail;
//
//use App\Models\User;
//use Illuminate\Bus\Queueable;
//use Illuminate\Contracts\Queue\ShouldQueue;
//use Illuminate\Mail\Mailable;
//use Illuminate\Mail\Mailables\Content;
//use Illuminate\Mail\Mailables\Envelope;
//use Illuminate\Queue\SerializesModels;
//
//class AdminInviteMail extends Mailable
//{
//    use Queueable, SerializesModels;
//
//    /**
//     * Create a new message instance.
//     */
//    public User $user;
//
//    public function __construct(User $user)
//    {
//        $this->user = $user;
//    }
//
//    /**
//     * Get the message envelope.
//     */
//    public function envelope(): Envelope
//    {
//        return new Envelope(
//            subject: 'Admin Invite Mail',
//        );
//    }
//
//    /**
//     * Get the message content definition.
//     */
//    public function content(): Content
//    {
//        return new Content(
//            view: 'view.name',
//        );
//    }
//
//    /**
//     * Get the attachments for the message.
//     *
//     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
//     */
//    public function attachments(): array
//    {
//        return [];
//    }
//}
