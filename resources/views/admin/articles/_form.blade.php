@php($article = $article ?? null)

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium" for="title">Judul</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="title" name="title" value="{{ old('title', $article?->title) }}" maxlength="255" required>
    </div>
    <div>
        <label class="block text-sm font-medium" for="slug">Slug</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="slug" name="slug" value="{{ old('slug', $article?->slug) }}" maxlength="255" pattern="[a-z0-9]+(-[a-z0-9]+)*" @readonly($article) required>
        @if ($article)<p class="mt-1 text-xs text-ink/60">Slug dikunci agar URL artikel terbit tetap stabil.</p>@endif
    </div>
    <div class="md:col-span-2">
        <label class="block text-sm font-medium" for="summary">Ringkasan</label>
        <textarea class="mt-1 w-full border border-rule bg-white px-3 py-2" id="summary" name="summary" rows="3" maxlength="5000" required>{{ old('summary', $article?->summary) }}</textarea>
    </div>
    <div class="md:col-span-2">
        <x-admin.rich-text-editor
            id="article-body"
            name="body"
            label="Isi artikel"
            :value="$article?->body ?? ''"
            :max-length="50000"
            help-text="Gunakan toolbar untuk memformat tulisan. Konten berbahaya akan dibuang saat disimpan."
        />
    </div>
    <div>
        <label class="block text-sm font-medium" for="status">Status</label>
        <select class="mt-1 w-full border border-rule bg-white px-3 py-2" id="status" name="status" required>
            @foreach (\App\Enums\ArticleStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(old('status', $article?->status->value ?? 'draft') === $status->value)>{{ ucfirst($status->value) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium" for="published_at">Waktu publikasi (untuk Terjadwal)</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="published_at" name="published_at" type="datetime-local" value="{{ old('published_at', $article?->published_at?->format('Y-m-d\TH:i')) }}">
    </div>
    <div>
        <label class="block text-sm font-medium" for="image">Gambar artikel</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp">
        <p class="mt-1 text-xs text-ink/60">JPEG, PNG, atau WebP; maksimal 5 MB. Gambar diproses ulang oleh layanan gambar situs.</p>
    </div>
    <div>
        <label class="block text-sm font-medium" for="image_alt">Teks alternatif gambar</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="image_alt" name="image_alt" value="{{ old('image_alt', $article?->image_alt) }}" maxlength="255">
    </div>
    @if ($article?->image_path)
        <div class="flex items-center gap-4 md:col-span-2">
            <img class="h-24 w-36 object-cover" src="{{ Storage::disk(config('image-processing.disk', 'public'))->url($article->image_path) }}" alt="{{ $article->image_alt ?? '' }}" width="144" height="96">
            <label class="flex items-center gap-2 text-sm"><input name="remove_image" type="checkbox" value="1"> Hapus gambar saat disimpan</label>
        </div>
    @endif
    <div>
        <label class="block text-sm font-medium" for="meta_title">SEO title</label>
        <input class="mt-1 w-full border border-rule bg-white px-3 py-2" id="meta_title" name="meta_title" value="{{ old('meta_title', $article?->meta_title) }}" maxlength="255">
    </div>
    <div>
        <label class="block text-sm font-medium" for="meta_description">SEO description</label>
        <textarea class="mt-1 w-full border border-rule bg-white px-3 py-2" id="meta_description" name="meta_description" rows="3" maxlength="320">{{ old('meta_description', $article?->meta_description) }}</textarea>
    </div>
</div>

<div class="mt-7 flex flex-wrap gap-3">
    <button class="bg-brand px-5 py-2 text-sm text-white hover:bg-brand-dark" type="submit">{{ $article ? 'Simpan perubahan' : 'Simpan artikel' }}</button>
    <button class="border border-brand px-5 py-2 text-sm text-brand-dark hover:bg-ivory" type="submit" name="publication_action" value="draft">Simpan Draft</button>
    <button class="bg-brand-dark px-5 py-2 text-sm text-white hover:bg-brand" type="submit" name="publication_action" value="publish">Publikasikan</button>
    <a class="border border-rule px-5 py-2 text-sm" href="{{ route('admin.articles.index') }}">Batal</a>
</div>
@if ($article)
    <p class="mt-2 text-xs text-ink/60">Simpan sebagai draft sebelum pratinjau agar perubahan terbaru ikut terlihat.</p>
@endif
