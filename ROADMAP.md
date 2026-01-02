# Roadmap BowPHP Framework

> Document évolutif basé sur l'analyse du code source (branche 5.x) et le manifeste du projet.
> Dernière mise à jour : Janvier 2026

---

## État Actuel du Framework

### Modules Existants (Analyse du `/src`)

| Module                 | Statut    | Description                                    |
| ---------------------- | --------- | ---------------------------------------------- |
| **Application**        | ✅ Stable | Bootstrap, exception handling, kernel          |
| **Auth**               | ✅ Stable | Guards (Session, JWT), Authentication          |
| **Cache**              | ✅ Stable | Adapters: Database, Filesystem, Redis          |
| **Configuration**      | ✅ Stable | Loader, Env, Logger configuration              |
| **Console**            | ✅ Stable | 26 commandes, générateurs, stubs               |
| **Container**          | ✅ Stable | DI container, middleware dispatcher            |
| **Database/Barry ORM** | ✅ Stable | MySQL, PostgreSQL, SQLite + Relations          |
| **Event**              | ✅ Stable | Event dispatcher, listeners, queue integration |
| **Http**               | ✅ Stable | Request, Response, Client, Exceptions          |
| **Mail**               | ✅ Stable | SMTP, Native adapters, queue support           |
| **Messaging**          | ✅ Stable | SMS, Mail, Slack, Telegram, Database channels  |
| **Middleware**         | ✅ Stable | Auth, CSRF, Base middleware                    |
| **Queue**              | ✅ Stable | Beanstalkd, Database, SQS, Sync adapters       |
| **Router**             | ✅ Stable | REST methods, prefixes, middlewares, resources |
| **Security**           | ✅ Stable | Crypto, Hash, Sanitize, Tokenize               |
| **Session**            | ✅ Stable | Cookie, File, Database, Redis adapters         |
| **Storage**            | ✅ Stable | Disk, FTP, S3 services                         |
| **Support**            | ✅ Stable | Helpers, Collection, Str, Log, Env             |
| **Testing**            | ✅ Stable | TestCase, Assertions, KernelTesting            |
| **Translate**          | ✅ Stable | i18n support                                   |
| **Validation**         | ✅ Stable | Règles de validation, messages custom          |
| **View**               | ✅ Stable | Tintin (default), Twig support                 |

### Dépendances Actuelles

**Requises :**

-   PHP ^8.1
-   bowphp/tintin ^3.0 (template engine)
-   filp/whoops ^2.1 (error handling)
-   nesbot/carbon 3.8.4 (dates)
-   fakerphp/faker ^1.20 (testing data)
-   ramsey/uuid ^4.7 (UUIDs)

**Dev/Suggérées :**

-   pda/pheanstalk ^5.0 (Beanstalkd)
-   aws/aws-sdk-php ^3.87 (S3)
-   bowphp/policier ^3.0 (JWT)
-   predis/predis ^2.1 (Redis)
-   twilio/sdk ^8.3 (SMS)
-   bowphp/slack-webhook ^1.0 (Slack)

---

## 🔴 NOW — 0 à 3 mois (Stabilisation & Consolidation)

### Tests et CI/CD

| Tâche                                               | Statut     | Priorité | Notes                                                  |
| --------------------------------------------------- | ---------- | -------- | ------------------------------------------------------ |
| Séparer les tests unitaires des tests d'intégration | ⏳ À faire | Haute    | Les tests DB/FTP/S3 nécessitent des services externes  |
| Ajouter `@group` PHPUnit pour isoler les tests      | ⏳ À faire | Haute    | `@group unit`, `@group integration`, `@group database` |
| Configurer GitHub Actions avec services Docker      | ⏳ À faire | Haute    | MySQL, PostgreSQL, Redis pour CI                       |
| Augmenter couverture tests unitaires > 80%          | ⏳ À faire | Moyenne  | Focus sur modules critiques                            |
| Intégrer PHPStan niveau 5+                          | ⏳ À faire | Moyenne  | Actuellement niveau 0.12.87                            |

### Corrections de Code

| Tâche                                           | Statut     | Priorité | Notes                                 |
| ----------------------------------------------- | ---------- | -------- | ------------------------------------- |
| Fixer les tests SQLite qui échouent (isolation) | ⏳ À faire | Haute    | Problème de state partagé entre tests |
| Uniformiser les signatures de méthodes          | ✅ Fait    | -        | PHP 8.1+ nullable types               |
| Fixer le cast `(double)` → `(float)`            | ✅ Fait    | -        | Model.php ligne 924                   |
| Gérer `array_key_exists` avec clé null          | ✅ Fait    | -        | Console.php                           |
| Créer le répertoire de test si inexistant       | ✅ Fait    | -        | CustomCommand.php                     |

### Documentation

| Tâche                                        | Statut     | Priorité | Notes                      |
| -------------------------------------------- | ---------- | -------- | -------------------------- |
| Mettre à jour README avec exemples API-first | ⏳ À faire | Moyenne  | Aligner avec le manifeste  |
| Documenter les configurations requises       | ⏳ À faire | Moyenne  | Chaque module              |
| Créer guide de contribution détaillé         | ⏳ À faire | Basse    | Au-delà du CONTRIBUTING.md |

---

## 🟠 NEXT — 3 à 6 mois (Nouvelles Fonctionnalités)

### Queue - Adapter Redis

| Tâche                                    | Statut     | Priorité | Notes                          |
| ---------------------------------------- | ---------- | -------- | ------------------------------ |
| Créer `RedisAdapter` pour Queue          | ⏳ À faire | Haute    | predis/predis déjà en dev-deps |
| Implémenter delayed jobs avec Redis ZADD | ⏳ À faire | Haute    |                                |
| Ajouter monitoring des queues via CLI    | ⏳ À faire | Moyenne  | `bow queue:status`             |

### Router - Attributs PHP 8

| Tâche                                                  | Statut     | Priorité | Notes                 |
| ------------------------------------------------------ | ---------- | -------- | --------------------- |
| Créer namespace `Bow\Router\Attributes`                | ⏳ À faire | Haute    |                       |
| Implémenter `#[Controller]`                            | ⏳ À faire | Haute    | prefix, middleware    |
| Implémenter `#[Get]`, `#[Post]`, `#[Put]`, `#[Delete]` | ⏳ À faire | Haute    |                       |
| Ajouter `$router->register(Controller::class)`         | ⏳ À faire | Haute    | Auto-discovery routes |

### Cache - Adapter Memcached

| Tâche                                         | Statut     | Priorité | Notes |
| --------------------------------------------- | ---------- | -------- | ----- |
| Créer `MemcachedAdapter`                      | ⏳ À faire | Moyenne  |       |
| Améliorer résilience Redis (reconnexion auto) | ⏳ À faire | Moyenne  |       |

### Messaging - Push Notifications

| Tâche                                | Statut     | Priorité | Notes         |
| ------------------------------------ | ---------- | -------- | ------------- |
| Créer `FcmChannelAdapter` (Firebase) | ⏳ À faire | Moyenne  |               |
| Créer `ApnsChannelAdapter` (Apple)   | ⏳ À faire | Moyenne  |               |
| Améliorer `TelegramChannelAdapter`   | ⏳ À faire | Basse    | Déjà existant |
| Améliorer `SlackChannelAdapter`      | ⏳ À faire | Basse    | Déjà existant |

### Database

| Tâche                                     | Statut     | Priorité | Notes                        |
| ----------------------------------------- | ---------- | -------- | ---------------------------- |
| Ajouter support SQL Server                | ⏳ À faire | Moyenne  |                              |
| Créer adapter Array/FileWriter pour tests | ⏳ À faire | Moyenne  | Évite dépendance DB en tests |

---

## 🟢 LATER — 6 à 12 mois (Vision Long Terme)

### Performance et Modernisation

| Tâche                                        | Statut     | Priorité | Notes                      |
| -------------------------------------------- | ---------- | -------- | -------------------------- |
| Support Swoole/FrankenPHP                    | ⏳ À faire | Moyenne  | Serveurs non-bloquants     |
| Images Docker officielles                    | ⏳ À faire | Moyenne  | Optimisées pour production |
| Support serverless (Lambda, Cloud Functions) | ⏳ À faire | Basse    | HTTP Handler adapté        |

### Écosystème

| Tâche                                            | Statut     | Priorité | Notes                |
| ------------------------------------------------ | ---------- | -------- | -------------------- |
| Package `bowphp/payment`                         | ⏳ À faire | Haute    | Mobile money Afrique |
| Package `bowphp/logviewer` ou `bowphp/telescope` | ⏳ À faire | Moyenne  | Observabilité        |
| Adapter laravel-notify pour Bow                  | ⏳ À faire | Basse    | UI notifications     |

### Observabilité

| Tâche                          | Statut     | Priorité | Notes                           |
| ------------------------------ | ---------- | -------- | ------------------------------- |
| Module OpenTelemetry optionnel | ⏳ À faire | Moyenne  | Tracing requests, jobs, queries |
| Intégration Prometheus/Grafana | ⏳ À faire | Basse    | Métriques production            |

---

## Légende

-   ✅ **Fait** : Tâche complétée
-   ⏳ **À faire** : Tâche planifiée
-   🔄 **En cours** : Travail en progression
-   ❌ **Annulé** : Tâche abandonnée

---

## Comment Contribuer

1. Choisir une tâche de la section **NOW** (priorité haute)
2. Ouvrir une issue pour discuter de l'implémentation
3. Créer une branche `feature/nom-de-la-tache`
4. Suivre les conventions du projet (voir CONTRIBUTING.md)
5. Soumettre une PR avec tests

---

## Notes Importantes

### Concernant les Tests

Les erreurs actuelles lors de `composer test` sont principalement dues à :

1. **Services externes non disponibles** (pas des bugs du framework) :

    - MySQL : Connection refused / Access denied
    - PostgreSQL : Connection refused
    - FTP : Connection refused
    - S3 : Invalid endpoint
    - Beanstalkd : Connection refused

2. **Isolation des tests SQLite** : Certains tests partagent l'état de la base, causant des échecs intermittents.

**Solution recommandée** : Séparer les tests en groupes (`@group unit`, `@group integration`) et configurer CI avec Docker Compose pour les tests d'intégration.

### Philosophie du Projet

Toute contribution doit respecter le manifeste :

-   **Simplicité** > Sophistication
-   **Lisibilité** > Concision extrême
-   **API-first** : Priorité aux backends JSON
-   **Performance** : Bootstrap minimal, réponse rapide
-   **Contrôle** : Le développeur garde le contrôle de son architecture
