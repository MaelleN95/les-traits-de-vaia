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
            $user->getEmail(),
            ['id' => $user->getId()],
            86400
        );

        $signedUrl = $signatureComponents->getSignedUrl();

        $email = (new TemplatedEmail())
            ->from(new Address('nioche.maelle@gmail.com', 'Les traits de Vaia'))
            ->to($user->getEmail())
            ->subject('Veuillez confirmer votre adresse email')
            ->htmlTemplate('emails/confirmation_email.html.twig')
            ->context([
                'signedUrl' => $signedUrl,
                'expiresAtMessageKey' => $signatureComponents->getExpirationMessageKey(),
                'expiresAtMessageData' => $signatureComponents->getExpirationMessageData(),
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
