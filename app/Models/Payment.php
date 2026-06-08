<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'invoice_id',
        'receipt_number',
        'amount',
        'method',
        'status',
        'reference',
        'notes',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public static function generateReceiptNumber(): string
    {
        $prefix = 'RCPT-' . date('Y') . '-';
        $last = static::where('receipt_number', 'like', $prefix . '%')
            ->orderBy('receipt_number', 'desc')
            ->value('receipt_number');
        $next = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;
        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function bookService(): BelongsTo
    {
        return $this->invoice?->bookService();
    }
}
