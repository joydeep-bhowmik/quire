<?php

declare(strict_types=1);

use Quire\Quire;
use Quire\Request;
use Quire\Response;

use function Quire\route;

/** @var Quire $quire */
$quire = require __DIR__ . '/fixtures/app.php';

$passed = 0;
$failed = 0;

function check(string $label, bool $ok, string $detail = ''): void
{
    global $passed, $failed;

    $ok ? $passed++ : $failed++;
    echo ($ok ? "  \u{2713} " : "  \u{2717} ") . $label . ($ok || $detail === '' ? '' : "  ({$detail})") . "\n";
}

function get(string $uri, array $cookies = [], string $method = 'GET', array $body = []): Response
{
    global $quire;

    return $quire->handle(Request::create($method, $uri, $body, [], $cookies));
}

echo "Routing\n";
check('/ -> index.blade.php', str_contains(get('/')->body, '<h1>Quire</h1>'));
check('/about -> about.blade.php', str_contains(get('/about')->body, '<h1>About</h1>'));
check('/about/ trailing slash', get('/about/')->status === 200);
check('/users -> users/index.blade.php', str_contains(get('/users')->body, 'Ada Lovelace'));
check('/users/2 -> users/[id].blade.php', str_contains(get('/users/2')->body, '<h1>Alan Turing</h1>'));
check('abort(404) inside page', get('/users/99')->status === 404 && str_contains(get('/users/99')->body, 'No user with id 99'));
check('/docs/a/b/c catch-all', str_contains(get('/docs/a/b/c')->body, 'a → b → c'));
check('/docs alone is 404 (catch-all needs 1+)', get('/docs')->status === 404);
check('/blog optional catch-all (empty)', str_contains(get('/blog')->body, '<h1>Blog</h1>'));
check('/blog/2026/hi optional catch-all', str_contains(get('/blog/2026/hi')->body, 'Post: hi'));
check('unknown path -> _errors/404.php', get('/nope')->status === 404 && str_contains(get('/nope')->body, '<h1>404</h1>'));
check('other statuses -> _errors/error.php', str_contains(get('/about', method: 'POST')->body, '<title>Error 405</title>'));
check('_errors/ is not routable', get('/_errors/404')->status === 404 && get('/_errors/error')->status === 404);
check('_partials are not routable', get('/_partials/header')->status === 404);
check('_middleware.php is not routable', get('/admin/_middleware')->status === 404);
check('url-encoded segment decoded', str_contains(get('/docs/hello%20world')->body, 'hello world'));
check('array return -> JSON', get('/api/time')->getHeader('Content-Type') === 'application/json');

echo "\nMethods\n";
check('POST to GET-only page -> 405', get('/about', method: 'POST')->status === 405);
check('405 sends Allow header', get('/about', method: 'POST')->getHeader('Allow') === 'GET');
check('HEAD allowed on GET page', get('/about', method: 'HEAD')->status === 200);
check('methods() allows POST', get('/login', method: 'POST', body: ['user' => 'bob', 'next' => '/users'])->status === 302);

echo "\nMiddleware\n";
check('global pattern middleware (after)', get('/')->getHeader('X-Response-Time') !== null);
$edit = get('/users/1/edit');
check('inline middleware redirects guest', $edit->status === 302 && $edit->getHeader('Location') === '/login?next=%2Fusers%2F1%2Fedit');
check('inline middleware passes user', str_contains(get('/users/1/edit', ['user' => 'bob'])->body, 'Logged in as bob'));
check('POST through middleware + page redirect', get('/users/1/edit', ['user' => 'bob'], 'POST')->getHeader('Location') === '/users/1?saved=1');
check('_middleware.php: guest redirected', get('/admin')->status === 302);
check('_middleware.php: role:admin denies bob (403)', get('/admin', ['user' => 'bob'])->status === 403);
check('_middleware.php: admin allowed', str_contains(get('/admin', ['user' => 'admin'])->body, 'Welcome, admin'));
$login = get('/login', method: 'POST', body: ['user' => 'admin', 'next' => '//evil.com']);
check('login sets cookie + blocks open redirect', $login->cookies['user'][0] === 'admin' && $login->getHeader('Location') === '/');

echo "\nNamed routes\n";
check("route('home')", route('home') === '/');
check("route('users.show', id)", route('users.show', ['id' => 7]) === '/users/7');
check('extra params -> query string', route('users.show', ['id' => 7, 'tab' => 'posts']) === '/users/7?tab=posts');
check('optional catch-all omitted', route('blog') === '/blog');
check('catch-all from array', route('blog', ['slug' => ['2026', 'a b']]) === '/blog/2026/a%20b');
try {
    route('users.show');
    check('missing param throws', false);
} catch (InvalidArgumentException) {
    check('missing param throws', true);
}

echo "\nBase path + mount prefix\n";
$sub = new Quire();
$sub->base('/app')->extension('.blade.php', $blade)->path(__DIR__ . '/fixtures/pages')->uri('/site');
check('/app/site/login matches', $sub->handle(Request::create('GET', '/app/site/login'))->status === 200);
check('/login without prefix is 404', $sub->handle(Request::create('GET', '/login'))->status === 404);
check('url() includes base + prefix', $sub->url('users.show', ['id' => 1]) === '/app/site/users/1');

echo "\nBlade\n";
{
    $home = get('/');
    check('home, about, users share the layout', array_reduce(
        ['/', '/about', '/users', '/users/1'],
        fn (bool $ok, string $uri) => $ok && str_contains(get($uri)->body, '<nav>') && str_contains(get($uri)->body, '<footer>'),
        true,
    ));
    check('@section title -> layout <title>', str_contains(get('/about')->body, '<title>About · Quire</title>'));
    check('layout marks current nav link', str_contains($home->body, 'aria-current="page">Home</a>'));
    check('layout shows serving file', str_contains(get('/users/1')->body, 'pages' . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . '[id].blade.php'));
    check('?saved flash on user page', str_contains(get('/users/1?saved=1')->body, 'class="flash"'));
    check('plain PHP page (edit.php) next to Blade pages', str_contains(get('/users/1/edit', ['user' => 'bob'])->body, 'Logged in as bob'));

    $plain = new Quire();
    $plain->path(__DIR__ . '/fixtures/pages');
    $plainFiles = array_map(fn ($r) => basename($r->file), $plain->routes());
    check('without renderer, .blade.php pages are not routed', $plainFiles !== [] && preg_grep('/\.blade\.php$/', $plainFiles) === []);

    $team = get('/team');
    check('index.blade.php + @extends layout', $team->status === 200 && str_contains($team->body, '<h1>Team</h1>') && str_contains($team->body, '<nav>'));
    check('anonymous <x-card> component', substr_count($team->body, 'class="card"') === 3);
    check('@php metadata block: name()', $quire->url('team.index') === '/team');
    check('[member].blade.php param', str_contains(get('/team/grace')->body, '<h1>Grace Hopper</h1>'));
    check('$request available in Blade', str_contains(get('/team/grace')->body, '<code>/team/grace</code>'));
    check('abort() inside Blade -> 404', get('/team/nobody')->status === 404 && str_contains(get('/team/nobody')->body, 'Nobody called nobody here'));
    $moved = get('/team/lovelace');
    check('respond() redirect from Blade', $moved->status === 301 && $moved->getHeader('Location') === '/team/ada');
    check('@php middleware(): guest redirected', get('/team/manage')->status === 302);
    check('@php middleware(): user allowed', str_contains(get('/team/manage', ['user' => 'bob'])->body, 'Hi bob'));
    check('global middleware wraps Blade pages', get('/team')->getHeader('X-Response-Time') !== null);
    $forbidden = get('/admin', ['user' => 'bob']);
    check('_errors/403.blade.php error page', $forbidden->status === 403 && str_contains($forbidden->body, '<h1>403 Forbidden</h1>'));
    check('no route for raw /team/index.blade', get('/team/index.blade')->status === 404);
    check('__DIR__/__FILE__ in Blade point at the page, not the cache',
        str_contains(get('/team/where')->body, 'dir=team file=where.blade.php php-block=team'));
}

echo "\nExample app\n";
{
    require_once __DIR__ . '/../example/helpers.php';
    $examplePages = __DIR__ . '/../example/pages';
    $example = new Quire();
    $example->path($examplePages);
    $exampleBlade = new \Quire\Blade\BladeRenderer($examplePages, __DIR__ . '/fixtures/storage/example');
    $exampleBlade->components($examplePages . '/_components');
    $example->extension('.blade.php', $exampleBlade);
    $hit = fn (string $uri) => $example->handle(Request::create('GET', $uri));

    check('home', $hit('/')->status === 200 && str_contains($hit('/')->body, 'get a route.'));
    check('about', $hit('/about')->status === 200 && str_contains($hit('/about')->body, '<title>About · Quire</title>'));
    check('layout links Tailwind build', str_contains($hit('/')->body, 'href="/css/app.css?v='));
    check('users list via users() helper', substr_count($hit('/users')->body, '<li>') === 3);
    check('<x-avatar> initials', str_contains($hit('/users')->body, '>AL</span>'));
    check('user page via user() helper', (bool) preg_match('#<h1[^>]*>Grace Hopper</h1>#', $hit('/users/3')->body));
    check('nav highlights Users on user page', (bool) preg_match('#aria-current="page"\s*>Users</a>#', $hit('/users/3')->body));
    check('unknown user -> _errors/404.blade.php', $hit('/users/9')->status === 404 && str_contains($hit('/users/9')->body, 'User not found'));
    check('helpers: initials()', initials('grace  brewster hopper') === 'GB');
    check('helpers: users() / user()', count(users()) === 3 && user('2')['name'] === 'Alan Turing');
    check('helpers: asset() adds version', (bool) preg_match('#^/css/app\.css\?v=\d+$#', asset('css/app.css')));
}

echo "\nRoute order\n";
$patterns = array_map(fn ($r) => $r->pattern(), $quire->routes());
check('static before [param]', array_search('/users', $patterns) < array_search('/users/[id]', $patterns));
check('[param] before catch-all', array_search('/users/[id]/edit', $patterns) !== false);

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed ? 1 : 0);
