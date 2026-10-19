<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSiteSettingsRequest;
use App\Models\SiteSetting;
use App\Services\AdminAuditLogger;
use App\Services\ProductImageProcessor;
use App\Services\PublicSiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SiteSettingController extends Controller
{
    private const HOMEPAGE_KEYS = [
        'home_hero_title',
        'home_hero_description',
        'home_hero_cta_label',
        'home_hero_cta_url',
        'home_sections',
    ];

    private const SETTING_KEYS = [
        'profile_name', 'profile_description', 'profile_address', 'site_logo_path',
        'contact_address', 'contact_email', 'contact_phone', 'whatsapp_number', 'contact_map_url',
        'social_instagram', 'social_facebook', 'social_youtube',
        'seo_title', 'seo_description',
        'service_delivery', 'service_payment', 'service_returns', 'service_hours',
        'home_hero_title', 'home_hero_description', 'home_hero_cta_label', 'home_hero_cta_url',
        'home_sections',
    ];

    public function edit(PublicSiteSettings $settings): View
    {
        $values = $settings->values(self::SETTING_KEYS);
        $draftValues = $this->homepageDraftValues();

        return view('admin.settings.edit', [
            'settings' => array_replace($values, $draftValues),
            'hasHomepageDraft' => array_key_exists('home_sections', $draftValues),
            'siteLogoUrl' => filled($values['site_logo_path'] ?? null)
                ? Storage::disk((string) config('image-processing.disk', 'public'))->url($values['site_logo_path'])
                : null,
        ]);
    }

    public function saveHomepageDraft(
        SaveSiteSettingsRequest $request,
        AdminAuditLogger $audit,
    ): RedirectResponse {
        $values = array_intersect_key(
            $request->safe()->all(),
            array_flip(self::HOMEPAGE_KEYS),
        );
        $values = $this->normalizeHomepageValues($values);

        DB::transaction(function () use ($values, $request, $audit): void {
            $setting = null;

            foreach (self::HOMEPAGE_KEYS as $key) {
                $setting = SiteSetting::query()->updateOrCreate(
                    ['setting_key' => $this->draftSettingKey($key)],
                    [
                        'setting_value' => $values[$key] ?? null,
                        'value_type' => 'string',
                        'is_public' => false,
                        'updated_by' => $request->user()->getAuthIdentifier(),
                    ],
                );
            }

            if ($setting instanceof SiteSetting) {
                $audit->record($request->user(), 'site-settings.homepage-draft-saved', $setting);
            }
        });

        Cache::forget('public-site-settings');

        return redirect()->route('admin.settings.edit')->with('status', 'Draft beranda berhasil disimpan.');
    }

    public function update(
        SaveSiteSettingsRequest $request,
        AdminAuditLogger $audit,
        ProductImageProcessor $images,
    ): RedirectResponse {
        $values = $request->safe()->only(self::SETTING_KEYS);
        $values = $this->normalizeHomepageValues($values);
        $previousLogoPath = SiteSetting::query()
            ->where('setting_key', 'site_logo_path')
            ->value('setting_value');
        $previousLogoPath = is_string($previousLogoPath) ? $previousLogoPath : null;
        $newLogoPath = null;

        if ($request->hasFile('logo')) {
            $newLogoPath = $images->storeLogoImage($request->file('logo'));
            $values['site_logo_path'] = $newLogoPath;
        } elseif ($request->boolean('remove_logo')) {
            $values['site_logo_path'] = null;
        } else {
            $values['site_logo_path'] = $previousLogoPath;
        }

        try {
            DB::transaction(function () use ($values, $request, $audit): void {
                $setting = null;

                foreach (self::SETTING_KEYS as $key) {
                    $setting = SiteSetting::query()->updateOrCreate(
                        ['setting_key' => $key],
                        [
                            'setting_value' => $values[$key] ?? null,
                            'value_type' => 'string',
                            'is_public' => true,
                            'updated_by' => $request->user()->getAuthIdentifier(),
                        ],
                    );
                }

                SiteSetting::query()
                    ->whereIn('setting_key', array_map($this->draftSettingKey(...), self::HOMEPAGE_KEYS))
                    ->where('is_public', false)
                    ->delete();

                if ($setting instanceof SiteSetting) {
                    $audit->record($request->user(), 'site-settings.updated', $setting);
                }
            });
        } catch (\Throwable $exception) {
            if ($newLogoPath !== null) {
                $images->deleteLogoImage($newLogoPath);
            }

            throw $exception;
        }

        if (($newLogoPath !== null || $request->boolean('remove_logo')) && $previousLogoPath !== null) {
            $images->deleteLogoImage($previousLogoPath);
        }

        Cache::forget('public-site-settings');

        return redirect()->route('admin.settings.edit')->with('status', 'Pengaturan situs berhasil diperbarui.');
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function normalizeHomepageValues(array $values): array
    {
        $values['home_sections'] = json_encode(
            collect($values['home_sections'])->map(fn (array $section): array => [
                'enabled' => (bool) $section['enabled'],
                'order' => (int) $section['order'],
            ])->all(),
            JSON_THROW_ON_ERROR,
        );

        return $values;
    }

    /**
     * @return array<string, string>
     */
    private function homepageDraftValues(): array
    {
        return SiteSetting::query()
            ->whereIn('setting_key', array_map($this->draftSettingKey(...), self::HOMEPAGE_KEYS))
            ->where('is_public', false)
            ->pluck('setting_value', 'setting_key')
            ->mapWithKeys(fn (mixed $value, string $key): array => [
                'home_'.substr($key, strlen('home_draft_')) => (string) $value,
            ])
            ->all();
    }

    private function draftSettingKey(string $homepageKey): string
    {
        return 'home_draft_'.substr($homepageKey, strlen('home_'));
    }
}
