<?php use function Quire\{e, route, url}; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Quire') ?></title>
    <style>
        body { font: 16px/1.5 system-ui, sans-serif; max-width: 46rem; margin: 2rem auto; padding: 0 1rem; color: #222; }
        nav a { margin-right: 1rem; }
        code { background: #f3f3f3; padding: .1rem .3rem; border-radius: 3px; }
    </style>
</head>
<body>
<nav>
    <a href="<?= route('home') ?>">Home</a>
    <a href="<?= route('about') ?>">About</a>
    <a href="<?= route('users.index') ?>">Users</a>
    <a href="<?= route('team.index') ?>">Team</a>
    <a href="<?= route('blog') ?>">Blog</a>
    <a href="<?= route('admin') ?>">Admin</a>
    <a href="<?= route('login') ?>">Login</a>
    <a href="<?= url('/logout') ?>">Logout</a>
</nav>
<main>
