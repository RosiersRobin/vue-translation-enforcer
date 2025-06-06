<?php

namespace Robinrosiers\VueTranslationEnforcer\ServiceProviders;

use Robinrosiers\VueTranslationEnforcer\Commands\AutoTranslateToToNewLocale;
use Robinrosiers\VueTranslationEnforcer\Commands\ExportTranslationsToTypeScript;
use Illuminate\Support\ServiceProvider;

class VueTranslationEnforcerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ExportTranslationsToTypeScript::class,
                AutoTranslateToToNewLocale::class,
            ]);
        }
    }
}