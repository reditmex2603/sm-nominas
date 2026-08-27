<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        $this->hardenProductionDebug();

        // Eloquent estricto fuera de producción: detección temprana de N+1, atributos
        // descartados silenciosamente y accesos a atributos inexistentes. En pruebas,
        // estas excepciones revelan bugs reales (p. ej. fillable incompleto).
        if (! app()->isProduction()) {
            Model::preventLazyLoading();
            Model::preventSilentlyDiscardingAttributes();
            Model::preventAccessingMissingAttributes();
        }

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Nunca exponer stack traces/APP_DEBUG en producción, incluso si .env lo
     * declara por error, y nunca servir la app a través de HTTP.
     */
    protected function hardenProductionDebug(): void
    {
        if (! app()->isProduction()) {
            return;
        }

        if (Config::get('app.debug')) {
            Log::warning('APP_DEBUG quedó activo en producción; se forzó a false.');

            Config::set('app.debug', false);
        }

        if (Config::get('security.force_https')) {
            URL::forceScheme('https');
        }

        TrustProxies::at(Config::get('security.trusted_proxies'));
    }
}
