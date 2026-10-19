<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'metric_date', 'product_views', 'whatsapp_clicks'])]
class ProductDailyMetric extends Model
{
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function casts(): array
    {
        return [
            'metric_date' => 'date',
            'product_views' => 'integer',
            'whatsapp_clicks' => 'integer',
        ];
    }
}
