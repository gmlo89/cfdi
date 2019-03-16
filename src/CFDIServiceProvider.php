<?php

namespace Gmlo\CFDI;

use Gmlo\CFDI\Utils\SatCatalogs;
use Illuminate\Support\ServiceProvider;

class CFDIServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        require __DIR__ . '/Utils/functions.php';
        $this->app->singleton('sat_catalogs', function () {
            return $this->app->make(SatCatalogs::class);
        });
        $this->loadConfigFiles();

        //require './Utils/Helpers.php';
        /*$this->app->bind(
            'Laracasts\Flash\SessionStore',
            'Laracasts\Flash\LaravelSessionStore'
        );
    */
        /*
        $this->app->singleton('cfdi', function () {
            return $this->app->make('Gmlo\CFDI\CFDIMaker');
        });*/
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        //$this->loadViewsFrom(__DIR__ . '/../../views', 'flash');
        /*
        $this->publishes([
            __DIR__ . '/../../views' => base_path('resources/views/vendor/flash')
        ]);*/
        $this->loadTranslations();
    }

    protected function loadTranslations()
    {
        $this->loadTranslationsFrom(__DIR__ . '/lang', 'CFDI');

        /*$this->publishes([
            __DIR__.'/translations' => resource_path('lang/vendor/courier'),
        ]);*/
    }

    /**
    * Load configuration files
    */
    protected function loadConfigFiles()
    {
        $config_files = [
            'countries', 'ine', 'tax_regime', 'taxes', 'types', 'units', 'cfdi_uses', 'core', 'pay_methods', 'pay_way', 'others'
        ];
        foreach ($config_files as $file) {
            $configPath = __DIR__ . "/config/{$file}.php";
            $this->mergeConfigFrom($configPath, 'cfdi.' . $file);
        }
    }
}
