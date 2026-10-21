<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps policy pages and their administration private until publication', function () {
    $this->get(route('pages.information', 'kebijakan-privasi'))->assertNotFound();
    $this->get(route('admin.information-pages.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create());
    $this->put(route('admin.information-pages.update', 'kebijakan-privasi'), [
        'title' => 'Kebijakan Privasi',
        'content' => 'Draf yang belum diterbitkan.',
        'action' => 'draft',
    ])->assertForbidden();

    $this->assertDatabaseCount('site_settings', 0);
});

it('sanitizes rich text drafts and renders a private no-store preview', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);
    $content = '<h2>Informasi</h2><p><strong>Konten</strong> draf belum diterbitkan. <a href="javascript:alert(1)">Tautan</a></p><script>alert("xss")</script>';

    $this->put(route('admin.information-pages.update', 'kebijakan-privasi'), [
        'title' => 'Privasi <uji>',
        'content' => $content,
        'action' => 'draft',
        'is_public' => true,
    ])->assertRedirect(route('admin.information-pages.index'));
    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $admin->id,
        'event' => 'site-information.draft-saved',
    ]);

    $this->assertDatabaseHas('site_settings', [
        'setting_key' => 'draft_privacy_policy',
        'is_public' => false,
    ]);
    $this->assertDatabaseMissing('site_settings', ['setting_key' => 'privacy_policy']);

    $this->get(route('admin.information-pages.edit', 'kebijakan-privasi'))
        ->assertOk()
        ->assertSee('data-rich-text-editor', false)
        ->assertSee('data-rich-text-toolbar', false)
        ->assertSee('data-rich-text-format', false)
        ->assertSee('data-rich-text-command="image"', false)
        ->assertSee('/admin/rich-text/images', false);

    $preview = $this->get(route('admin.information-pages.preview', 'kebijakan-privasi'));
    $preview->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('Privasi &lt;uji&gt;', false)
        ->assertSee('<h2>Informasi</h2><p><strong>Konten</strong> draf belum diterbitkan. <a>Tautan</a></p>', false)
        ->assertDontSee('<script>', false)
        ->assertDontSee('javascript:', false)
        ->assertSee('<meta name="robots" content="noindex,nofollow">', false);
    expect($preview->headers->get('Cache-Control'))
        ->toContain('private')
        ->toContain('no-store')
        ->toContain('max-age=0');

    $this->get(route('pages.information', 'kebijakan-privasi'))->assertNotFound();
});

it('publishes approved rich text, invalidates public settings, and includes only published pages in navigation and sitemap', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);
    $this->get(route('home'))->assertOk()->assertDontSee('Kebijakan Privasi');

    $this->put(route('admin.information-pages.update', 'kebijakan-privasi'), [
        'title' => 'Kebijakan Privasi',
        'content' => '<h2>Ringkasan</h2><p>Konten sebelumnya yang telah <strong>disetujui</strong>.</p>',
        'action' => 'publish',
    ])->assertRedirect(route('admin.information-pages.index'));
    $this->assertDatabaseHas('site_settings', [
        'setting_key' => 'privacy_policy',
        'is_public' => true,
    ]);
    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $admin->id,
        'event' => 'site-information.published',
    ]);
    $this->assertDatabaseMissing('site_settings', ['setting_key' => 'draft_privacy_policy']);
    $this->get(route('pages.information', 'kebijakan-privasi'))
        ->assertOk()
        ->assertSee('Konten sebelumnya yang telah disetujui.')
        ->assertSee('<strong>disetujui</strong>', false)
        ->assertSee('Kebijakan Privasi');

    $this->put(route('admin.information-pages.update', 'kebijakan-privasi'), [
        'title' => 'Kebijakan Privasi',
        'content' => 'Perubahan yang masih berupa draft.',
        'action' => 'draft',
    ])->assertRedirect(route('admin.information-pages.index'));
    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $admin->id,
        'event' => 'site-information.draft-saved',
    ]);
    $this->get(route('pages.information', 'kebijakan-privasi'))
        ->assertOk()
        ->assertSee('Konten sebelumnya yang telah disetujui.')
        ->assertDontSee('Perubahan yang masih berupa draft.');
    $this->get(route('admin.information-pages.preview', 'kebijakan-privasi'))
        ->assertOk()
        ->assertSee('Perubahan yang masih berupa draft.');

    $this->put(route('admin.information-pages.update', 'kebijakan-privasi'), [
        'title' => 'Kebijakan Privasi',
        'content' => 'Keterangan privasi yang diberikan pemilik toko.',
        'action' => 'publish',
    ])->assertRedirect(route('admin.information-pages.index'));
    $this->get(route('pages.information', 'kebijakan-privasi'))
        ->assertOk()
        ->assertSee('Keterangan privasi yang diberikan pemilik toko.')
        ->assertDontSee('Perubahan yang masih berupa draft.');
    $this->get(route('home'))->assertOk()->assertSee(route('pages.information', 'kebijakan-privasi'), false);

    $xml = $this->get(route('sitemap'))->assertOk()->streamedContent();
    expect($xml)->toContain(route('pages.information', 'kebijakan-privasi'))
        ->not->toContain(route('pages.information', 'ketentuan-pemesanan'));
});

it('rejects publishing empty page content and invalid statistics ranges', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $this->from(route('admin.information-pages.edit', 'ketentuan-pemesanan'))
        ->put(route('admin.information-pages.update', 'ketentuan-pemesanan'), [
            'title' => 'Ketentuan Pemesanan',
            'content' => '<p>&nbsp;</p><p><br></p><script>tidak terlihat</script>',
            'action' => 'publish',
        ])
        ->assertRedirect(route('admin.information-pages.edit', 'ketentuan-pemesanan'))
        ->assertSessionHasErrors(['content' => 'Isi halaman perlu dilengkapi sebelum dipublikasikan.']);

    $this->assertDatabaseMissing('site_settings', ['setting_key' => 'ordering_terms']);
    $this->get(route('admin.statistics.products', ['days' => 999]))
        ->assertSessionHasErrors(['days']);
});
