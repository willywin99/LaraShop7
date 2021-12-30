<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Repositories\Front\Interfaces\CatalogueRepositoryInterface;
use App\Repositories\Front\CatalogueRepository;
use App\Repositories\Front\Interfaces\CartRepositoryInterface;
use App\Repositories\Front\CartRepository;


class FrontRepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            CatalogueRepositoryInterface::class,
            CatalogueRepository::class
        );
        $this->app->bind(
            CartRepositoryInterface::class,
            CartRepository::class
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
