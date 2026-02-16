<?php

namespace App\Providers;

use App\Interfaces\PasswordInterface;
use App\Interfaces\UserInterface;
use App\Repositories\PasswordRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $bindings = [
            UserInterface::class => UserRepository::class,
            PasswordInterface::class => PasswordRepository::class,
        ];

        foreach ($bindings as $interface => $repository)
        {
            $this->app->bind($interface, $repository);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
