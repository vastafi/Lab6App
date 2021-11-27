<?php


namespace App\Controller;


use App\Entity\Order;
use Symfony\Bridge\Twig\Mime\BodyRenderer;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class MailerController extends AbstractController
{
    /**
     * @Route("/email_status", name="send_email_status")
     */
    public function sendEmail(MailerInterface $mailer, Order $order):void
    {
        if ($order->getStatus() == 'New'){
            $email = (new TemplatedEmail())
                ->from(new Address('simple.store@gmail.com', 'Simple Store'))
                ->to( new Address($order->getUser()->getEmail()))
                ->subject("Order confirmation")
                ->htmlTemplate('emails/new_order_email.html.twig')
                ->context([
                    'order' => $order
                ]);

            $mailer->send($email);
        }
        else{
        $email = (new TemplatedEmail())
            ->from(new Address('simple.store@gmail.com', 'Simple Store'))
            ->to( new Address($order->getUser()->getEmail()))
            ->subject("Order details")
            ->htmlTemplate('emails/status_change_email.html.twig')
            ->context([
                'order' => $order
            ]);

        $mailer->send($email);
        }
    }


}