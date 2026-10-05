<?php
use function Quire\e;

$title = "Error {$status}";
require PAGES . '/_partials/header.php';
?>
<h1><?= e($status) ?></h1>
<p><?= e($message) ?></p>
<?php require PAGES . '/_partials/footer.php';
