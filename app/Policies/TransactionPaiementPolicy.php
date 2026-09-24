<?php

namespace App\Policies;

use App\Models\TransactionPaiement;
use App\Models\User;

class TransactionPaiementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['ADMIN', 'GESTIONNAIRE']);
    }

    public function view(User $user, TransactionPaiement $transaction): bool
    {
        return $user->hasAnyRole(['ADMIN', 'GESTIONNAIRE'])
            || $user->id === $transaction->adherent->user_id;
    }
}
