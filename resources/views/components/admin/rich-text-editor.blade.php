@props([
    'id',
    'name',
    'label',
    'value' => '',
    'maxLength' => 50000,
    'required' => true,
    'helpText' => 'Gunakan toolbar untuk memformat tulisan. Konten akan dibersihkan sebelum ditampilkan.',
])

@php
    $helpId = $id.'-help';
    $errorId = $id.'-error';
    $statusId = $id.'-status';
    $countId = $id.'-count';
    $errorMessage = $errors->first($name);
@endphp

<div
    class="tiptap-editor"
    data-rich-text-editor
    data-rich-text-max-length="{{ $maxLength }}"
    data-rich-text-image-upload-url="{{ route('admin.rich-text.images.store') }}"
>
    <label class="admin-field-label" id="{{ $id }}-label" for="{{ $id }}-source">{{ $label }}</label>
    <p class="tiptap-editor__help" id="{{ $helpId }}">{{ $helpText }} Klik area putih untuk mulai mengetik. Pilih teks sebelum menekan tombol format. Untuk gambar, gunakan JPEG, PNG, atau WebP maksimal 5 MB. Pintasan: Ctrl+B (tebal), Ctrl+I (miring), Ctrl+Z (urungkan).</p>
    <div class="tiptap-editor__toolbar" role="toolbar" aria-label="Pemformatan {{ strtolower($label) }}" data-rich-text-toolbar hidden>
        <div class="tiptap-editor__group" aria-label="Riwayat perubahan">
            <button class="tiptap-editor__button" type="button" data-rich-text-command="undo" aria-label="Urungkan" title="Urungkan (Ctrl+Z)">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 14 4 9l5-5M4 9h9a7 7 0 0 1 7 7v3"/></svg>
            </button>
            <button class="tiptap-editor__button" type="button" data-rich-text-command="redo" aria-label="Ulangi" title="Ulangi (Ctrl+Y)">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m15 14 5-5-5-5m5 5h-9a7 7 0 0 0-7 7v3"/></svg>
            </button>
        </div>
        <div class="tiptap-editor__group" aria-label="Pemformatan teks">
            <button class="tiptap-editor__button tiptap-editor__button--bold" type="button" data-rich-text-command="bold" aria-label="Tebal" aria-pressed="false" title="Tebal (Ctrl+B)">B</button>
            <button class="tiptap-editor__button tiptap-editor__button--italic" type="button" data-rich-text-command="italic" aria-label="Miring" aria-pressed="false" title="Miring (Ctrl+I)">I</button>
            <button class="tiptap-editor__button tiptap-editor__button--underline" type="button" data-rich-text-command="underline" aria-label="Garis bawah" aria-pressed="false" title="Garis bawah (Ctrl+U)">U</button>
            <button class="tiptap-editor__button tiptap-editor__button--strike" type="button" data-rich-text-command="strike" aria-label="Coret" aria-pressed="false" title="Coret teks">S</button>
            <button class="tiptap-editor__button" type="button" data-rich-text-command="code" aria-label="Kode sebaris" aria-pressed="false" title="Kode sebaris">&lt;/&gt;</button>
        </div>
        <div class="tiptap-editor__group" aria-label="Gaya paragraf">
            <label class="tiptap-editor__select-label" for="{{ $id }}-format" title="Pilih gaya paragraf atau judul">Gaya</label>
            <select class="tiptap-editor__select" id="{{ $id }}-format" data-rich-text-format aria-label="Gaya teks">
                <option value="paragraph">Paragraf biasa</option>
                <option value="heading-2">Judul besar</option>
                <option value="heading-3">Subjudul</option>
                <option value="heading-4">Judul kecil</option>
                <option value="blockquote">Kutipan</option>
            </select>
        </div>
        <div class="tiptap-editor__group" aria-label="Daftar">
            <button class="tiptap-editor__button" type="button" data-rich-text-command="bulletList" aria-label="Daftar berpoin" aria-pressed="false" title="Daftar berpoin">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4" cy="6" r="1" fill="currentColor" stroke="none"/><circle cx="4" cy="12" r="1" fill="currentColor" stroke="none"/><circle cx="4" cy="18" r="1" fill="currentColor" stroke="none"/></svg>
            </button>
            <button class="tiptap-editor__button" type="button" data-rich-text-command="orderedList" aria-label="Daftar bernomor" aria-pressed="false" title="Daftar bernomor">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M10 6h10M10 12h10M10 18h10M4 5h2v3M4 11h2l-2 2h2M4 17h2v2H4"/></svg>
            </button>
            <button class="tiptap-editor__button" type="button" data-rich-text-command="outdent" aria-label="Kurangi inden" title="Kurangi inden daftar">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M10 6h10M10 12h10M10 18h10M3 9l4 3-4 3"/></svg>
            </button>
            <button class="tiptap-editor__button" type="button" data-rich-text-command="indent" aria-label="Tambah inden" title="Tambah inden daftar">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M10 6h10M10 12h10M10 18h10M7 9l-4 3 4 3"/></svg>
            </button>
        </div>
        <div class="tiptap-editor__group" aria-label="Perataan teks">
            <button class="tiptap-editor__button" type="button" data-rich-text-command="align" data-rich-text-alignment="left" aria-label="Rata kiri" aria-pressed="false" title="Rata kiri">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 5h16M4 9h11M4 13h16M4 17h11M4 21h16"/></svg>
            </button>
            <button class="tiptap-editor__button" type="button" data-rich-text-command="align" data-rich-text-alignment="center" aria-label="Rata tengah" aria-pressed="false" title="Rata tengah">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 5h16M7 9h10M4 13h16M7 17h10M4 21h16"/></svg>
            </button>
            <button class="tiptap-editor__button" type="button" data-rich-text-command="align" data-rich-text-alignment="right" aria-label="Rata kanan" aria-pressed="false" title="Rata kanan">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 5h16M9 9h11M4 13h16M9 17h11M4 21h16"/></svg>
            </button>
            <button class="tiptap-editor__button" type="button" data-rich-text-command="align" data-rich-text-alignment="justify" aria-label="Rata kiri-kanan" aria-pressed="false" title="Rata kiri-kanan">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 5h16M4 9h16M4 13h16M4 17h16M4 21h16"/></svg>
            </button>
        </div>
        <div class="tiptap-editor__group" aria-label="Tautan dan media">
            <button class="tiptap-editor__button" type="button" data-rich-text-command="link" aria-label="Sisipkan tautan" title="Sisipkan tautan pada teks terpilih">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M10 13a5 5 0 0 0 7.1 0l3-3A5 5 0 0 0 13 2.9l-1.7 1.7M14 11a5 5 0 0 0-7.1 0l-3 3A5 5 0 0 0 11 21.1l1.7-1.7"/></svg>
            </button>
            <button class="tiptap-editor__button" type="button" data-rich-text-command="unlink" aria-label="Hapus tautan" title="Hapus tautan dari teks terpilih">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M10 13a5 5 0 0 0 7.1 0l3-3A5 5 0 0 0 13 2.9l-1.7 1.7M14 11a5 5 0 0 0-7.1 0l-3 3A5 5 0 0 0 11 21.1l1.7-1.7M3 3l18 18"/></svg>
            </button>
            <button class="tiptap-editor__button" type="button" data-rich-text-command="image" aria-label="Sisipkan gambar" title="Unggah dan sisipkan gambar">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m21 15-5-5L5 20"/></svg>
            </button>
            <input class="tiptap-editor__file-input" type="file" accept="image/jpeg,image/png,image/webp" data-rich-text-image-input aria-label="Pilih gambar untuk disisipkan">
        </div>
        <div class="tiptap-editor__group" aria-label="Elemen tambahan">
            <button class="tiptap-editor__button tiptap-editor__button--text" type="button" data-rich-text-command="codeBlock" aria-label="Blok kode" title="Sisipkan blok kode">&lt;&gt;</button>
            <button class="tiptap-editor__button" type="button" data-rich-text-command="horizontalRule" aria-label="Sisipkan garis pemisah" title="Sisipkan garis pemisah">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 12h16"/></svg>
            </button>
            <button class="tiptap-editor__button tiptap-editor__button--text tiptap-editor__button--clear" type="button" data-rich-text-command="clearFormatting" aria-label="Hapus format" title="Hapus semua format dari teks terpilih">A<sup>×</sup></button>
        </div>
    </div>
    <div class="tiptap-editor__workspace">
        <div
            class="tiptap-editor__surface"
            id="{{ $id }}-surface"
            data-rich-text-surface
            hidden
            aria-labelledby="{{ $id }}-label"
            aria-describedby="{{ $helpId }} {{ $errorId }} {{ $statusId }}"
            @if ($errorMessage) aria-invalid="true" @endif
        ></div>
        <textarea
            class="admin-field-input tiptap-editor__source"
            id="{{ $id }}-source"
            name="{{ $name }}"
            rows="14"
            maxlength="{{ $maxLength }}"
            @required($required)
            aria-describedby="{{ $helpId }} {{ $errorId }} {{ $statusId }}"
            @if ($errorMessage) aria-invalid="true" @endif
            data-rich-text-source
        >{{ old($name, $value) }}</textarea>
    </div>
    <p class="tiptap-editor__status" id="{{ $statusId }}" role="status" aria-live="polite" data-rich-text-status hidden></p>
    @if ($errorMessage)
        <p class="admin-field-error" id="{{ $errorId }}" role="alert">{{ $errorMessage }}</p>
    @endif
    <p class="tiptap-editor__count" id="{{ $countId }}" data-rich-text-count hidden></p>
</div>
