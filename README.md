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

### About DTOs
DTOs are used instead of entities for stability, safety, security, and reduced serialization weight.

- **Serialization Weight**: Entities carry relations and Doctrine proxies, making them heavy to serialize. DTOs are lightweight and fast to convert to JSON or other formats.
- **Stability & Safety**: DTOs define explicit fields. If an entity gains new fields or associations, cached DTOs remain stable and respect the view contract, preventing accidental data leaks.
- **Security**: You control what’s exposed to the UI, avoiding the risk of revealing internal or sensitive fields.

---

### Redis jargon

Redis = my fast toolbox for caching + queues + pub/sub. Notes to self, tied to **RedisDemo**:

* **Key/Value**
  Plain named values. Example keys I use: `task:list`, `task:{id}`.
  Set/get like: `SET task:1 "Draw a car"` → `GET task:1`.

* **TTL (Time-to-Live)**
  Auto-expiry for keys so cache stays fresh.
  Example: `EXPIRE task:list 3600` (my task list cache lives 1h).

* **Streams**
  Append-only logs that act as durable queues. I have three:
  `task_high`, `task_default`, `task_low` (mapped to Messenger transports `async_high`, `async`, `async_low`).
  Messages = `TaskProcessMessage`.

* **Queues (Messenger)**
  “Where” messages wait. I route by **priority** (from DB) to the right transport.
  Workers read them with:
  `php bin/console messenger:consume async_high async async_low -vv`.

* **Pub/Sub** *(optional demo)*
  Fire-and-forget broadcasts. I publish status updates on `task_updates`; a console subscriber can listen.
  *(Not durable; if no one listens, the message is lost.)*

* **Consumer Group**
  A named team of workers for one stream (default group name: `symfony`).
  Redis spreads messages across the group; each message goes to one consumer.

* **Consumer**
  One worker inside the group (I give each a unique name, e.g. `worker_high`, `worker_low`).
  Start like:
  `MESSENGER_CONSUMER_NAME=worker1 php bin/console messenger:consume async -vv`.

* **ACK**
  “Done!” signal. After my handler finishes, Messenger sends `XACK` so Redis removes the message from the pending list.

* **Pending Entries (PEL)**
  Messages taken by a consumer but not yet ACKed (e.g., worker crashed).
  I can reclaim them so they’re not stuck forever.

* **XADD**
  Add an entry to a stream. Conceptually:
  `XADD task_default * type TaskProcessMessage task_id 42`.

* **XREADGROUP**
  Group-aware read: assigns each entry to exactly one consumer in the group.

* **XACK**
  Confirm processing:
  `XACK task_default symfony <entry-id>` (Messenger does this for me).

* **XAUTOCLAIM / XCLAIM**
  Take over stale pending entries from a dead consumer so work continues.

* **MAXLEN**
  Keep streams bounded:
  `XADD task_default MAXLEN ~ 10000 * …` to trim old entries and avoid unbounded growth.
