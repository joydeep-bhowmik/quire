<?php
use function Quire\{name, middleware, methods, abort, e, redirect, route};

name('users.edit');
middleware('auth');
methods('GET', 'POST');

$users = require PAGES . '/_data/users.php';
$user = $users[$id] ?? abort(404);

if ($request->isMethod('POST')) {
    // Pretend we saved $request->input('name') somewhere.
    return redirect(route('users.show', ['id' => $id, 'saved' => 1]));
}

$title = "Edit {$user['name']}";
require PAGES . '/_partials/header.php';
?>
<h1>Edit <?= e($user['name']) ?></h1>
<p>Logged in as <?= e($request->attributes['user']) ?>.</p>
<form method="post">
    <input name="name" value="<?= e($user['name']) ?>">
    <button>Save</button>
</form>
<?php require PAGES . '/_partials/footer.php';
