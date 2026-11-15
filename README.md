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

`RateLimitingPass` регистрируется в `App\Kernel::build()` приложения (не бандлом).

## Состав

- OptionalField, Validator (`OptionalNumeric`)
- Doctrine types (`MeasurementType`, `BigIntType`), `UpdatedAt` + listener
- Cipher, Redis, Lock, Json, Http, Metrics, Notifier, простой `Flusher`
- `TransactionSideEffectInterface` — opt-in для Filesystem cleanup после коммита
- Exception/HTTP/Domain базовые (классы исключений, не подписчики)
- `RateLimiter\Attribute\RateLimiting` (атрибут; listener и CompilerPass — в приложении)
- Entry Console (devops CLI)
- PlatformToken
- Platform Messenger: `Messages\Platform\IntegrationSystemEventMessage`, `Messenger\PlatformEnvelopeSerializer` (конверт `{type, integrationSystemCode, payload}`)

## Ответственность приложения (не бандла)

Бандл **не** регистрирует реакции на события. В каждом монолите должны быть:

- `src/Common/Dictionary/LogTag.php`
- `src/Common/EventSubscriber/` (Exception, Metrics, RateLimitter)
- `src/Common/DependencyInjection/Compiler/RateLimitingPass.php`
- регистрация Pass и опционального `McpToolDurationSubscriber` в `App\Kernel::build()`
- exclude `EventSubscriber/Metrics/McpToolDurationSubscriber.php` в `Common/Resource/config/services.yaml`

Не входит (остаётся в приложении): продуктовые `Messages/*`, Gateways, `*UnavailableHttpException`, `LogTag`, EventSubscriber, recipe-book AggregateRoot и т.п.

## Flusher + Filesystem

Если в приложении есть cleanup файлов после транзакции, реализуйте `IWD\SymfonyAppCommon\Service\Core\TransactionSideEffectInterface` (как бывший `FilesystemFileCleanup`) — `Flusher` подхватит сервис через autowire optional dependency.
