<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Ports\ArchitectureRevisionRepositoryInterface;
use App\Domain\Ports\AuditLoggerInterface;
use App\Domain\Ports\IdempotencyStoreInterface;
use App\Domain\Ports\OutboxPublisherInterface;
use App\Domain\Ports\ProjectRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Infrastructure\Audit\EloquentAuditLogger;
use App\Infrastructure\Clock\SystemClock;
use App\Infrastructure\Outbox\TransactionalOutboxPublisher;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentArchitectureRevisionRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentIdempotencyStore;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentProjectRepository;
use Illuminate\Support\ServiceProvider;

/**
 * The one place a Domain/Application port is bound to its Infrastructure
 * adapter (COMPONENT_ARCHITECTURE_V7.md "Domain <- Ports <- Infrastructure
 * Adapters"). Swapping an adapter - e.g. a different persistence backend
 * behind the same port - never requires touching Domain or Application code.
 */
final class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ArchitectureRevisionRepositoryInterface::class, EloquentArchitectureRevisionRepository::class);
        $this->app->bind(ProjectRepositoryInterface::class, EloquentProjectRepository::class);
        $this->app->bind(IdempotencyStoreInterface::class, EloquentIdempotencyStore::class);
        $this->app->bind(AuditLoggerInterface::class, EloquentAuditLogger::class);
        $this->app->bind(OutboxPublisherInterface::class, TransactionalOutboxPublisher::class);
        $this->app->singleton(ClockInterface::class, SystemClock::class);
    }
}
