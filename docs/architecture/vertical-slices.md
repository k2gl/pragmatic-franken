# Vertical Slices Architecture — Краткое руководство

## Принципы

| Принцип | Описание |
|---------|----------|
| **Slices** | Код группируется по бизнес-фичам, не по техническим слоям |
| **CQRS** | Разделение Command (запись) и Query (чтение) |
| **Low Coupling** | Минимум зависимостей между срезами |
| **High Cohesion** | Всё для фичи — в одной папке |

## Структура проекта

```
src/
├── Shared/                      # Инфраструктура (исключения, сервисы)
│   ├── Exception/
│   └── Services/
│
├── User/                       # Модуль
│   ├── Entity/
│   ├── Enums/
│   ├── UseCase/
│   │   └── {FeatureName}/
│   │       ├── Input/
│   │       ├── Output/
│   │       ├── Handler/
│   │       ├── Action/
│   │       └── Client/
│
├── Board/                      # Модуль
│   └── ...
│
├── Task/                       # Модуль
│   └── ...
│
└── Health/                     # Техническая фича
    ├── Services/
    └── UseCase/
```

## Что выносить в Shared?

### Выносим (Always)
- Инфраструктурные обёртки (Helpers)
- Базовые исключения

### НЕ выносим (Never)
- Бизнес-логику
- Похожий код в разных срезах (WET > DRY)

## Naming Conventions

| Тип | Пример |
|-----|--------|
| UseCase | `CreateUser/` |
| Input/Command | `CreateUserMessage.php` |
| Output/Response | `CreateUserResponse.php` |
| Handler | `CreateUserHandler.php` |
| Action/Controller | `CreateUserAction.php` |

## Конфигурация

### services.yaml
```yaml
App\:
    resource: '../src/'
    exclude:
        - '../src/Shared/{Exception,Services}/'
        - '../src/*/{Entity,Enums}/'

App\**\*Controller:
    tags: ['controller.service_arguments']

App\**\*Handler:
    tags: ['messenger.message_handler']
```

### routes.yaml
```yaml
controllers:
    resource: ../src/
    type: attribute
```

## Ключевые инструменты

1. **Symfony Messenger** — для Command/Query Bus
2. **Deptrac** — для контроля границ модулей
3. **PHPStan** — статический анализ

## Преимущества для ИИ-агентов

- **Локализация контекста**: одна папка = один запрос к ИИ
- **Простота тестирования**: один вход → один выход
- **Безопасные изменения**: изменение одного среза не ломает другие

## Ссылки

- [ADR-007: Vertical Slices vs Classic DDD](../adr/adr-007-vertical-slices-vs-classic-ddd.md)
- [Symfony Messenger](https://symfony.com/doc/current/messenger.html)
- [Deptrac](https://github.com/qossmic/deptrac)
