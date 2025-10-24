<?php
namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Form\CheckoutType;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

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
}
