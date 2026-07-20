# app/Providers

## Purpose

This reference defines conventions for providers under `app/Providers`.

## When To Use

Use this reference when your task matches this path and you need to follow its local conventions.

## Required Pattern

Use `app/Providers` for application-wide framework configuration, package integration, and container bindings.

### Provider Shape

- Keep provider boot logic intentional and minimal.
- Split broad boot configuration into private `configure*(): void` methods when the provider owns many independent framework settings.
- Existing provider behavior configures destructive-command protection, immutable dates, unknown-field rejection for form requests, health checks, strict Eloquent behavior, production-only model violation reporting, morph maps, resource wrapping, rate limiters, password defaults, redirect toast macros, URL behavior, Vite prefetching, Fortify views/actions, Filament panel setup, TypeScript transformer setup, and NanoID bindings.
- Prefer typed rate limiter closures where sibling provider code uses them, and preserve package-established closure shapes where sibling code does not type them. Use transliterated throttle keys when submitted identifier or actor input participates in the key. When touching Fortify limiters, compare existing Fortify provider siblings before changing the closure shape.
- Use `DeferrableProvider` plus `provides(): array` for narrow container bindings such as generated-id service aliases.
- Keep Filament panel setup fluent and declarative inside the panel provider.
- Avoid adding provider state that can leak across Octane requests.
- Do not change provider behavior without checking the broad blast radius.

### Provider Registration

Register application providers explicitly in `bootstrap/providers.php`.

```php
<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\ExampleIdServiceProvider;

return [
    AppServiceProvider::class,
    ExampleIdServiceProvider::class,
];
```

### Application Provider Example

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Actor;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Facades\Health;

class ExampleApplicationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureCommands();
        $this->configureDates();
        $this->configureFormRequests();
        $this->configureHealthChecks();
        $this->configureModels();
        $this->configureRateLimiters();
        $this->configureResources();
        $this->configureRouteMacros();
        $this->configureUrls();
        $this->configureVite();
    }

    private function configureCommands(): void
    {
        DB::prohibitDestructiveCommands($this->app->isProduction());
    }

    private function configureDates(): void
    {
        Date::use(CarbonImmutable::class);
    }

    private function configureFormRequests(): void
    {
        FormRequest::failOnUnknownFields();
    }

    private function configureHealthChecks(): void
    {
        Health::checks([
            CacheCheck::new(),
            DatabaseCheck::new(),
            DebugModeCheck::new(),
            OptimizedAppCheck::new(),
            QueueCheck::new()->failWhenHealthJobTakesLongerThanMinutes(2),
            ScheduleCheck::new()->heartbeatMaxAgeInMinutes(2),
        ]);
    }

    private function configureModels(): void
    {
        Model::automaticallyEagerLoadRelationships();
        Model::shouldBeStrict();
        Model::unguard();

        Relation::enforceMorphMap([
            'actor' => Actor::class,
        ]);

        if ($this->app->isProduction()) {
            Model::handleDiscardedAttributeViolationUsing(
                ExampleReporter::discardedAttributeViolationReporter()
            );
            Model::handleLazyLoadingViolationUsing(
                ExampleReporter::lazyLoadingViolationReporter()
            );
            Model::handleMissingAttributeViolationUsing(
                ExampleReporter::missingAttributeViolationReporter()
            );
        }
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute(1000)->by(auth()->id() ?? $request->ip());
        });

        RateLimiter::for('access-code.request', function (Request $request): Limit {
            return Limit::perMinute(5)->by(
                Str::transliterate(sprintf('%s|%s', $request->string('identifier'), $request->ip()))
            );
        });
    }

    private function configureResources(): void
    {
        JsonResource::withoutWrapping();
    }

    private function configureRouteMacros(): void
    {
        RedirectResponse::macro('toast', function (
            string $title,
            null|string $description = null,
            ExampleNoticeVariant $variant = ExampleNoticeVariant::Success,
            int $timeout = 5
        ): RedirectResponse {
            Inertia::flash(ExampleNoticeKey::Toast(), array_filter([
                'description' => $description,
                'timeout' => $timeout * 1000,
                'title' => $title,
                'variant' => $variant->value,
            ], fn (null|int|string $value): bool => $value !== null));

            return $this;
        });
    }

    private function configureUrls(): void
    {
        URL::forceHttps(! $this->app->environment('local', 'testing'));
    }

    private function configureVite(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
```

### Fortify Provider Example

Authentication providers keep Fortify action registration, views, redirect callbacks, and framework throttle definitions together:

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Auth\CreateNewActor;
use App\Actions\Auth\ResetActorPassword;
use App\Actions\Auth\UpdateActorPassword;
use App\Actions\Auth\UpdateActorProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class ExampleFortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewActor::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);
        Fortify::resetUserPasswordsUsing(ResetActorPassword::class);
        Fortify::updateUserPasswordsUsing(UpdateActorPassword::class);
        Fortify::updateUserProfileInformationUsing(UpdateActorProfileInformation::class);

        Fortify::loginView(function () {
            return Inertia::render('auth/Login');
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(sprintf(
                '%s|%s',
                Str::lower((string) $request->string(Fortify::username())),
                $request->ip()
            ));

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
```

### Deferrable Binding Provider Example

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Override;

class ExampleIdServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * @return list<string>
     */
    #[Override]
    public function provides(): array
    {
        return [ExampleIdClient::class, 'example-id'];
    }

    #[Override]
    public function register(): void
    {
        $this->app->singleton(ExampleIdClient::class);

        $this->app->alias(ExampleIdClient::class, 'example-id');
    }
}
```

### Fluent Panel Provider Example

```php
<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;

class ExamplePanelServiceProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->colors(['primary' => Color::Amber])
            ->default()
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->login()
            ->path('admin');
    }
}
```

### Package Configuration Provider Example

Package extension providers may extend the package's service provider and override the package configure hook instead of implementing a generic `boot()` method:

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use Package\Transformer\BaseTransformerServiceProvider;
use Package\Transformer\ConfigFactory;

class ExampleTransformerServiceProvider extends BaseTransformerServiceProvider
{
    protected function configure(ConfigFactory $config): void
    {
        $config
            ->transformDirectories(app_path('Enums'))
            ->outputDirectory(resource_path('js/types'))
            ->writer(new ExampleWriter('generated/enums.ts'));
    }
}
```

### Tests

- Cover provider-facing behavior through the surface it affects: architecture tests, middleware feature tests, controller feature tests, model tests, or integration tests.
- Do not add provider tests that only duplicate framework wiring unless the wiring is the contract.
- For container bindings, prefer testing the consuming action unless the binding alias itself is the contract.

## Coverage Expectations

Read the live file in this path, compare it with sibling files, and cover the behavior in the suite or reference that owns that surface. Do not add adjacent coverage just for symmetry.

## Do Not

- Do not contradict the skill non-negotiables or project conventions.

## Related References

- `references/app/functions.php.md`
- `references/tests/Pest.md`
