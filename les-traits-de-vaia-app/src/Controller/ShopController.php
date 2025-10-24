<?php
namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShopController extends AbstractController
{
    #[Route('/', name: 'shop_index')]
    public function index(Request $request, ProductRepository $productRepo): Response
    {
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = 12;

        $products = $productRepo->findByPage($page, $limit);

        return $this->render('shop/index.html.twig', [
            'products' => $products['items'],
            'total'    => $products['total'],
            'page'     => $page,
            'limit'    => $limit,
        ]);
    }

    #[Route('/category/{slug}', name: 'shop_category')]
    public function category(string $slug, Request $request, CategoryRepository $categoryRepo, ProductRepository $productRepo): Response
    {
        $category = $categoryRepo->findOneBy(['slug' => $slug]);
        if (!$category) {
            throw $this->createNotFoundException('Category not found.');
        }

        $page = max(1, (int)$request->query->get('page', 1));
        $limit = 12;

        $products = $productRepo->findByCategoryPage($category, $page, $limit);

        return $this->render('shop/category.html.twig', [
            'category' => $category,
            'products' => $products['items'],
            'total'    => $products['total'],
            'page'     => $page,
            'limit'    => $limit,
        ]);
    }

    #[Route('/product/{slug}', name: 'shop_product')]
    public function product(string $slug, ProductRepository $productRepo): Response
    {
        $product = $productRepo->findOneBy(['slug' => $slug]);
        if (!$product) {
            throw $this->createNotFoundException('Product not found.');
        }

        return $this->render('shop/product.html.twig', [
            'product' => $product
        ]);
    }
}
