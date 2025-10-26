<?php
namespace App\Security;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Symfony\Component\Mime\Address;
use App\Entity\User;

class EmailVerifier
{
    private $mailer;
    private $verifyEmailHelper;
    private $fromEmail;

    public function __construct(MailerInterface $mailer, VerifyEmailHelperInterface $verifyEmailHelper, string $fromEmail)
    {
        $this->mailer = $mailer;
        $this->verifyEmailHelper = $verifyEmailHelper;
        $this->fromEmail = $fromEmail;
    }

    public function sendEmailConfirmation(string $routeName, User $user): void
    {
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $routeName,
            $user->getId(),
            $user->getEmail()
        );

        $email = (new TemplatedEmail())
            ->from(new Address('nioche.maelle@gmail.com', 'Nom de ton site'))
            ->to($user->getEmail())
            ->subject('Veuillez confirmer votre adresse email')
            ->htmlTemplate('emails/confirmation_email.html.twig')
            ->context([
                'signedUrl' => $this->verifyEmailHelper->generateSignature(
                    'app_verify_email',
                    $user->getId(), // <-- ici tu passes l'ID
                    $user->getEmail(),
                    ['id' => $user->getId()]
                )->getSignedUrl(),
                'expiresAtMessageKey' => '...',
                'expiresAtMessageData' => ['%count%' => 3600],
                'user' => $user,
            ]);

        $this->mailer->send($email);
    }

    public function handleEmailConfirmation($request, User $user)
    {
        $this->verifyEmailHelper->validateEmailConfirmationFromRequest(
            $request, 
            $user->getId(), 
            $user->getEmail()
        );
        $user->setIsVerified(true);
    }
}
