<?php

namespace App\Services;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;

final class ArticleHtmlSanitizer
{
    private const ALLOWED_ELEMENTS = [
        'a', 'blockquote', 'br', 'code', 'del', 'em', 'h2', 'h3', 'h4', 'hr', 'img',
        'i', 'li', 'ol', 'p', 'pre', 's', 'strong', 'u', 'ul',
    ];

    public function sanitize(string $html): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previousSetting = libxml_use_internal_errors(true);

        try {
            $document->loadHTML(
                '<?xml encoding="utf-8" ?><div id="article-content">'.$html.'</div>',
                LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED | LIBXML_NONET,
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousSetting);
        }

        $container = $document->getElementById('article-content');

        if (! $container instanceof DOMElement) {
            return e($html);
        }

        $this->sanitizeChildren($container);
        $result = '';

        foreach ($container->childNodes as $child) {
            $result .= $document->saveHTML($child);
        }

        return $result;
    }

    private function sanitizeChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMComment) {
                $node->removeChild($child);

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template'], true)) {
                $node->removeChild($child);

                continue;
            }

            if (! in_array($tag, self::ALLOWED_ELEMENTS, true)) {
                $this->unwrap($child);

                continue;
            }

            $this->sanitizeAttributes($child, $tag);

            if ($tag === 'img' && ! $child->hasAttribute('src')) {
                $node->removeChild($child);

                continue;
            }

            $this->sanitizeChildren($child);
        }
    }

    private function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        $allowed = match ($tag) {
            'a' => ['href', 'title'],
            'img' => ['alt', 'src', 'title'],
            'blockquote', 'h2', 'h3', 'h4', 'p' => ['style'],
            default => [],
        };

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->name);

            if (! in_array($name, $allowed, true)) {
                $element->removeAttributeNode($attribute);

                continue;
            }

            if ($name === 'style' && ! $this->sanitizeTextAlignment($element)) {
                $element->removeAttribute('style');
            }
        }

        if ($tag === 'a' && $element->hasAttribute('href') && ! $this->isSafeLink($element->getAttribute('href'))) {
            $element->removeAttribute('href');
        }

        if ($tag === 'img' && $element->hasAttribute('src') && ! $this->isSafeImageSource($element->getAttribute('src'))) {
            $element->removeAttribute('src');
        }
    }

    private function isSafeImageSource(string $url): bool
    {
        $url = trim($url);

        if ($url === '' || preg_match('/[\x00-\x20]/', $url) === 1 || str_starts_with($url, '//')) {
            return false;
        }

        if (str_starts_with($url, '/')) {
            return true;
        }

        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    private function sanitizeTextAlignment(DOMElement $element): bool
    {
        if (preg_match('/\A\s*text-align\s*:\s*(left|center|right|justify)\s*;?\s*\z/i', $element->getAttribute('style'), $matches) !== 1) {
            return false;
        }

        $element->setAttribute('style', 'text-align: '.strtolower($matches[1]));

        return true;
    }

    private function isSafeLink(string $url): bool
    {
        $url = trim($url);

        if ($url === '' || preg_match('/[\x00-\x20]/', $url) === 1 || str_starts_with($url, '//')) {
            return false;
        }

        if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }

        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https', 'mailto'], true);
    }

    private function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;

        if ($parent === null) {
            return;
        }

        while ($element->firstChild !== null) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }
}
