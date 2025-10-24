<?php
namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    #[Route('/cart/add/{id}', name: 'cart_add')]
    public function add(int $id, CartService $cartService, ProductRepository $productRepo): RedirectResponse
    {
        $product = $productRepo->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Product not found.');
        }

        $cartService->add($id);

        $this->addFlash('success', sprintf('Produit "%s" ajouté au panier.', $product->getName()));

        // Redirige vers la page précédente ou vers le catalogue
        return $this->redirectToRoute('shop_index');
    }
}
