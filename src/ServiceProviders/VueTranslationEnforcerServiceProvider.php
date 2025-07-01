<?php

namespace Rosiersrobin\VueTranslationEnforcer\ServiceProviders;

use Rosiersrobin\VueTranslationEnforcer\Commands\ExportTranslationsToTypeScript;
use Illuminate\Support\ServiceProvider;

class VueTranslationEnforcerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ExportTranslationsToTypeScript::class,
            ]);
        }
    }
}