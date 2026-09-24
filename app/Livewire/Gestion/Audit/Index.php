<?php

namespace App\Livewire\Gestion\Audit;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Page « Journal d'audit » ; la liste est portée par JournalTable. */
#[Layout('layouts.app')]
class Index extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->can('voir_journal_audit'), 403);
    }

    public function render(): View
    {
        return view('livewire.gestion.audit.index');
    }
}
