<?php
use function Quire\{name, e, route};

name('blog');

// [[...slug]] also matches zero segments, so /blog lands here too.
$title = 'Blog';
require PAGES . '/_partials/header.php';
?>
<?php if ($slug === []): ?>
    <h1>Blog</h1>
    <p><a href="<?= route('blog', ['slug' => ['2026', 'hello-world']]) ?>">Hello world</a></p>
<?php else: ?>
    <h1>Post: <?= e(end($slug)) ?></h1>
    <p>Path segments: <code><?= e(json_encode($slug)) ?></code></p>
<?php endif ?>
<?php require PAGES . '/_partials/footer.php';
