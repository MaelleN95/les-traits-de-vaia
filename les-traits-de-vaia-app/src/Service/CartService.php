<?php
namespace App\Service;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    private $session;

    public function __construct(RequestStack $requestStack, private ProductRepository $repo)
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
        $cart[$productId] = ($cart[$productId] ?? 0) + $qty;
        $this->session->set('cart', $cart);
    }

    public function clear(): void
    {
        $this->session->remove('cart');
    }

    public function getTotal(): int
    {
        $total = 0;
        foreach ($this->getCart() as $id => $qty) {
            $product = $this->repo->find($id);
            if ($product) {
                $total += $product->getPrice() * $qty;
            }
        }
        return $total;
    }

    public function getDetailedItems(): array
    {
        $items = [];
        foreach ($this->getCart() as $id => $qty) {
            $product = $this->repo->find($id);
            if ($product) {
                $items[] = ['product' => $product, 'qty' => $qty];
            }
        }
        return $items;
    }
}
