
simple admin generator for [Symfony][2] applications.
====================================================

Requirements
------------

  * PHP 7.1.3 or higher;
  * PDO-SQLite PHP extension enabled;
  * and the [usual Symfony application requirements][3].

How to install this project
---------------------------

  1. `git clone https://github.com/deeppanara/DomAdminPlatform`
  1. `cd DomAdminPlatform/`
  1. `composer install`
  1. `php bin/console server:run`
  1. Browse `http://127.0.0.1:8000/admin/`

The project comes with an SQLite sample database, but you can also create your
own database and load the fixtures data:

  1. Edit the `DATABASE_URL` env var in the `.env` file to use your database credentials.
  1. `php bin/console doctrine:database:create`
  1. `php bin/console doctrine:schema:create`
  1. `php bin/console doctrine:fixtures:load --append`

DomAdmin
========

EasyAdmin creates administration backends for your Symfony applications with
unprecedented simplicity.

<img src="https://raw.githubusercontent.com/EasyCorp/DomAdminBundle/master/doc/images/domadmin-promo.png" alt="Symfony Backends created with EasyAdmin" align="right" />

* [Installation][1]
* [Creating Your First Backend][2]
* [Documentation][3]

**Features**

  * **CRUD** operations on Doctrine entities (create, edit, list, delete).
  * Full-text **search**, **pagination** and column **sorting**.
  * Supports Symfony 4.1 or higher
  * Translated into tens of languages.
  * **Fast**, **simple** and **smart** where appropriate.

**Requirements**

  * Symfony 4.1 or higher applications.
  * Doctrine ORM entities (Doctrine ODM not supported).
  * Entities with composite keys or using inheritance are not supported.

If your application uses a Symfony version older than 4.1, check out the
[1.x version of this bundle](https://github.com/EasyCorp/DomAdminBundle/tree/1.x)
which is compatible with Symfony 2.x, 3.x and 4.x.

Demo Application
----------------

[easy-admin-demo](https://github.com/javiereguiluz/easy-admin-demo) is a complete
Symfony application created to showcase EasyAdmin features.

License
-------

This software is published under the [MIT License](LICENSE.md)

[1]: https://symfony.com/doc/current/bundles/DomAdminBundle/book/installation.html
[2]: https://symfony.com/doc/current/bundles/DomAdminBundle/book/your-first-backend.html
[3]: https://symfony.com/doc/current/bundles/DomAdminBundle
