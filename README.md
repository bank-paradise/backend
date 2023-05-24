start docker:

```
sudo docker-compose build app && docker-compose up -d && docker-compose exec app composer install && docker-compose exec app php artisan key:generate && docker-compose exec app php artisan migrate && docker-compose exec app php artisan websockets:serve && docker-compose exec app pm2 start /usr/local/bin/startup.sh
```
