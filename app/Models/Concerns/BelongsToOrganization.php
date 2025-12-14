<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Support\Tenancy\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\App;

trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope('organization', function (Builder $builder): void {
            $tenantId = Tenant::id();

            if (App::runningInConsole() || App::environment('testing')) {
                if ($tenantId) {
                    $builder->where($builder->getModel()->getQualifiedOrganizationIdColumn(), $tenantId);
                }
                return;
            }

            if (! $tenantId) {
                abort(403, 'Tenant not resolved.');
            }

            $builder->where($builder->getModel()->getQualifiedOrganizationIdColumn(), $tenantId);
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute($model->getOrganizationIdColumn())) {
                return;
            }

            $tenantId = Tenant::id();

            if (! $tenantId && ! (App::runningInConsole() || App::environment('testing'))) {
                abort(403, 'Tenant not resolved.');
            }

            if ($tenantId) {
                $model->setAttribute($model->getOrganizationIdColumn(), $tenantId);
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, $this->getOrganizationIdColumn());
    }

    protected function getOrganizationIdColumn(): string
    {
        return property_exists($this, 'organizationIdColumn')
            ? $this->organizationIdColumn
            : 'organization_id';
    }

    protected function getQualifiedOrganizationIdColumn(): string
    {
        return $this->getTable().'.'.$this->getOrganizationIdColumn();
    }
}
