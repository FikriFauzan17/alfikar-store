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
    <div class="md:col-span-2" data-article-editor>
        <label class="block text-sm font-medium" id="body-label" for="body" data-article-editor-label>Isi artikel</label>
        <p class="mt-1 text-xs text-ink/60" id="body-help">Gunakan toolbar untuk memformat tulisan. Konten berbahaya akan dibuang saat disimpan.</p>
        <div class="article-editor__ribbon mt-3" aria-label="Pita alat editor" data-article-editor-toolbar hidden>
            <div class="article-editor__tabs" role="tablist" aria-label="Tab editor" data-article-editor-tabs>
                <button class="article-editor__tab is-active" id="body-tab-home" type="button" role="tab" aria-selected="true" aria-controls="body-panel-home" data-article-editor-tab="home">Beranda</button>
                <button class="article-editor__tab" id="body-tab-insert" type="button" role="tab" aria-selected="false" aria-controls="body-panel-insert" tabindex="-1" data-article-editor-tab="insert">Sisipkan</button>
            </div>
            <div class="article-editor__panel" id="body-panel-home" role="tabpanel" aria-labelledby="body-tab-home" data-article-editor-panel="home">
                <div class="article-editor__group" aria-label="Urungkan dan ulangi">
                    <div class="article-editor__group-tools">
                        <button class="article-editor__button article-editor__button--symbol" type="button" data-article-editor-command="undo" aria-label="Urungkan" title="Urungkan (Ctrl+Z)">↶</button>
                        <button class="article-editor__button article-editor__button--symbol" type="button" data-article-editor-command="redo" aria-label="Ulangi" title="Ulangi (Ctrl+Y)">↷</button>
                    </div>
                    <span class="article-editor__group-label">Urungkan</span>
                </div>
                <div class="article-editor__group" aria-label="Pemformatan huruf">
                    <div class="article-editor__group-tools">
                        <button class="article-editor__button article-editor__button--symbol article-editor__button--bold" type="button" data-article-editor-command="bold" data-article-editor-toggle aria-label="Tebal" title="Tebal (Ctrl+B)" aria-pressed="false">B</button>
                        <button class="article-editor__button article-editor__button--symbol article-editor__button--italic" type="button" data-article-editor-command="italic" data-article-editor-toggle aria-label="Miring" title="Miring (Ctrl+I)" aria-pressed="false">I</button>
                        <button class="article-editor__button article-editor__button--symbol article-editor__button--underline" type="button" data-article-editor-command="underline" data-article-editor-toggle aria-label="Garis bawah" title="Garis bawah (Ctrl+U)" aria-pressed="false">U</button>
                    </div>
                    <span class="article-editor__group-label">Huruf</span>
                </div>
                <div class="article-editor__group" aria-label="Paragraf dan daftar">
                    <div class="article-editor__group-tools">
                        <button class="article-editor__button article-editor__button--symbol" type="button" data-article-editor-command="insertUnorderedList" aria-label="Daftar berpoin" title="Daftar berpoin">•</button>
                        <button class="article-editor__button article-editor__button--symbol" type="button" data-article-editor-command="insertOrderedList" aria-label="Daftar bernomor" title="Daftar bernomor">1.</button>
                    </div>
                    <span class="article-editor__group-label">Paragraf</span>
                </div>
                <div class="article-editor__group article-editor__group--style" aria-label="Gaya teks">
                    <label class="sr-only" for="body-editor-style">Pilih gaya teks</label>
                    <select class="article-editor__style-select" id="body-editor-style" data-article-editor-format>
                        <option value="p">Normal</option>
                        <option value="h2">Judul 2</option>
                        <option value="h3">Judul 3</option>
                        <option value="h4">Judul 4</option>
                        <option value="blockquote">Kutipan</option>
                    </select>
                    <span class="article-editor__group-label">Gaya</span>
                </div>
            </div>
            <div class="article-editor__panel" id="body-panel-insert" role="tabpanel" aria-labelledby="body-tab-insert" data-article-editor-panel="insert" hidden>
                <div class="article-editor__group" aria-label="Sisipkan elemen">
                    <div class="article-editor__group-tools">
                        <button class="article-editor__button article-editor__button--text" type="button" data-article-editor-command="createLink" aria-label="Sisipkan tautan" title="Sisipkan tautan">Tautan</button>
                        <button class="article-editor__button article-editor__button--text" type="button" data-article-editor-command="insertHorizontalRule" aria-label="Sisipkan garis horizontal" title="Sisipkan garis horizontal">Garis horizontal</button>
                    </div>
                    <span class="article-editor__group-label">Sisipkan</span>
                </div>
            </div>
            <p class="article-editor__shortcut-hint">Pintasan: Ctrl+B tebal · Ctrl+I miring · Ctrl+U garis bawah</p>
        </div>
        <div class="article-editor__workspace">
            <div class="article-editor__surface" id="body-editor" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="body-label" aria-describedby="body-help body-error" aria-required="true" data-article-editor-surface hidden></div>
        </div>
        <textarea class="mt-2 w-full border border-rule bg-white px-3 py-2 font-mono text-sm" id="body" name="body" rows="14" maxlength="50000" aria-describedby="body-help body-error" required data-article-editor-source>{{ old('body', $article?->body) }}</textarea>
        <p class="article-editor__status mt-1 text-xs text-ink/60" id="body-error" aria-live="polite" data-article-editor-status hidden></p>
        @error('body')<p class="mt-1 text-sm text-red-800" role="alert">{{ $message }}</p>@enderror
        <p class="mt-1 text-xs text-ink/60" data-article-editor-count hidden></p>
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
