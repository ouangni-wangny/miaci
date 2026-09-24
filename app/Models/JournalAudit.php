<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'action', 'entite_type', 'entite_id', 'description', 'donnees_avant', 'donnees_apres', 'ip_address'])]
class JournalAudit extends Model
{
    const UPDATED_AT = null;

    protected $table = 'journal_audit';

    protected function casts(): array
    {
        return [
            'donnees_avant' => 'array',
            'donnees_apres' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
