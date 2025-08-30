# RedisDemo

```
docker compose up -d --build

docker compose exec app php bin/console doctrine:database:create

docker compose exec app php bin/console make:migration

docker compose exec app php bin/console doctrine:migrations:migrate

docker compose exec app php bin/console doctrine:fixtures:load
 ```

---

DTO used instead of entities for stability, safety, security and serialization weight

- Serialization weight: Entities drag relations/proxies; DTOs are light and fast to serialize.
- Stability & safety: DTO fields are explicit; if your entity gains new fields/associations, cached DTOs still match the view contract.
- Security: You only expose what the UI needs (no accidental leakage of internal fields).