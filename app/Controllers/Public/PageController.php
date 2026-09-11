<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CmsRepository;

/**
 * CMS-driven static pages — content comes from the `cms_pages` table
 * (Phase 9 admin, seeded in Phase 9 with about/privacy/terms/security).
 * Any slug not present and public in the database is a 404.
 */
final class PageController extends Controller
{
    public function __construct(private readonly CmsRepository $cms = new CmsRepository())
    {
    }

    public function show(Request $request): Response
    {
        $slug = (string) $request->route('slug', '');
        $page = $this->cms->pageBySlug($slug);

        if ($page === null) {
            return $this->view('errors/404', [], 404);
        }

        return $this->view('pages/generic', [
            'title'   => (string) $page['title'],
            'heading' => (string) $page['title'],
            'body'    => (string) $page['content'],
        ]);
    }
}
