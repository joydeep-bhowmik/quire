# Quire example app

A small site built with [Quire](https://github.com/joydeep-bhowmik/quire): Blade pages, a shared layout,
error pages and Tailwind CSS. Copy this folder anywhere to start your own app.

```bash
composer install     # installs Quire and Blade
composer serve       # http://localhost:8000
```

The built CSS is included. To change styles:

```bash
npm install
npm run dev          # rebuilds public/css/app.css as you edit pages
npm run build        # minified build
```

```
├── app.css               Tailwind entry
├── composer.json         Quire + Blade, autoloads helpers.php
├── helpers.php           asset(), users(), user(), initials(), is_current()
├── data/users.php        fake data
├── public/index.php      front controller
└── pages/
    ├── _layouts/         layout
    ├── _components/      <x-avatar>, <x-error-page>, <x-error-action>
    ├── _errors/          400, 401, 403, 404, 405, 419, 429, 500, 503 + error.blade.php
    ├── index.blade.php   /
    ├── about.blade.php   /about
    └── users/
        ├── index.blade.php   /users
        └── [id].blade.php    /users/{id}
```

Every file in `pages/` is a route. Files and folders starting with `_` aren't. See the
[Quire README](https://github.com/joydeep-bhowmik/quire#readme) for middleware, named routes and more.
