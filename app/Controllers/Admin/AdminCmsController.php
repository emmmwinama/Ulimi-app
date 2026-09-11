<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AdminAuth;
use App\Core\AuditLog;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CmsRepository;

/**
 * The public marketing site's content: settings (key/value), static pages,
 * feature tiles and testimonials. One controller, several sub-resources —
 * each is a handful of fields, not worth a separate class per the
 * "don't over-engineer a simple CRUD" rule.
 */
final class AdminCmsController extends Controller
{
    public function __construct(private readonly CmsRepository $cms = new CmsRepository())
    {
    }

    public function index(Request $request): Response
    {
        return $this->view('admin/cms/index', [
            'title'        => 'Site content',
            'active'       => 'cms',
            'tab'          => (string) $request->query('tab', 'content'),
            'content'      => $this->cms->allContentRows(),
            'pages'        => $this->cms->pages(),
            'features'     => $this->cms->features(),
            'testimonials' => $this->cms->testimonials(),
        ]);
    }

    /* ------------------------------------------------------------- content */

    public function updateContent(Request $request): Response
    {
        $key = (string) $request->route('key');
        $value = (string) $request->input('value', '');
        $this->cms->setContent($key, $value, (string) AdminAuth::id());
        AuditLog::admin('admin.cms_content_updated', (string) AdminAuth::id(), 'site_content', $key, [], $request->ip());
        Flash::success('Saved.');
        return $this->redirect(url('admin/cms'));
    }

    /* --------------------------------------------------------------- pages */

    public function savePage(Request $request): Response
    {
        $data = $this->validate($request, [
            'slug'    => ['required', 'max:80', 'regex:/^[a-z0-9-]+$/'],
            'title'   => ['required', 'max:160'],
            'content' => ['required'],
        ], ['slug.regex' => 'Use lowercase letters, numbers and hyphens only.']);
        if ($data instanceof Response) {
            return $data;
        }
        $id = (string) $request->input('id', '') ?: null;
        $this->cms->upsertPage($id, (string) $data['slug'], (string) $data['title'], (string) $data['content'], $request->boolean('is_public'));
        AuditLog::admin('admin.cms_page_saved', (string) AdminAuth::id(), 'cms_page', $id ?? $data['slug'], [], $request->ip());
        Flash::success('Page saved.');
        return $this->redirect(url('admin/cms?tab=pages'));
    }

    public function deletePage(Request $request): Response
    {
        $id = (string) $request->route('id');
        $this->cms->deletePage($id);
        AuditLog::admin('admin.cms_page_deleted', (string) AdminAuth::id(), 'cms_page', $id, [], $request->ip());
        Flash::success('Page deleted.');
        return $this->redirect(url('admin/cms?tab=pages'));
    }

    /* ------------------------------------------------------------ features */

    public function saveFeature(Request $request): Response
    {
        $data = $this->validate($request, [
            'icon' => ['required', 'max:40'],
            'title' => ['required', 'max:160'],
            'description' => ['required'],
            'sort_order' => ['integer'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }
        $id = (string) $request->input('id', '');
        $sort = (int) ($data['sort_order'] ?? 0);
        $active = $request->boolean('is_active');
        if ($id !== '') {
            $this->cms->updateFeature($id, (string) $data['icon'], (string) $data['title'], (string) $data['description'], $sort, $active);
        } else {
            $this->cms->createFeature((string) $data['icon'], (string) $data['title'], (string) $data['description'], $sort);
        }
        Flash::success('Feature saved.');
        return $this->redirect(url('admin/cms?tab=features'));
    }

    public function deleteFeature(Request $request): Response
    {
        $this->cms->deleteFeature((string) $request->route('id'));
        Flash::success('Feature deleted.');
        return $this->redirect(url('admin/cms?tab=features'));
    }

    /* --------------------------------------------------------- testimonials */

    public function saveTestimonial(Request $request): Response
    {
        $data = $this->validate($request, [
            'quote' => ['required'],
            'name'  => ['required', 'max:120'],
            'role'  => ['max:120'],
            'initials' => ['max:4'],
            'sort_order' => ['integer'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }
        $id = (string) $request->input('id', '');
        $sort = (int) ($data['sort_order'] ?? 0);
        $active = $request->boolean('is_active');
        $initials = (string) ($data['initials'] ?? '') ?: strtoupper(substr((string) $data['name'], 0, 2));
        if ($id !== '') {
            $this->cms->updateTestimonial($id, (string) $data['quote'], (string) $data['name'], (string) ($data['role'] ?? ''), $initials, $sort, $active);
        } else {
            $this->cms->createTestimonial((string) $data['quote'], (string) $data['name'], (string) ($data['role'] ?? ''), $initials, $sort);
        }
        Flash::success('Testimonial saved.');
        return $this->redirect(url('admin/cms?tab=testimonials'));
    }

    public function deleteTestimonial(Request $request): Response
    {
        $this->cms->deleteTestimonial((string) $request->route('id'));
        Flash::success('Testimonial deleted.');
        return $this->redirect(url('admin/cms?tab=testimonials'));
    }
}
