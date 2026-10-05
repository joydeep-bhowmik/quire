<?php
use function Quire\{name, e};

name('admin');

$title = 'Admin';
require PAGES . '/_partials/header.php';
?>
<h1>Admin</h1>
<p>Welcome, <?= e($request->attributes['user']) ?>. Only <code>admin</code> gets here.</p>
<?php require PAGES . '/_partials/footer.php';
