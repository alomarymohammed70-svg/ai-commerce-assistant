<?php

namespace App\Policies;

use App\Models\AIAnalysis;
use App\Models\User;

class AIAnalysisPolicy
{
    /**
     * Only administrators may list all AI analyses (used by Filament Admin).
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * A user may view an AI analysis only if they are an admin or own the record.
     * Legacy/unowned analyses (user_id === null) are denied to regular users.
     */
    public function view(User $user, AIAnalysis $analysis): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $analysis->user_id !== null
            && (int) $analysis->user_id === (int) $user->id;
    }

    /**
     * Only administrators may create AI analysis records through Filament CRUD.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only administrators may update AI analysis records through Filament CRUD.
     */
    public function update(User $user, AIAnalysis $analysis): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only administrators may delete AI analysis records through Filament CRUD.
     */
    public function delete(User $user, AIAnalysis $analysis): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only administrators may bulk delete AI analysis records through Filament CRUD.
     */
    public function deleteAny(User $user): bool
    {
        return $user->isAdmin();
    }
}

