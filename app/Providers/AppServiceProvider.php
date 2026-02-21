<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind MailTrapService wrapper so it can be injected where expected.
        $this->app->singleton(\App\Services\MailTrap\MailTrapService::class, function ($app) {
            return new \App\Services\MailTrap\MailTrapService($app->make(\App\Services\EmailService::class));
        });

        // Bind TransactionRepositoryInterface to its concrete implementation.
        $this->app->bind(
            \App\Contracts\TransactionRepositoryInterface::class,
            \App\Repositories\TransactionRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
