
# Order Management System

Un sistema di gestione degli ordini costruito con Laravel per monitorare e gestire gli ordini degli utenti e l'inventario dei prodotti.

## Technologies Used

- PHP 8.x
- Laravel Framework
- MySQL Database
- Docker for containerization

## Requirements

- Docker and Docker Compose
- PHP 8.0 or higher
- Composer

## Getting Started

### Installation

1. Clone the repository:
   ```bash
   git clone <repository-url>
   ```

2. Copy the example environment file:
   ```bash
   cp .env.example .env
   ```

3. Configure your database settings in the `.env` file:
   ```
   DB_CONNECTION=mysql
   DB_HOST=db
   DB_PORT=3306
   DB_DATABASE=laravel
   DB_USERNAME=laravel
   DB_PASSWORD=secret
   ```

4. Start the Docker containers:
   ```bash
   docker-compose up -d
   ```

5. Install dependencies:
   ```bash
   docker-compose exec app composer install
   ```

6. Generate application key:
   ```bash
   docker-compose exec app php artisan key:generate
   ```

7. Run migrations to create the database schema:
    ```bash
    docker-compose exec app php artisan migrate
    ```

8. Seed the database with initial data:
    ```bash
    docker-compose exec app php artisan db:seed
    ```

9. Cache the configuration and routes for better performance:
    ```bash
    docker-compose exec app php artisan config:cache
    docker-compose exec app php artisan route:cache
    ```

10. Set proper permissions for storage and cache directories:
    ```bash
    docker-compose exec app chmod -R 777 storage bootstrap/cache
    ```


## API Endpoints

### Orders
- `GET /api/orders` - Get all orders with optional filters
- `GET /api/orders/{id}` - Get a specific order
- `POST /api/orders` - Create a new order
- `PUT /api/orders/{id}` - Update an existing order
- `DELETE /api/orders/{id}` - Delete an order

### Products
- `GET /api/products` - Get all products
- `GET /api/products/{id}` - Get a specific product
- `POST /api/products` - Create a new product
- `PUT /api/products/{id}` - Update an existing product
- `DELETE /api/products/{id}` - Delete a product


## Running Tests

To run all tests:

   ```bash
    docker-compose exec app php artisan test
   ```

### Test Coverage

To generate a test coverage report:

1. Ensure Xdebug is installed in your Docker environment:
   ```bash
   docker-compose exec app pecl install xdebug
   docker-compose exec app docker-php-ext-enable xdebug
   ```

2. Configure Xdebug in your php.ini:
   ```bash
   docker-compose exec app bash -c "echo 'xdebug.mode=coverage' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini"
   ```

3. Run tests with coverage report:
   ```bash
   docker-compose exec app php artisan test --coverage
   ```

   Alternatively, use PHPUnit directly:
   ```bash
   docker-compose exec app ./vendor/bin/phpunit --coverage-html tests/coverage
   ```

4. View the HTML coverage report in the `tests/coverage` directory.
