<?php

namespace App\Policies;

use App\Models\ProductMetric;
use App\Models\User;

class ProductMetricPolicy
{
    /**
     * Only administrators may list all product metrics (used by Filament Admin).
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * A user may view an individual user-owned ProductMetric only if they are an admin or own the record.
     * Shared catalog metrics (user_id === null) are surfaced via scoped catalog endpoints, not direct ownership.
     */
    public function view(User $user, ProductMetric $metric): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $metric->user_id !== null
            && (int) $metric->user_id === (int) $user->id;
    }

    /**
     * Only administrators may create product metric records through Filament CRUD.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only administrators may update product metric records through Filament CRUD.
     */
    public function update(User $user, ProductMetric $metric): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only administrators may delete product metric records through Filament CRUD.
     */
    public function delete(User $user, ProductMetric $metric): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only administrators may bulk delete product metric records through Filament CRUD.
     */
    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}

