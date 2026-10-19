@php($category = $category ?? null)

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="name">Nama kategori</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="name" name="name" value="{{ old('name', $category?->name) }}" maxlength="255" required>
    </div>
    <div>
        <label class="block text-sm font-medium" for="slug">Slug</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="slug" name="slug" value="{{ old('slug', $category?->slug) }}" maxlength="255" pattern="[a-z0-9]+(-[a-z0-9]+)*" required>
    </div>
    <div class="md:col-span-2">
        <label class="block text-sm font-medium" for="description">Deskripsi</label>
        <textarea class="mt-1 w-full border border-rule bg-white px-3 py-2" id="description" name="description" rows="4" maxlength="5000">{{ old('description', $category?->description) }}</textarea>
    </div>
    <div class="grid gap-3 md:col-span-2">
        <label class="block text-sm font-medium" for="image">Gambar kategori (satu gambar)</label>
        @if ($category?->image_path)
            <img class="aspect-square w-full max-w-sm border border-rule object-cover" src="{{ Storage::disk(config('image-processing.disk', 'public'))->url($category->image_path) }}" alt="Gambar kategori {{ $category->name }}">
            <label class="flex items-center gap-2 text-sm">
                <input name="remove_image" type="checkbox" value="1" @checked(old('remove_image'))>
                Hapus gambar saat ini
            </label>
        @endif
        <input class="w-full border border-rule bg-white px-3 py-2" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
        <p class="text-xs text-ink/60">JPEG, PNG, atau WebP; maksimal 5 MB. Mengunggah gambar baru akan mengganti gambar sebelumnya.</p>
        @error('image')<p class="text-sm text-red-800">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium" for="sort_order">Urutan</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="sort_order" name="sort_order" type="number" min="0" max="65535" value="{{ old('sort_order', $category?->sort_order ?? 0) }}">
    </div>
    <label class="flex items-center gap-3 self-end py-2 text-sm">
        <input name="is_published" type="checkbox" value="1" @checked(old('is_published', $category?->is_published ?? false))>
        Terbitkan kategori
    </label>
</div>

<div class="mt-7 flex flex-wrap gap-3">
    <button class="bg-brand px-5 py-2 text-sm text-white hover:bg-brand-dark" type="submit">{{ $category ? 'Simpan perubahan' : 'Simpan kategori' }}</button>
    <a class="border border-rule px-5 py-2 text-sm" href="{{ route('admin.categories.index') }}">Batal</a>
</div>