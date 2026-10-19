<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_id',
    'product_code',
    'product_title',
    'actor_id',
    'actor_name',
    'direction',
    'reason',
    'quantity',
    'stock_before',
    'stock_after',
    'notes',
    'submission_token',
])]
class StockAdjustment extends Model
{
    public const REASONS = ['whatsapp_sale', 'goods_received', 'correction'];

    public $timestamps = false;

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'stock_before' => 'integer',
            'stock_after' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
