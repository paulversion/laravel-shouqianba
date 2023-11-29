<?php
namespace Paulversion\LaravelShouQianBa;
use Illuminate\Support\ServiceProvider;
class ShouQianBaServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('shouqianba.php')
        ]);

    }
    public  function register()
    {
        $this->app->singleton('ShouQianBa',function ($app){
            return new ShouQianBa($app['config']);
        });
    }
}
