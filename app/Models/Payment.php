<?php

// app/Models/Payment.php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'reservation_id', 'method', 'status', 'amount', 'gateway_ref',
        'proof_path', 'sender_name', 'sender_bank', 'transfer_date',
    ];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'transfer_date' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** Antrean verifikasi admin, terlama di atas (UC-15). */
    public function scopeAwaitingVerification(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Submitted)->oldest();
    }
}
