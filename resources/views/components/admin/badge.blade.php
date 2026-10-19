@props(['tone' => 'neutral'])

<span {{ $attributes->class(['admin-badge', 'admin-badge--success' => $tone === 'success', 'admin-badge--warning' => $tone === 'warning', 'admin-badge--danger' => $tone === 'danger', 'admin-badge--neutral' => $tone === 'neutral']) }}>
    {{ $slot }}
</span>
