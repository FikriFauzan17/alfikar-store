@php($product = $product ?? null)

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="title">Judul</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="title" name="title" value="{{ old('title', $product?->title) }}" maxlength="255" required>
    </div>
    <div>
        <label class="block text-sm font-medium" for="code">Kode</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="code" name="code" value="{{ old('code', $product?->code) }}" maxlength="100" required>
    </div>
    <div>
        <label class="block text-sm font-medium" for="slug">Slug</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="slug" name="slug" value="{{ old('slug', $product?->slug) }}" maxlength="255" pattern="[a-z0-9]+(-[a-z0-9]+)*" required>
    </div>
    <div>
        <label class="block text-sm font-medium" for="category_id">Kategori</label>
        <select class="mt-1 w-full border border-rule bg-white px-3 py-2" id="category_id" name="category_id" required>
            <option value="">Pilih kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $product?->category_id) === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium" for="author">Penulis</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="author" name="author" value="{{ old('author', $product?->author) }}" maxlength="255" required>
    </div>
    <div>
        <label class="block text-sm font-medium" for="publisher">Penerbit</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="publisher" name="publisher" value="{{ old('publisher', $product?->publisher) }}" maxlength="255" required>
    </div>
    <div>
        <label class="block text-sm font-medium" for="isbn">ISBN</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="isbn" name="isbn" value="{{ old('isbn', $product?->isbn) }}" maxlength="255">
    </div>
    @if (! $product)
        <div>
            <label class="block text-sm font-medium" for="stock">Stok awal</label>
            <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="stock" name="stock" type="number" min="0" step="1" value="{{ old('stock', 0) }}" required>
            @error('stock')<p class="mt-1 text-sm text-red-800">{{ $message }}</p>@enderror
        </div>
    @endif
    <div>
        <label class="block text-sm font-medium" for="unit_price">Harga satuan (rupiah)</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="unit_price" name="unit_price" type="number" min="0" step="1" value="{{ old('unit_price', $product?->unit_price) }}" required>
    </div>
    <div>
        <label class="block text-sm font-medium" for="wholesale_price">Harga grosir (rupiah)</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="wholesale_price" name="wholesale_price" type="number" min="0" step="1" value="{{ old('wholesale_price', $product?->wholesale_price) }}">
    </div>
    <div>
        <label class="block text-sm font-medium" for="wholesale_minimum">Minimum pembelian grosir</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="wholesale_minimum" name="wholesale_minimum" type="number" min="1" step="1" value="{{ old('wholesale_minimum', $product?->wholesale_minimum) }}">
    </div>
    <div>
        <label class="block text-sm font-medium" for="published_at">Waktu publikasi</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="published_at" name="published_at" type="datetime-local" value="{{ old('published_at', $product?->published_at?->format('Y-m-d\TH:i')) }}">
    </div>
    <div class="md:col-span-2">
        <label class="block text-sm font-medium" for="description">Deskripsi</label>
        <textarea class="mt-1 w-full border border-rule bg-white px-3 py-2" id="description" name="description" rows="6" maxlength="50000" required>{{ old('description', $product?->description) }}</textarea>
    </div>
    <div class="md:col-span-2">
        <label class="block text-sm font-medium" for="specifications_json">Spesifikasi (JSON)</label>
        <textarea class="mt-1 w-full border border-rule bg-white px-3 py-2 font-mono text-sm" id="specifications_json" name="specifications_json" rows="5">{{ old('specifications_json', $product?->specifications ? json_encode($product->specifications, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium" for="meta_title">SEO title</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="meta_title" name="meta_title" value="{{ old('meta_title', $product?->meta_title) }}" maxlength="255">
    </div>
    <div>
        <label class="block text-sm font-medium" for="meta_description">SEO description</label>
        <textarea class="mt-1 w-full border border-rule bg-white px-3 py-2" id="meta_description" name="meta_description" rows="3" maxlength="320">{{ old('meta_description', $product?->meta_description) }}</textarea>
    </div>
    <div class="flex flex-wrap gap-x-6 gap-y-3 md:col-span-2">
        <label class="flex items-center gap-2 text-sm"><input name="is_published" type="checkbox" value="1" @checked(old('is_published', $product?->is_published ?? false))> Terbit</label>
        <label class="flex items-center gap-2 text-sm"><input name="is_featured" type="checkbox" value="1" @checked(old('is_featured', $product?->is_featured ?? false))> Unggulan</label>
        <label class="flex items-center gap-2 text-sm"><input name="is_bestseller" type="checkbox" value="1" @checked(old('is_bestseller', $product?->is_bestseller ?? false))> Bestseller</label>
    </div>
</div>

<div class="mt-7 flex flex-wrap gap-3">
    <button class="bg-brand px-5 py-2 text-sm text-white hover:bg-brand-dark" type="submit">{{ $product ? 'Simpan perubahan' : 'Simpan produk' }}</button>
    <button class="border border-brand px-5 py-2 text-sm text-brand-dark hover:bg-ivory" type="submit" name="publication_action" value="draft">Simpan Draft</button>
    <button class="bg-brand-dark px-5 py-2 text-sm text-white hover:bg-brand" type="submit" name="publication_action" value="publish">Publikasikan</button>
    <a class="border border-rule px-5 py-2 text-sm" href="{{ route('admin.products.index') }}">Batal</a>
</div>
@if ($product)
    <p class="mt-2 text-xs text-ink/60">Simpan sebagai draft sebelum pratinjau agar perubahan terbaru ikut terlihat.</p>
@endif