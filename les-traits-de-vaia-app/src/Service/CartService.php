<?php
namespace App\Service;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    private $session;

    public function __construct(RequestStack $requestStack, private ProductRepository $productRepository)
    {
        $this->session = $requestStack->getSession();
    }

    public function getCart(): array
    {
        return $this->session->get('cart', []);
    }

    public function add(int $productId, int $qty = 1): void
    {
        $cart = $this->getCart();

        if (isset($cart[$productId])) {
            $cart[$productId] += $qty;
        } else {
            $cart[$productId] = $qty;
        }
        
        $this->session->set('cart', $cart);
    }

    public function clear(): void
    {
        $this->session->remove('cart');
    }

    /**
     * Retire complètement un produit du panier
     */
    public function remove(int $productId): void
    {
        $cart = $this->session->get('cart', []);
        unset($cart[$productId]);
        $this->session->set('cart', $cart);
    }

    public function removeOne(int $productId): void
    {
        $cart = $this->session->get('cart', []);

        if (!isset($cart[$productId])) {
            return;
        }

        $cart[$productId]--;

        if ($cart[$productId] <= 0) {
            unset($cart[$productId]);
        }

        $this->session->set('cart', $cart);
    }

    public function getTotal(): int
    {
        $total = 0;
        foreach ($this->getCart() as $id => $qty) {
            $product = $this->productRepository->find($id);
            if ($product) {
                $total += $product->getPrice() * $qty;
            }
        }
        return $total;
    }

    public function getDetailedCartItems(): array
    {
        $cart = $this->session->get('cart', []);
        $detailedCart = [];

        foreach ($cart as $id => $quantity) {
            $product = $this->productRepository->find($id);

            if ($product) {
                $detailedCart[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                ];
            }
        }

        return $detailedCart;
    }

    public function getDetailedItems(): array
    {
        $items = [];
        foreach ($this->getCart() as $id => $qty) {
            $product = $this->productRepository->find($id);
            if ($product) {
                $items[] = ['product' => $product, 'qty' => $qty];
            }
        }
        return $items;
    }
}
