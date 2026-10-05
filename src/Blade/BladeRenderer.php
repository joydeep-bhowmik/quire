<?php

declare(strict_types=1);

namespace Quire\Blade;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory as FactoryContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Component;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use LogicException;

/**
 * Renders *.blade.php pages with Laravel's Blade, standalone (no Laravel app needed).
 *
 *   composer require illuminate/view illuminate/events
 *
 *   $blade = new BladeRenderer(viewPaths: __DIR__ . '/pages', cachePath: __DIR__ . '/storage/views');
 *   $blade->components(__DIR__ . '/pages/_components');      // <x-card>
 *   $quire->extension('.blade.php', $blade);
 *
 * View paths are where @extends, @include and @component look, e.g. @extends('_layouts.app')
 * resolves to pages/_layouts/app.blade.php when the pages folder is a view path.
 */
final class BladeRenderer
{
    private readonly Factory $factory;

    private readonly BladeCompiler $compiler;

    private readonly Container $container;

    private static ?self $active = null;

    /**
     * @param string|list<string> $viewPaths
     */
    public function __construct(string|array $viewPaths, string $cachePath, ?Container $container = null)
    {
        if (!class_exists(Factory::class) || !class_exists(Dispatcher::class)) {
            throw new LogicException('The Blade adapter needs Blade: composer require illuminate/view illuminate/events');
        }

        if (!is_dir($cachePath) && !mkdir($cachePath, 0775, true) && !is_dir($cachePath)) {
            throw new LogicException("Unable to create Blade cache directory [{$cachePath}].");
        }

        $files = new Filesystem();
        $this->container = $container ?? Container::getInstance();
        $this->compiler = new class ($files, $cachePath) extends BladeCompiler {
            // Compiled views run from the cache folder, which would make __DIR__ useless in a page.
            // Point __DIR__ and __FILE__ back at the original .blade.php file (in @php blocks too).
            public function compileString($value)
            {
                $compiled = parent::compileString($value);
                $path = $this->getPath();

                return $path === null ? $compiled : (string) preg_replace_callback(
                    '/\b__(DIR|FILE)__\b/',
                    static fn (array $m): string => var_export($m[1] === 'DIR' ? dirname($path) : $path, true),
                    $compiled,
                );
            }
        };

        $engines = new EngineResolver();
        $engines->register('blade', fn () => new CompilerEngine($this->compiler, $files));
        $engines->register('php', fn () => new PhpEngine($files));

        $this->factory = new Factory($engines, new FileViewFinder($files, (array) $viewPaths), new Dispatcher($this->container));
        $this->factory->setContainer($this->container);

        $this->activate();

        // <x-...> tags ask the Laravel app for its namespace before trying anonymous components.
        // Outside Laravel there's no app, so answer for it.
        if (!$this->container->bound(Application::class)) {
            $this->container->instance(Application::class, new class () {
                public function getNamespace(): string
                {
                    return 'App\\';
                }
            });
        }
    }

    /**
     * @param array<string, mixed> $vars
     */
    public function __invoke(string $file, array $vars): string
    {
        $this->activate();

        return $this->factory->file($file, $vars)->render();
    }

    /**
     * Register a folder of anonymous components: components($dir) gives <x-card>,
     * components($dir, 'ui') gives <x-ui::card>.
     */
    public function components(string $path, ?string $prefix = null): self
    {
        $this->activate();
        $this->compiler->anonymousComponentPath($path, $prefix);

        return $this;
    }

    /**
     * Custom directive: directive('money', fn ($expr) => "<?php echo number_format($expr, 2); ?>").
     */
    public function directive(string $name, callable $handler): self
    {
        $this->compiler->directive($name, $handler);

        return $this;
    }

    /**
     * Make a variable available to every view.
     */
    public function share(string $key, mixed $value): self
    {
        $this->factory->share($key, $value);

        return $this;
    }

    public function factory(): Factory
    {
        return $this->factory;
    }

    public function compiler(): BladeCompiler
    {
        return $this->compiler;
    }

    /**
     * Blade components find the factory and compiler through the global container, and
     * Component caches the factory statically. Point both at this renderer, so several
     * renderers (e.g. two Quire apps in one process) don't render through each other.
     */
    private function activate(): void
    {
        if (self::$active === $this) {
            return;
        }

        $this->container->instance(FactoryContract::class, $this->factory);
        $this->container->instance('view', $this->factory);
        $this->container->instance(BladeCompiler::class, $this->compiler);
        $this->container->instance('blade.compiler', $this->compiler);
        Component::forgetFactory();

        self::$active = $this;
    }
}
