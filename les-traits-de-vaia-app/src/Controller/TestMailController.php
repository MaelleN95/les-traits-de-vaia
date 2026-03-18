<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class TestMailController extends AbstractController
{
    #[Route('/test-mail', name: 'test_mail')]
    public function index(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('contact@koji-dev.fr')
            ->to('maelle.nioche@gmail.com')
            ->subject('Test Symfony + Brevo')
            ->text('Mail de test envoyé via Brevo SMTP !');

        $mailer->send($email);

        return new Response('✅ Email envoyé.');
    }
}
