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

<div class="tiptap-editor" data-rich-text-editor data-rich-text-max-length="{{ $maxLength }}">
    <label class="admin-field-label" id="{{ $id }}-label" for="{{ $id }}-source">{{ $label }}</label>
    <p class="tiptap-editor__help" id="{{ $helpId }}">{{ $helpText }}</p>
    <div class="tiptap-editor__toolbar" role="toolbar" aria-label="Pemformatan {{ strtolower($label) }}" data-rich-text-toolbar hidden>
        <div class="tiptap-editor__group" aria-label="Urungkan dan ulangi">
            <button class="tiptap-editor__button" type="button" data-rich-text-command="undo" aria-label="Urungkan" title="Urungkan (Ctrl+Z)">↶</button>
            <button class="tiptap-editor__button" type="button" data-rich-text-command="redo" aria-label="Ulangi" title="Ulangi (Ctrl+Y)">↷</button>
        </div>
        <div class="tiptap-editor__group" aria-label="Pemformatan teks">
            <button class="tiptap-editor__button tiptap-editor__button--bold" type="button" data-rich-text-command="bold" aria-label="Tebal" aria-pressed="false" title="Tebal (Ctrl+B)">B</button>
            <button class="tiptap-editor__button tiptap-editor__button--italic" type="button" data-rich-text-command="italic" aria-label="Miring" aria-pressed="false" title="Miring (Ctrl+I)">I</button>
            <button class="tiptap-editor__button tiptap-editor__button--underline" type="button" data-rich-text-command="underline" aria-label="Garis bawah" aria-pressed="false" title="Garis bawah (Ctrl+U)">U</button>
            <button class="tiptap-editor__button" type="button" data-rich-text-command="strike" aria-label="Coret" aria-pressed="false" title="Coret">S</button>
            <button class="tiptap-editor__button" type="button" data-rich-text-command="code" aria-label="Kode sebaris" aria-pressed="false" title="Kode sebaris">&lt;/&gt;</button>
        </div>
        <div class="tiptap-editor__group" aria-label="Gaya paragraf">
            <button class="tiptap-editor__button" type="button" data-rich-text-command="paragraph" aria-label="Paragraf biasa" aria-pressed="false" title="Paragraf">¶</button>
            <button class="tiptap-editor__button" type="button" data-rich-text-command="heading" data-rich-text-level="2" aria-label="Judul tingkat 2" aria-pressed="false" title="Judul 2">H2</button>
            <button class="tiptap-editor__button" type="button" data-rich-text-command="heading" data-rich-text-level="3" aria-label="Judul tingkat 3" aria-pressed="false" title="Judul 3">H3</button>
            <button class="tiptap-editor__button" type="button" data-rich-text-command="blockquote" aria-label="Kutipan" aria-pressed="false" title="Kutipan">“ ”</button>
        </div>
        <div class="tiptap-editor__group" aria-label="Daftar">
            <button class="tiptap-editor__button" type="button" data-rich-text-command="bulletList" aria-label="Daftar berpoin" aria-pressed="false" title="Daftar berpoin">•</button>
            <button class="tiptap-editor__button" type="button" data-rich-text-command="orderedList" aria-label="Daftar bernomor" aria-pressed="false" title="Daftar bernomor">1.</button>
        </div>
        <div class="tiptap-editor__group" aria-label="Tautan dan elemen">
            <button class="tiptap-editor__button tiptap-editor__button--text" type="button" data-rich-text-command="link" aria-label="Sisipkan tautan">Tautan</button>
            <button class="tiptap-editor__button tiptap-editor__button--text" type="button" data-rich-text-command="unlink" aria-label="Hapus tautan">Hapus tautan</button>
            <button class="tiptap-editor__button tiptap-editor__button--text" type="button" data-rich-text-command="codeBlock" aria-label="Blok kode">Blok kode</button>
            <button class="tiptap-editor__button tiptap-editor__button--text" type="button" data-rich-text-command="horizontalRule" aria-label="Sisipkan garis pemisah">Garis</button>
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
