@php($banner = $banner ?? null)

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="eyebrow">Eyebrow</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="eyebrow" name="eyebrow" value="{{ old('eyebrow', $banner?->eyebrow) }}" maxlength="255">
    </div>
    <div>
        <label class="block text-sm font-medium" for="title">Judul</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="title" name="title" value="{{ old('title', $banner?->title) }}" maxlength="255" required>
    </div>
    <div class="md:col-span-2">
        <label class="block text-sm font-medium" for="description">Deskripsi</label>
        <textarea class="mt-1 w-full border border-rule bg-white px-3 py-2" id="description" name="description" rows="4" maxlength="5000">{{ old('description', $banner?->description) }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium" for="image">Gambar</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
        <p class="mt-1 text-xs text-ink/60">JPEG, PNG, atau WebP; maksimal 5 MB. Gambar diproses ulang.</p>
    </div>
    <div>
        <label class="block text-sm font-medium" for="image_alt">Teks alternatif gambar</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="image_alt" name="image_alt" value="{{ old('image_alt', $banner?->image_alt) }}" maxlength="255">
    </div>
    @if ($banner?->image_path)
        <div class="flex items-center gap-4 md:col-span-2">
            <img class="h-24 w-36 object-cover" src="{{ Storage::disk(config('image-processing.disk', 'public'))->url($banner->image_path) }}" alt="{{ $banner->image_alt ?? '' }}" width="144" height="96">
            <label class="flex items-center gap-2 text-sm"><input name="remove_image" type="checkbox" value="1"> Hapus gambar saat disimpan</label>
        </div>
    @endif
    <div>
        <label class="block text-sm font-medium" for="link_url">URL tujuan (HTTPS atau path lokal)</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="link_url" name="link_url" value="{{ old('link_url', $banner?->link_url) }}" maxlength="2048">
    </div>
    <div>
        <label class="block text-sm font-medium" for="link_label">Label tautan</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="link_label" name="link_label" value="{{ old('link_label', $banner?->link_label) }}" maxlength="255">
    </div>
    <div>
        <label class="block text-sm font-medium" for="starts_at">Mulai tampil</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at', $banner?->starts_at?->format('Y-m-d\TH:i')) }}">
    </div>
    <div>
        <label class="block text-sm font-medium" for="ends_at">Akhir tampil</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at', $banner?->ends_at?->format('Y-m-d\TH:i')) }}">
    </div>
    <div>
        <label class="block text-sm font-medium" for="sort_order">Urutan</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="sort_order" name="sort_order" type="number" min="0" max="65535" value="{{ old('sort_order', $banner?->sort_order ?? 0) }}" required>
    </div>
    <label class="flex items-center gap-2 self-end py-2 text-sm"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $banner?->is_active ?? false))> Aktifkan banner pada periode ini</label>
</div>

<div class="mt-7 flex flex-wrap gap-3">
    <button class="bg-brand px-5 py-2 text-sm text-white hover:bg-brand-dark" type="submit">{{ $banner ? 'Simpan perubahan' : 'Simpan banner' }}</button>
    <a class="border border-rule px-5 py-2 text-sm" href="{{ route('admin.banners.index') }}">Batal</a>
</div>
