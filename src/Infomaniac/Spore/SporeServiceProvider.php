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
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $classLoader = $this->getComposerClassLoader();
        $this->app['composer.autoload'] = $this->app->share(
            function () use ($classLoader) {
                return $classLoader;
            }
        );

        $this->app['router'] = $this->app->share(
            function () {
                return new Router();
            }
        );
    }

    private function getComposerClassLoader()
    {
        $base             = App::make('path.base');
        if(!file_exists($base) || !file_exists("$base/vendor/autoload.php"))
            throw new Exception('Could not load Composer\'s ClassLoader - this is required for Spore to work');

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
        return array();
    }

}