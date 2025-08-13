# gan.events

## Installation

```
composer install
yarn install
yarn build
php bin/console make:migrations:migrate
```

## Messenger

```
php bin/console messenger:consume [-vv]
supervisorctl start messenger-consume:*
```
## Task schedules 

```
php bin/console app:schedules:prepare
```

## Fonctionnalités
- Création d'utilisateur avec choix du mot de passe à la première visite


