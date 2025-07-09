<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordChangedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function build()
    {
        $html = "
            <h2>Bonjour {$this->user->prenom} {$this->user->nom},</h2>
            <p>Votre mot de passe a été changé avec succès.</p>
            <p>Si ce n’était pas vous, merci de contacter immédiatement votre administrateur.</p>
            <p>Cordialement,<br>L’équipe sécurité</p>
        ";

        return $this->subject('🔐 Confirmation de changement de mot de passe')
                    ->html($html);
    }
}
