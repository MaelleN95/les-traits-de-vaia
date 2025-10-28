<?php
namespace App\Controller;

use Stripe\Stripe;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Form\CheckoutType;
use App\Service\CartService;
use Stripe\Checkout\Session;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'checkout')]
    public function checkout(Request $request, CartService $cartService, EntityManagerInterface $em, SessionInterface $session)
    {
        $form = $this->createForm(CheckoutType::class);

        $savedData = $session->get('checkout_data', []);
        if (!empty($savedData)) {
            $form->get('fullname')->setData($savedData['fullname'] ?? null);
            $form->get('address')->setData($savedData['address'] ?? null);
            $form->get('city')->setData($savedData['city'] ?? null);
            $form->get('postcode')->setData($savedData['postcode'] ?? null);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $session->set('checkout_data', [
                'fullname' => $data['fullname'],
                'address'  => $data['address'],
                'city'     => $data['city'],
                'postcode'  => $data['postcode'],
            ]);

            $items = $cartService->getDetailedItems();

            if (empty($items)) {
                $this->addFlash('error', 'Votre panier est vide.');
                return $this->redirectToRoute('cart_index');
            }

            if(empty($items)) {
                $this->addFlash('error', 'Votre panier est vide.');
                return $this->redirectToRoute('cart_index');
            }

            $order = new Order();
            
            $order->setUser($this->getUser());
            if (!$this->getUser()) {
                $this->addFlash('error', 'Vous devez être connecté pour passer une commande.');
                return $this->redirectToRoute('app_login');
            }

            $order->setCreatedAt(new \DateTimeImmutable());
            $order->setStatus('pending');
            $order->setTotal($cartService->getTotal());
            $order->setAddress($data['address']);

            foreach($items as $it){
                $oi = new OrderItem();
                $oi->setProduct($it['product'])
                   ->setQuantity($it['qty'])
                   ->setUnitPrice($it['product']->getPrice())
                   ->setCustomerOrder($order);
                $em->persist($oi);
            }

            $em->persist($order);
            $em->flush();

            return $this->redirectToRoute('payment_order_details', ['orderId' => $order->getId()]);
        }

        return $this->render('checkout/form.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/payment/order-details/{orderId}', name:'payment_order_details')]
    public function orderDetailsPayment(int $orderId, EntityManagerInterface $em)
    {
        $order = $em->getRepository(Order::class)->find($orderId);
        if(!$order) throw $this->createNotFoundException('Order not found');

        return $this->render('payment/order-details.html.twig', [
            'order' => $order
        ]);
    }

    #[Route('/payment/{orderId}', name: 'payment_start', methods: ['POST', 'GET'])]
    public function start(int $orderId, EntityManagerInterface $em): JsonResponse
    {
        $order = $em->getRepository(Order::class)->find($orderId);
        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }
 
        if (!isset($_ENV['STRIPE_SECRET_KEY']) || empty($_ENV['STRIPE_SECRET_KEY'])) {
            throw new \LogicException('Stripe secret key is missing.');
        }

        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        $unitAmount = (int) $order->getTotal(); // en centimes
        if ($unitAmount <= 0) {
            throw new \LogicException('Invalid order total amount.');
        }

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => ['name' => 'Commande #' . $order->getId()],
                    'unit_amount' => $unitAmount,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('payment_complete', ['orderId' => $orderId], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url'  => $this->generateUrl('payment_failed', ['orderId' => $orderId], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        // On renvoie juste l’URL, pas de redirection serveur car il supprime ce qu'il se trouve après le dièze dans l'url => Amène à l'erreur "Something went wrong The page you were looking for could not be found. Please check the URL or contact the merchant."
        return $this->json(['url' => $session->url]);
    }

    #[Route('/payment/complete/{orderId}', name:'payment_complete')]
    public function completePayment(int $orderId, EntityManagerInterface $em, CartService $cartService, SessionInterface $session, MailerInterface $mailer)
    {
        $order = $em->getRepository(Order::class)->find($orderId);
        if(!$order) throw $this->createNotFoundException('Order not found');

        $order->setStatus('paid');
        $order->setPaidAt(new \DateTimeImmutable());
        $em->flush();

        $cartService->clear();

        $session->remove('checkout_data');

        $email = (new TemplatedEmail())
            ->from(new Address($_ENV['MAILER_FROM'], 'Les traits de Vaia'))
            ->to($order->getUser()->getEmail())
            ->subject('Confirmation de votre commande fictive n°' . $order->getId())
            ->htmlTemplate('emails/order_confirmation.html.twig')
            ->context([
                'order' => $order,
                'user' => $order->getUser(),
            ]); 

        try {
            $mailer->send($email);
        } catch (\Throwable $e) {
            $this->addFlash('warning', 'Le paiement est validé, mais l’e-mail de confirmation n’a pas pu être envoyé.');
        }

        $this->addFlash('success', 'Paiement effectué avec succès ! Un e-mail de confirmation vous a été envoyé.');


        return $this->redirectToRoute('shop_index');
    }

    #[Route('/payment/failed/{orderId}', name: 'payment_failed', methods: ['POST','GET'])]
    public function failed(int $orderId, EntityManagerInterface $em): Response
    {
        $order = $em->getRepository(Order::class)->find($orderId);
        if ($order) {
            $order->setStatus('failed');
            $em->flush();
        }
        $this->addFlash('error', 'Paiement échoué. Votre panier est conservé.');
        return $this->redirectToRoute('cart_index');
    }
}
