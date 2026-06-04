<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assessment extends Model
{
    protected $fillable = [
        'book_service_id',
        'assessed_by',
        'findings',
        'photos',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'photos' => 'array',
        ];
    }

    public function bookService(): BelongsTo
    {
        return $this->belongsTo(BookService::class);
    }

    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function quotation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Quotation::class);
    }
}
