<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveInformationPageRequest;
use App\Models\SiteSetting;
use App\Services\AdminAuditLogger;
use App\Services\InformationalPageService;
use App\Services\PublicSiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InformationPageController extends Controller
{
    public function index(InformationalPageService $pages, PublicSiteSettings $settings): View
    {
        $content = [];

        foreach (['kebijakan-privasi', 'ketentuan-pemesanan'] as $slug) {
            $definition = $pages->definition($slug);
            $content[$slug] = [
                'definition' => $definition,
                ...$pages->adminContent($slug, $settings),
            ];
        }

        return view('admin.information-pages.index', ['pages' => $content]);
    }

    public function edit(
        string $page,
        InformationalPageService $pages,
        PublicSiteSettings $settings,
    ): View {
        $definition = $pages->definition($page);
        abort_if($definition === null, 404);

        return view('admin.information-pages.edit', [
            'slug' => $page,
            'definition' => $definition,
            'content' => $pages->adminContent($page, $settings),
        ]);
    }

    public function preview(
        string $page,
        InformationalPageService $pages,
        PublicSiteSettings $settings,
    ): Response {
        $definition = $pages->definition($page);
        abort_if($definition === null, 404);

        $content = $pages->adminContent($page, $settings);
        $view = view('pages.information', [
            'page' => $content,
            'pageSlug' => $page,
            'pageTitle' => $definition['title'].' — Pratinjau',
            'isPreview' => true,
        ]);

        return response($view->render(), 200, [
            'X-Robots-Tag' => 'noindex, nofollow',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function update(
        SaveInformationPageRequest $request,
        string $page,
        InformationalPageService $pages,
        AdminAuditLogger $audit,
    ): RedirectResponse {
        $definition = $pages->definition($page);
        abort_if($definition === null, 404);

        $validated = $request->validated();
        $validated['content'] = $validated['content'] ?? '';
        $key = $validated['action'] === 'publish'
            ? $definition['key']
            : 'draft_'.$definition['key'];

        DB::transaction(function () use ($request, $validated, $definition, $key, $audit): void {
            $setting = SiteSetting::query()->updateOrCreate(
                ['setting_key' => $key],
                [
                    'setting_value' => json_encode([
                        'title' => $validated['title'],
                        'content' => $validated['content'],
                    ], JSON_THROW_ON_ERROR),
                    'value_type' => 'json',
                    'is_public' => $validated['action'] === 'publish',
                    'updated_by' => $request->user()->getAuthIdentifier(),
                ],
            );

            if ($validated['action'] === 'publish') {
                SiteSetting::query()
                    ->where('setting_key', 'draft_'.$definition['key'])
                    ->where('is_public', false)
                    ->delete();
            }

            $audit->record(
                $request->user(),
                $validated['action'] === 'publish' ? 'site-information.published' : 'site-information.draft-saved',
                $setting,
            );
        });

        return redirect()
            ->route('admin.information-pages.index')
            ->with('status', $validated['action'] === 'publish'
                ? $definition['title'].' berhasil dipublikasikan.'
                : 'Draft '.$definition['title'].' berhasil disimpan.');
    }
}
