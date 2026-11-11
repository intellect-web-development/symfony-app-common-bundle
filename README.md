# symfony-app-common-bundle

Общее инфраструктурное ядро IWD Symfony-монолитов (бывший модуль `App\Common`).

**Package:** `intellect-web-development/symfony-app-common-bundle`  
**Namespace:** `IWD\SymfonyAppCommon\`

## Установка

```bash
composer require intellect-web-development/symfony-app-common-bundle
```

В `config/bundles.php`:

```php
IWD\SymfonyAppCommon\SymfonyAppCommonBundle::class => ['all' => true],
```

Шаблон Doctrine-миграций (`config/packages/doctrine_migrations.yaml`):

```yaml
doctrine_migrations:
    custom_template: '%kernel.project_dir%/vendor/intellect-web-development/symfony-app-common-bundle/src/Doctrine/Template/MigrationTemplate.dist'
```

`RateLimitingPass` регистрируется самим бандлом — ручная регистрация в `App\Kernel` не нужна.

## Состав

- OptionalField, Validator (`OptionalNumeric`)
- Doctrine types (`MeasurementType`, `BigIntType`), `UpdatedAt` + listener
- Cipher, Redis, Lock, Json, Http, Metrics, Notifier, простой `Flusher`
- `TransactionSideEffectInterface` — opt-in для Filesystem cleanup после коммита
- Exception/HTTP/Domain базовые, EventSubscriber (exception, metrics, rate limit)
- Entry Console (devops CLI)
- PlatformToken
- `McpToolDurationSubscriber` — только если установлен MCP (`symfony/mcp-bundle`)

Не входит (остаётся в приложении): продуктовые `Messages/*`, Gateways, `*UnavailableHttpException`, расширенный `LogTag`, recipe-book AggregateRoot и т.п.

## Flusher + Filesystem

Если в приложении есть cleanup файлов после транзакции, реализуйте `IWD\SymfonyAppCommon\Service\Core\TransactionSideEffectInterface` (как бывший `FilesystemFileCleanup`) — `Flusher` подхватит сервис через autowire optional dependency.
