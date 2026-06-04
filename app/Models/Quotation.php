<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quotation extends Model
{
    protected $fillable = [
        'book_service_id',
        'assessment_id',
        'line_items',
        'subtotal',
        'tax',
        'total',
        'status',
        'valid_until',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'line_items' => 'array',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'valid_until' => 'date',
        ];
    }

    public function bookService(): BelongsTo
    {
        return $this->belongsTo(BookService::class);
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function project(): HasOne
    {
        return $this->hasOne(Project::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }
}
