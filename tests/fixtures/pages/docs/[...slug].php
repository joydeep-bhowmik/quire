<?php
use function Quire\e;

// [...slug] matches one or more segments: /docs/a, /docs/a/b/c ...
$title = 'Docs: ' . implode(' / ', $slug);
require PAGES . '/_partials/header.php';
?>
<h1>Docs</h1>
<p>You are reading: <code><?= e(implode(' → ', $slug)) ?></code></p>
<?php require PAGES . '/_partials/footer.php';
