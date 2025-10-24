<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\Category;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findByPage(int $page = 1, int $limit = 12): array
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $paginator = new Paginator($qb);
        $total = count($paginator);
        $items = iterator_to_array($paginator);

        return ['items' => $items, 'total' => $total];
    }

    public function findByCategoryPage(Category $category, int $page = 1, int $limit = 12): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.category = :cat')
            ->setParameter('cat', $category)
            ->orderBy('p.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $paginator = new Paginator($qb);
        $total = count($paginator);
        $items = iterator_to_array($paginator);

        return ['items' => $items, 'total' => $total];
    }
}
