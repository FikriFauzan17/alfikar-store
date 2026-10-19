<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

class WhatsAppOrderBuilder
{
    public function __construct(private readonly PublicSiteSettings $settings) {}

    public function isAvailable(): bool
    {
        return $this->settings->whatsappNumber() !== null;
    }

    public function url(Product $product, int $quantity, string $productUrl): string
    {
        $number = $this->settings->whatsappNumber();

        if ($number === null) {
            throw new \LogicException('WhatsApp is not configured with a valid international number.');
        }

        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Jumlah harus minimal satu.']);
        }

        $wholesaleApplies = $product->wholesale_price !== null
            && $product->wholesale_minimum !== null
            && $quantity >= $product->wholesale_minimum;
        $unitPrice = (int) ($wholesaleApplies ? $product->wholesale_price : $product->unit_price);

        if ($unitPrice > 0 && $quantity > intdiv(PHP_INT_MAX, $unitPrice)) {
            throw ValidationException::withMessages(['quantity' => 'Jumlah melebihi batas perhitungan harga.']);
        }

        $subtotal = $unitPrice * $quantity;
        $stockMessage = match (true) {
            $product->stock === 0 => 'Mohon informasikan apakah buku tersedia kembali.',
            $quantity > $product->stock => 'Mohon konfirmasi ketersediaan stok untuk jumlah tersebut.',
            default => 'Mohon konfirmasi stok dan ongkir.',
        };

        $message = implode("\n", [
            'Halo Toko Buku Alfikar, saya ingin memesan buku berikut:',
            'Judul: ' . $product->title,
            'Kode: ' . $product->code,
            'Jumlah: ' . $quantity,
            'Harga satuan: Rp' . number_format($unitPrice, 0, ',', '.'),
            'Subtotal: Rp' . number_format($subtotal, 0, ',', '.') . ' (belum termasuk ongkir)',
            'URL: ' . $productUrl,
            $stockMessage,
        ]);

        return 'https://wa.me/' . $number . '?text=' . rawurlencode($message);
    }
}
