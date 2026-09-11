<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Controller;
use App\Core\AuditLog;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\InquiryRepository;

/**
 * Public contact + demo-booking forms. Both carry a honeypot field
 * ("website") that is hidden from sighted users via CSS — a bot that fills
 * every field trips it, and the submission is silently dropped with the same
 * success response a real visitor gets, so the bot learns nothing.
 */
final class ContactController extends Controller
{
    public function __construct(private readonly InquiryRepository $inquiries = new InquiryRepository())
    {
    }

    public function submitContact(Request $request): Response
    {
        $data = $this->validate($request, [
            'name'    => ['required', 'max:160'],
            'email'   => ['required', 'email', 'max:190'],
            'message' => ['required', 'max:2000'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        if ($this->isHoneypotTripped($request)) {
            Flash::success('Thanks — we’ll be in touch soon.');
            return $this->back($request, '/');
        }

        $this->inquiries->createContact([
            'name'    => trim((string) $data['name']),
            'email'   => mb_strtolower(trim((string) $data['email'])),
            'message' => trim((string) $data['message']),
        ]);

        AuditLog::record('public.contact_submitted', 'system', null, null, null, null, [], $request->ip());
        Flash::success('Thanks — we’ll be in touch soon.');
        return $this->back($request, '/');
    }

    public function submitDemo(Request $request): Response
    {
        $data = $this->validate($request, [
            'name'  => ['required', 'max:160'],
            'email' => ['required', 'email', 'max:190'],
            'farm'  => ['required', 'max:160'],
            'message' => ['max:2000'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        if ($this->isHoneypotTripped($request)) {
            Flash::success('Thanks — we’ll reach out to schedule your demo.');
            return $this->back($request, '/');
        }

        $this->inquiries->createDemo([
            'name'    => trim((string) $data['name']),
            'email'   => mb_strtolower(trim((string) $data['email'])),
            'farm'    => trim((string) $data['farm']),
            'message' => ($data['message'] ?? '') !== '' ? trim((string) $data['message']) : null,
        ]);

        AuditLog::record('public.demo_requested', 'system', null, null, null, null, [], $request->ip());
        Flash::success('Thanks — we’ll reach out to schedule your demo.');
        return $this->back($request, '/');
    }

    private function isHoneypotTripped(Request $request): bool
    {
        return trim((string) $request->input('website', '')) !== '';
    }
}
