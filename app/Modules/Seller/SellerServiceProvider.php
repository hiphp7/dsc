<?php

namespace App\Modules\Seller;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class SellerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap services.
     *
     * @param Router $router
     * @return void
     */
    public function boot(Router $router)
    {
        $this->registerRoute($router);
    }

    /**
     * Register routes.
     *
     * @param $router
     */
    protected function registerRoute($router)
    {
        if (!$this->app->routesAreCached()) {
            $router->middleware('web')->namespace(__NAMESPACE__ . '\Controllers')->group(__DIR__ . '/route.php');
        }
    }
}
