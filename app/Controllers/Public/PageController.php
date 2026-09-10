<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;

/**
 * CMS-driven static pages (about, privacy, terms, security, …).
 *
 * Phase 1: a hardcoded allowlist with placeholder copy so footer links resolve.
 * Phase 10 replaces the body with content from the `cms_pages` table.
 */
final class PageController extends Controller
{
    private const PAGES = [
        'about'     => 'About AgriVault',
        'privacy'   => 'Privacy Policy',
        'terms'     => 'Terms of Service',
        'security'  => 'Security',
        'support'   => 'Support',
    ];

    public function show(Request $request): Response
    {
        $slug = (string) $request->route('slug', '');

        if (!array_key_exists($slug, self::PAGES)) {
            return $this->view('errors/404', [], 404);
        }

        return $this->view('pages/generic', [
            'title'   => self::PAGES[$slug],
            'heading' => self::PAGES[$slug],
            'slug'    => $slug,
        ]);
    }
}
