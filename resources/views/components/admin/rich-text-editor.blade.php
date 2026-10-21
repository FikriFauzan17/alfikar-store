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
        <div class="tiptap-editor__group" aria-label="Urungkan dan ulangi">
            <button class="tiptap-editor__button tiptap-editor__button--text" type="button" data-rich-text-command="undo" title="Urungkan perubahan (Ctrl+Z)">Urungkan</button>
            <button class="tiptap-editor__button tiptap-editor__button--text" type="button" data-rich-text-command="redo" title="Ulangi perubahan (Ctrl+Y)">Ulangi</button>
        </div>
        <div class="tiptap-editor__group" aria-label="Pemformatan teks">
            <button class="tiptap-editor__button tiptap-editor__button--bold" type="button" data-rich-text-command="bold" aria-label="Tebal" aria-pressed="false" title="Tebal (Ctrl+B)">B</button>
            <button class="tiptap-editor__button tiptap-editor__button--italic" type="button" data-rich-text-command="italic" aria-label="Miring" aria-pressed="false" title="Miring (Ctrl+I)">I</button>
            <button class="tiptap-editor__button tiptap-editor__button--underline" type="button" data-rich-text-command="underline" aria-label="Garis bawah" aria-pressed="false" title="Garis bawah (Ctrl+U)">U</button>
            <button class="tiptap-editor__button" type="button" data-rich-text-command="strike" aria-label="Coret" aria-pressed="false" title="Coret teks">S</button>
            <button class="tiptap-editor__button" type="button" data-rich-text-command="code" aria-label="Kode sebaris" aria-pressed="false" title="Kode sebaris">&lt;/&gt;</button>
        </div>
        <div class="tiptap-editor__group" aria-label="Gaya paragraf">
            <label class="tiptap-editor__select-label" for="{{ $id }}-format">Gaya teks</label>
            <select class="tiptap-editor__select" id="{{ $id }}-format" data-rich-text-format>
                <option value="paragraph">Paragraf biasa</option>
                <option value="heading-2">Judul besar</option>
                <option value="heading-3">Subjudul</option>
                <option value="heading-4">Judul kecil</option>
                <option value="blockquote">Kutipan</option>
            </select>
        </div>
        <div class="tiptap-editor__group" aria-label="Daftar">
            <button class="tiptap-editor__button tiptap-editor__button--text" type="button" data-rich-text-command="bulletList" aria-pressed="false" title="Buat daftar berpoin">• Daftar</button>
            <button class="tiptap-editor__button tiptap-editor__button--text" type="button" data-rich-text-command="orderedList" aria-pressed="false" title="Buat daftar bernomor">1. Nomor</button>
            <button class="tiptap-editor__button tiptap-editor__button--text" type="button" data-rich-text-command="outdent" title="Kurangi inden daftar">← Inden</button>
            <button class="tiptap-editor__button tiptap-editor__button--text" type="button" data-rich-text-command="indent" title="Tambah inden daftar">Inden →</button>
        </div>
        <div class="tiptap-editor__group" aria-label="Perataan teks">
            <button class="tiptap-editor__button tiptap-editor__button--text" type="button" data-rich-text-command="align" data-rich-text-alignment="left" aria-pressed="false" title="Rata kiri">Kiri</button>
            <button class="tiptap-editor__button tiptap-editor__button--text" type="button" data-rich-text-command="align" data-rich-text-alignment="center" aria-pressed="false" title="Rata tengah">Tengah</button>
            <button class="tiptap-editor__button tiptap-editor__button--text" type="button" data-rich-text-command="align" data-rich-text-alignment="right" aria-pressed="false" title="Rata kanan">Kanan</button>
            <button class="tiptap-editor__button tiptap-editor__button--text" type="button" data-rich-text-command="align" data-rich-text-alignment="justify" aria-pressed="false" title="Rata kiri-kanan">Rata</button>
        </div>
        <div class="tiptap-editor__group" aria-label="Tautan dan media">
            <button class="tiptap-editor__button tiptap-editor__button--text" type="button" data-rich-text-command="link" title="Pilih teks dahulu, lalu tambahkan alamat tautan">Sisipkan tautan</button>
            <button class="tiptap-editor__button tiptap-editor__button--text" type="button" data-rich-text-command="unlink" title="Hapus tautan dari teks terpilih">Hapus tautan</button>
            <button class="tiptap-editor__button tiptap-editor__button--text" type="button" data-rich-text-command="image" title="Unggah gambar dari perangkat Anda">Sisipkan gambar</button>
            <input class="tiptap-editor__file-input" type="file" accept="image/jpeg,image/png,image/webp" data-rich-text-image-input aria-label="Pilih gambar untuk disisipkan">
        </div>
        <div class="tiptap-editor__group" aria-label="Elemen tambahan">
            <button class="tiptap-editor__button tiptap-editor__button--text" type="button" data-rich-text-command="codeBlock" title="Sisipkan blok kode">Blok kode</button>
            <button class="tiptap-editor__button tiptap-editor__button--text" type="button" data-rich-text-command="horizontalRule" title="Sisipkan garis pemisah">Garis pemisah</button>
            <button class="tiptap-editor__button tiptap-editor__button--text" type="button" data-rich-text-command="clearFormatting" title="Hapus format dari teks terpilih">Hapus format</button>
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
