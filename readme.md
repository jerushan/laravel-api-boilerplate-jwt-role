## Laravel API Boilerplate (JWT Edition) & Role management for Laravel 5.8

Laravel API Boilerplate is a "starter kit" you can use to build your first API in seconds. As you can easily imagine, it is built on top of the awesome Laravel Framework. This version is built on Laravel 5.8!

It is built on top of three big guys:

* JWT-Auth - [tymondesigns/jwt-auth](https://github.com/tymondesigns/jwt-auth)
* Dingo API - [dingo/api](https://github.com/dingo/api)
* Laravel-CORS [barryvdh/laravel-cors](http://github.com/barryvdh/laravel-cors)
* Role Management [santigarcor/laratrust](https://github.com/santigarcor/laratrust)

## What does Laratrust support?

- Multiple user models.
- Multiple roles and permissions can be attached to users.
- Multiple permissions can be attached to roles.
- Roles and permissions verification.
- Roles and permissions caching.
- Events when roles and permissions are attached, detached or synced.
- Multiple roles and permissions can be attached to users within teams.
- Objects ownership verification.
- Multiple guards for the middleware.
- View full documentation (https://laratrust.santigarcor.me/api/5.1/).
- [Laravel gates and policies](http://laratrust.santigarcor.me/docs/5.0/troubleshooting.html).

## Installation

1. Clone the project or run `composer create-project jerushan/laravel-api-boilerplate-jwt-role-management myProject`;
2. run the `php artisan migrate` command to install the required tables.
3. run `php artisan db:seed` command to create developer account

## Usage

For each controller there's an already setup route in `routes/api.php` file:

* `POST api/auth/login`, to do the login and get your access token;
* `POST api/auth/refresh`, to refresh an existent access token by getting a new one;
* `POST api/auth/signup`, to create a new user into your application;
* `POST api/auth/recovery`, to recover your credentials;
* `POST api/auth/reset`, to reset your password after the recovery;
* `POST api/auth/logout`, to log out the user by invalidating the passed token;
* `GET api/auth/me`, to get current user data;

* `POST api/createRole`, to create a role;
* `POST api/viewRole`, to to view all roles;
* `POST api/updateRole`, to update a role;

* `POST api/assignRole`, to assign a role for a user;
* `POST api/detachRole`, to detach a role form a user;

### Separate File for Routes

All the API routes can be found in the `routes/api.php` file. This also follow the Laravel 5.5 convention.

### Secrets Generation

Every time you create a new project starting from this repository, the _php artisan jwt:generate_ command will be executed.

However, there are some extra options that I placed in a _config/boilerplate.php_ file:

* `sign_up.release_token`: set it to `true` if you want your app release the token right after the sign up process;
* `reset_password.release_token`: set it to `true` if you want your app release the token right after the password reset process;

There are also the validation rules for every action (login, sign up, recovery and reset). Feel free to customize it for your needs.

## Creating Endpoints

You can create endpoints in the same way you could to with using the single _dingo/api_ package. You can <a href="https://github.com/dingo/api/wiki/Creating-API-Endpoints" target="_blank">read its documentation</a> for details. After all, that's just a boilerplate! :)

However, I added some example routes to the `routes/api.php` file to give you immediately an idea.

## Cross Origin Resource Sharing

If you want to enable CORS for a specific route or routes group, you just have to use the _cors_ middleware on them.

Thanks to the _barryvdh/laravel-cors_ package, you can handle CORS easily. Just check <a href="https://github.com/barryvdh/laravel-cors" target="_blank">the docs at this page</a> for more info.

## Tests

If you want to contribute to this project, feel free to do it and open a PR. However, make sure you have tests for what you implement.

In order to run tests:

* be sure to have the PDO sqlite extension installed in your environment;
* run `php vendor/bin/phpunit`;
