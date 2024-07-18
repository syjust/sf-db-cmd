# Symfony Db Command

## Description

A copy / adaptation of laravel/framework [DbCommand](https://github.com/laravel/framework/blob/11.x/src/Illuminate/Database/Console/DbCommand.php)
* used to easily access your DB with a command line interface,
* based on symfony/console, symfony/process & doctrine/orm libraries.

## Installation

First, add the dependency in your project.

```shell
composer require syjust/sf-db-cmd
```

Then add the following line in your `config/bundles.php` file.

```php
// ...
    Syjust\SfDbCmd\DbCommandBundle::class => ['all' => true],
// ...
```

## Usage

```shell
# connecting interactively into the database
php bin/console db
```

## Limitations

This command was only tested with `pdo_mysql` & `pdo_sqlite` at this time in interactive mode at this time.


## Contributions

* First, please use [conventional commit](https://www.conventionalcommits.org/en/v1.0.0-beta.4/),
* then, submit your Pull Request onto the [GitHub Repo](https://github.com/syjust/sf-db-cmd.git),
* and we will see 😋.

## Known issues

If you get the following message:

```shell
TTY mode requires /dev/tty to be read/writable.
```

1. Ensure `/dev/tty` is writable in shell mode (`echo foobar >> /dev/tty`).
2. Ensure you are on unix based OS (`/` is the directory separator).
3. Ensure PHP is able to write in `/dev/tty` (`<?php is_writable('/dev/tty') ?>`)
   If the latest test fails: add ':/dev/tty' to the `open_basedir` config in your `php.ini`.
