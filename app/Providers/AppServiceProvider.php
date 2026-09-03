<?php

namespace App\Providers;

use App\Support\Modulos;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
        $this->registrarGatesDeModulos();
    }

    /**
     * Autorización por módulo mediante Gates de Laravel (reemplaza a los middlewares
     * VerPermiso/EsAdmin):
     *
     * - `Gate::before`: el rol admin tiene acceso implícito a todo.
     * - `Gate::define('modulo:{clave}')`: un Gate por cada módulo del catálogo, que
     *   delega en User::tienePermiso() — la misma fuente de verdad que ya validan
     *   los Form Requests de UsuarioController contra Modulos::claves().
     */
    protected function registrarGatesDeModulos(): void
    {
        Gate::before(fn ($user, string $ability) => $user->esAdmin() ? true : null);

        // Administración del sistema (parámetros, usuarios, marca): solo rol admin.
        Gate::define('es-admin', fn ($user) => $user->esAdmin());

        foreach (Modulos::claves() as $modulo) {
            Gate::define("modulo:{$modulo}", fn ($user) => $user->tienePermiso($modulo));
        }
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
