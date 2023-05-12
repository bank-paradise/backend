start docker:

```
docker-compose build app && docker-compose up -d && docker-compose exec app composer install && docker-compose exec app php artisan key:generate && docker-compose exec app php artisan migrate

```
