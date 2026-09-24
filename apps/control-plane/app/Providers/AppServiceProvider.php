<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Fail loudly on lazy-loading/mass-assignment/silent-attribute
        // mistakes outside production - these are exactly the classes of
        // bug spec/09_SECURITY_ABUSE_TESTS.md "privilege escalation by mass
        // assignment" cares about.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Immutable datetimes everywhere by default, matching
        // App\Domain\Shared\ClockInterface's return type.
        Date::use(CarbonImmutable::class);

        // spec/08_SLO_CAPACITY_BUDGETS.md "Backpressure": every ingress has
        // a finite admission-control limit rather than accepting unbounded
        // load. Scoped per authenticated principal (falling back to IP for
        // an unauthenticated caller) so one noisy client cannot exhaust
        // another's budget.
        RateLimiter::for('riskweft-writes', static fn (Request $request) => Limit::perMinute(30)->by($request->user()?->getAuthIdentifier() ?? $request->ip()));

        RateLimiter::for('riskweft-reads', static fn (Request $request) => Limit::perMinute(120)->by($request->user()?->getAuthIdentifier() ?? $request->ip()));
    }
}
