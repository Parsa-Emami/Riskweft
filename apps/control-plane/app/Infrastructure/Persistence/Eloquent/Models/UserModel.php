<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Local/dev principal used to issue Sanctum bearer tokens (see
 * app/Console/Commands/SeedDevActorCommand.php). contracts/openapi.v1.yaml
 * documents bearerAuth as "opaque/OIDC-access-token": a production
 * deployment is expected to front this with a real OIDC-token-verifying
 * guard as an infrastructure adapter; this model is what that guard
 * ultimately resolves to, not a login system RiskWeft exposes over HTTP (no
 * such route exists in the frozen OpenAPI contract).
 */
final class UserModel extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids;

    protected $table = 'users';

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function projectMemberships(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProjectMemberModel::class, 'user_id');
    }
}
