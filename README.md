# Les Traits de Vaïa – Symfony 7.2 E-commerce Project

This repository contains a Symfony 7.2 project named les-traits-de-vaia-app, a small e-commerce web application developed to simulate an online boutique experience.
It is designed as a practical learning project to master Symfony 7, Doctrine ORM, authentication, mailing, payment integration, and deployment automation.

## Objectives

- Learn to build a complete e-commerce website using Symfony 7.2.
- Manage product listings, a shopping cart, and customer orders.
- Implement email verification for account creation and order confirmation.
- Integrate Stripe for secure payment processing.
- Use Doctrine ORM 3 for database management.
- Automate deployment to Hostinger using GitHub Actions.
- Practice environment configuration and real hosting constraints.

## Project Structure


```php
les-traits-de-vaia/
├── assets/              # Frontend assets (CSS, JS, images)
├── migrations/          # Doctrine migration files
├── public/              # Public web root (entry point index.php)
├── src/                 # Symfony source code (controllers, entities, services)
├── templates/           # Twig templates (HTML views)
├── .env                 # Environment variables (local)
├── composer.json        # PHP dependencies
├── symfony.lock         # Symfony configuration lock file
└── README.md
```

## Usage (local environment)

1. Clone the repository :
    ```bash
    git clone https://github.com/MaelleN95/les-traits-de-vaia.git
    cd les-traits-de-vaia
    ```

2. Build and start the Docker containers :

   ```bash
   docker compose up -d --build
   ```

3. (Optional) Access the PHP container to run Symfony CLI or Composer commands :
   
   ```bash
   docker exec -it biblios-php bash
   ```

4. Install dependencies :
   ```bash
   composer install
   ```

6. Configure your .env.local with your own database and mailer settings :
  ```bash
  DATABASE_URL="mysql://symfony:symfony@les-traits-de-vaia-db:3306/les-traits-de-vaia?serverVersion=10.11.2-MariaDB&charset=utf8mb4"
  MAILER_DSN=YOUR_MAILER_DSN
  STRIPE_PUBLIC_KEY=YOUR_STRIPE_PUBLIC_KEY
  STRIPE_SECRET_KEY=YOUR_STRIPE_SECRET_KEY
  MESSENGER_TRANSPORT_DSN=sync://
  ```

8. Create and initialize the database:
    ```bash
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate
    php bin/console doctrine:fixtures:load
    ```
  
10. Access the Symfony app at `http://localhost:8080`

12. Access phpMyAdmin at `http://localhost:8081`
   - Username: `symfony`
   - Password: `symfony`

12. To stop the containers :

```bash
docker compose down
```

## Deployment (Hostinger + GitHub Actions)

Deployment is fully automated with GitHub Actions:
On each push to the main branch, the workflow:

- Connects to Hostinger via SSH.
- Updates the codebase.
- Clears and warms up the Symfony cache.
- Applies Doctrine migrations.
- Uses environment variables from GitHub Secrets (DATABASE_URL, MAILER_DSN, STRIPE_KEYS, etc.).

This allows seamless, reproducible deployments directly from GitHub.

# Features

- Product catalog with images and prices (managed in assets/images).
- Shopping cart with session persistence.
- User registration and authentication with email verification.
- Order creation and payment processing via Stripe Checkout.
- Automatic confirmation email after successful payment.
- Admin management via Doctrine (customizable).

## Notes

- Prices are stored in cents in the database for precision and converted in display templates.
- The mail system uses Symfony Mailer with templated HTML emails.
- All configuration is compatible with PHP 8.2 and MariaDB 11.8.
- The project was designed to simulate a real boutique, not just a technical demo.
