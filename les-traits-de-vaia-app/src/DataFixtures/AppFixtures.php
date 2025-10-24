<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private SluggerInterface $slugger
    ) {}

    public function load(ObjectManager $manager): void
    {
        // --- 1. Création des catégories ---
        $categoriesData = [
            'T-shirts' => 'Collection de t-shirts unis ou imprimés',
            'Sweats'   => 'Sweats et hoodies confortables',
            'Accessoires' => 'Casquettes, sacs et autres accessoires',
        ];

        $categories = [];
        foreach ($categoriesData as $name => $desc) {
            $category = new Category();
            $category->setName($name);
            $category->setSlug(strtolower($this->slugger->slug($name)));
            $category->setDescription($desc);
            $manager->persist($category);
            $categories[] = $category;
        }

        // --- 2. Création des produits ---
        $productsData = [
            ['T-shirt basique', 'T-shirt classique 100% coton', 1999, 100, 'T-shirts'],
            ['T-shirt premium', 'T-shirt épais, coupe ajustée', 2999, 50, 'T-shirts'],
            ['Sweat à capuche', 'Sweat unisexe molletonné', 4499, 30, 'Sweats'],
            ['Casquette', 'Casquette taille réglable', 1499, 80, 'Accessoires'],
            ['Sac en toile', 'Sac en toile durable 30L', 2499, 40, 'Accessoires'],
        ];

        foreach ($productsData as [$name, $desc, $price, $stock, $catName]) {
            $product = new Product();
            $product->setName($name);
            $product->setSlug(strtolower($this->slugger->slug($name)));
            $product->setDescription($desc);
            $product->setPrice($price);
            $product->setStock($stock);
            $product->setCreatedAt(new \DateTimeImmutable());
            $product->setImage('https://picsum.photos/seed/' . urlencode($name) . '/400/400');
            // liaison catégorie
            foreach ($categories as $cat) {
                if ($cat->getName() === $catName) {
                    $product->setCategory($cat);
                    break;
                }
            }
            $manager->persist($product);
        }

        // --- 3. Création d’un utilisateur admin ---
        $admin = new User();
        $admin->setFullname('Admin Boutique');
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'adminpass'));
        $manager->persist($admin);

        // --- 4. Création d’un utilisateur client ---
        $client = new User();
        $client->setFullname('Client Démo');
        $client->setEmail('client@example.com');
        $client->setRoles(['ROLE_USER']);
        $client->setPassword($this->passwordHasher->hashPassword($client, 'clientpass'));
        $manager->persist($client);

        // --- 5. Enregistrement ---
        $manager->flush();
    }
}
