<?php

namespace App\Livewire\Gestion\Paiements;

use App\Models\TransactionPaiement;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Page « Transactions de paiement en ligne » ; la liste est portée par TransactionsTable. */
#[Layout('layouts.app')]
class Index extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', TransactionPaiement::class);
    }

    public function render(): View
    {
        return view('livewire.gestion.paiements.index');
    }
}
