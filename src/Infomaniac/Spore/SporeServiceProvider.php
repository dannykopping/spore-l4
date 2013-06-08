<?php namespace Infomaniac\Spore;

use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Infomaniac\Spore\Illuminate\Routing\Router;

class SporeServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('infomaniac/spore');

        // replace Laravel's native Router
        $this->app['router'] = $this->app->share(
            function () {
                return new Router();
            }
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $classLoader                    = $this->getComposerClassLoader();
        $this->app['composer.autoload'] = $this->app->share(
            function () use ($classLoader) {
                return $classLoader;
            }
        );
    }

    /**
     * Get the Composer ClassLoader for use with namespace searching
     *
     * @return mixed|null
     */
    private function getComposerClassLoader()
    {
        $base = App::make('path.base');
        if (!file_exists($base) || !file_exists("$base/vendor/autoload.php")) {
            return null;
        }

        $classLoader = require "$base/vendor/autoload.php";
        return $classLoader;
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('composer.autoload', 'router');
    }

}