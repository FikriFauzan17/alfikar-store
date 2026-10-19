<?php

namespace App\Http\Controllers;

use App\Http\Requests\WhatsAppOrderRequest;
use App\Models\Product;
use App\Services\ProductMetricRecorder;
use App\Services\WhatsAppOrderBuilder;
use Illuminate\Http\RedirectResponse;

class WhatsAppOrderController extends Controller
{
    public function redirect(
        WhatsAppOrderRequest $request,
        Product $product,
        WhatsAppOrderBuilder $whatsapp,
        ProductMetricRecorder $metrics,
    ): RedirectResponse {
        $product = Product::query()
            ->published()
            ->whereKey($product->getKey())
            ->firstOrFail();

        abort_unless($whatsapp->isAvailable(), 404);

        $productUrl = route('products.show', ['product' => $product->slug]);
        $whatsappUrl = $whatsapp->url($product, $request->integer('quantity'), $productUrl);
        $metrics->recordOnce($product, 'whatsapp_click', $request->session()->getId());

        return redirect()->away($whatsappUrl);
    }
}
