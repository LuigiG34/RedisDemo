# RedisDemo
A Symfony project demonstrating the use of Redis for caching, message queues, and pub/sub, featuring a `Task` entity managed with Doctrine and populated with fixtures.

---

## 1) Requirements
1. Docker
2. Docker Compose
3. (Windows) WSL2

---

## 2) Installation / Run
1. Clone the Repository
   ```
   git clone https://github.com/LuigiG34/RedisDemo
   cd RedisDemo
   ```

2. Start Docker Containers
   ```
   docker compose up -d --build
   ```

3. Install PHP Dependencies
   ```
   docker compose exec app composer install
   ```

4. Create Database
   ```
   docker compose exec app php bin/console doctrine:database:create
   ```

5. Generate Database Migration
   ```
   docker compose exec app php bin/console make:migration
   ```

6. Apply Database Migration
   ```
   docker compose exec app php bin/console doctrine:migrations:migrate
   ```

7. Load DataFixtures
   ```
   docker compose exec app php bin/console doctrine:fixtures:load
   ```

8. Access the Web Application
   - Test the Redis cache `http://localhost:8000/tasks`
   - Dispatch the pending Tasks to Redis `http://localhost:8000/tasks/dispatch-all`

9. Handle & proccess tasks
   ```
   docker compose exec app php bin/console messenger:consume async_high async async_low -vv
   ```
*We have 1 worker that process from high → normal → low priority*

---

## About DTOs
DTOs are used instead of entities for stability, safety, security, and reduced serialization weight.

- **Serialization Weight**: Entities carry relations and Doctrine proxies, making them heavy to serialize. DTOs are lightweight and fast to convert to JSON or other formats.
- **Stability & Safety**: DTOs define explicit fields. If an entity gains new fields or associations, cached DTOs remain stable and respect the view contract, preventing accidental data leaks.
- **Security**: You control what’s exposed to the UI, avoiding the risk of revealing internal or sensitive fields.