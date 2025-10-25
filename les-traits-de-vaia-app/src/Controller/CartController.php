<?php
namespace App\Controller;

use App\Service\CartService;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/cart', name: 'cart_')]
class CartController extends AbstractController
{

    private CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    #[Route('/', name: 'index')]
    public function index(CartService $cartService): Response
    {
        $items = $cartService->getDetailedCartItems();
        $total = $cartService->getTotal();

        return $this->render('cart/index.html.twig', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    /**
     * Ajoute un produit au panier
     */
    #[Route('/add/{id}', name: 'add')]
    public function add(int $id, Request $request): Response
    {
        $qty = max(1, (int) $request->request->get('qty', 1));

        $this->cartService->add($id, $qty);
        $this->addFlash('success', 'Produit ajouté au panier.');

        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?? $this->generateUrl('shop_index'));
    }

    /**
     * Retire une unité d’un produit
     */
    #[Route('/remove-one/{id}', name: 'remove_one')]
    public function removeOne(int $id): Response
    {
        $this->cartService->removeOne($id);

        $this->addFlash('success', 'Quantité mise à jour.');
        return $this->redirectToRoute('cart_index');
    }

    /**
     * Retire complètement un produit
     */
    #[Route('/remove/{id}', name: 'remove')]
    public function remove(int $id): Response
    {
        $this->cartService->remove($id);

        $this->addFlash('success', 'Produit supprimé du panier.');
        return $this->redirectToRoute('cart_index');
    }

    /**
     * Vide le panier
     */
    #[Route('/clear', name: 'clear')]
    public function clear(): Response
    {
        $this->cartService->clear();

        $this->addFlash('success', 'Panier vidé.');
        return $this->redirectToRoute('cart_index');
    }
}