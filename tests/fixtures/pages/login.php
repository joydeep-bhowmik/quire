<?php
use function Quire\{name, methods, e, redirect, url};

name('login');
methods('GET', 'POST');

if ($request->isMethod('POST')) {
    $user = trim((string) $request->input('user'));
    $next = (string) $request->input('next', '');

    // Only follow local redirects.
    if (!str_starts_with($next, '/') || str_starts_with($next, '//')) {
        $next = url('/');
    }

    return redirect($next)->cookie('user', $user);
}

$title = 'Login';
require PAGES . '/_partials/header.php';
?>
<h1>Login</h1>
<p>Any name works. Log in as <code>admin</code> to see the admin page.</p>
<form method="post">
    <input type="hidden" name="next" value="<?= e($request->query['next'] ?? '') ?>">
    <input name="user" placeholder="username" required>
    <button>Log in</button>
</form>
<?php require PAGES . '/_partials/footer.php';
