<?php

namespace App\Http\Controllers;

use App\Services\ArticleHtmlSanitizer;
use App\Services\InformationalPageService;
use App\Services\PublicSiteSettings;
use Illuminate\View\View;

class InformationPageController extends Controller
{
    public function show(
        string $page,
        InformationalPageService $pages,
        PublicSiteSettings $settings,
        ArticleHtmlSanitizer $sanitizer,
    ): View {
        $definition = $pages->definition($page);
        abort_if($definition === null, 404);

        $content = $pages->published($page, $settings);
        abort_if($content === null, 404);
        $safeContent = $sanitizer->sanitize($content['content']);

        return view('pages.information', [
            'page' => $content,
            'pageSlug' => $page,
            'pageTitle' => $definition['title'],
            'isPreview' => false,
            'safeContent' => $safeContent,
        ]);
    }
}
