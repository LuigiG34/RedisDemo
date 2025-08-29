# RedisDemo

docker compose up -d --build
docker compose exec app php bin/console doctrine:database:create
docker compose exec app php bin/console make:migration
docker compose exec app php bin/console doctrine:migrations:migrate
docker compose exec app php bin/console doctrine:fixtures:load
 