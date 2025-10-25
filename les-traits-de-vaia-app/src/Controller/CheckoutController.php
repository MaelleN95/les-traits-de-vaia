<?php
namespace App\Controller;

use Stripe\Stripe;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Form\CheckoutType;
use App\Service\CartService;
use Stripe\Checkout\Session;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CheckoutController extends AbstractController
{
    #[Route('/checkout', name: 'checkout')]
    public function checkout(Request $request, CartService $cartService, EntityManagerInterface $em)
    {
        $form = $this->createForm(CheckoutType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $items = $cartService->getDetailedItems();
            if (empty($items)) {
                dd('Panier vide', $cartService->getCart());
            }

            if(empty($items)) {
                throw $this->createNotFoundException('Panier vide');
            }

            $order = new Order();
            $order->setUser($this->getUser());
            if (!$this->getUser()) {
                dd('Utilisateur non connecté');
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

            return $this->redirectToRoute('payment_simulate', ['orderId' => $order->getId()]);
        }

        return $this->render('checkout/form.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/payment/simulate/{orderId}', name:'payment_simulate')]
    public function simulatePayment(int $orderId, EntityManagerInterface $em)
    {
        $order = $em->getRepository(Order::class)->find($orderId);
        if(!$order) throw $this->createNotFoundException('Order not found');

        return $this->render('payment/simulate.html.twig', [
            'order' => $order
        ]);
    }

    #[Route('/payment/{orderId}', name: 'payment_start', methods: ['POST'])]
    public function start(int $orderId, EntityManagerInterface $em): Response
    {
        $order = $em->getRepository(Order::class)->find($orderId);
        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }

        // Vérifie correctement la clé
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

        return $this->redirect($session->url, 303);
    }

    #[Route('/payment/complete/{orderId}', name:'payment_complete', methods:['POST'])]
    public function completePayment(int $orderId, EntityManagerInterface $em, CartService $cartService)
    {
        $order = $em->getRepository(Order::class)->find($orderId);
        if(!$order) throw $this->createNotFoundException('Order not found');

        $order->setStatus('paid');
        $order->setPaidAt(new \DateTimeImmutable());
        $em->flush();

        // Vider le panier
        $cartService->clear();

        // ici tu pourrais dispatcher un message Messenger pour envoyer un email

        $this->addFlash('success', 'Paiement effectué avec succès !');

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
