<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\AdminAuth;
use App\Core\AuditLog;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\InquiryRepository;

final class AdminInquiriesController extends Controller
{
    public function __construct(private readonly InquiryRepository $inquiries = new InquiryRepository())
    {
    }

    public function index(Request $request): Response
    {
        return $this->view('admin/inquiries/index', [
            'title'   => 'Inquiries',
            'tab'     => (string) $request->query('tab', 'contact'),
            'contacts'=> $this->inquiries->contacts(),
            'demos'   => $this->inquiries->demos(),
        ]);
    }

    public function updateContactStatus(Request $request): Response
    {
        $id = (string) $request->route('id');
        $status = (string) $request->input('status', 'read');
        if (!in_array($status, ['new', 'read', 'replied', 'archived'], true)) {
            $status = 'read';
        }
        $this->inquiries->updateContactStatus($id, $status);
        AuditLog::admin('admin.contact_status_changed', (string) AdminAuth::id(), 'contact_submission', $id, ['status' => $status], $request->ip());
        Flash::success('Updated.');
        return $this->redirect(url('admin/inquiries'));
    }

    public function updateDemoStatus(Request $request): Response
    {
        $id = (string) $request->route('id');
        $status = (string) $request->input('status', 'confirmed');
        if (!in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'], true)) {
            $status = 'pending';
        }
        $this->inquiries->updateDemoStatus($id, $status);
        AuditLog::admin('admin.demo_status_changed', (string) AdminAuth::id(), 'demo_booking', $id, ['status' => $status], $request->ip());
        Flash::success('Updated.');
        return $this->redirect(url('admin/inquiries?tab=demo'));
    }
}
