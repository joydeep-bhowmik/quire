<?php
use function Quire\e;

$title = 'Not found';
require PAGES . '/_partials/header.php';
?>
<h1>404</h1>
<p><?= e($message) ?></p>
<?php require PAGES . '/_partials/footer.php';
