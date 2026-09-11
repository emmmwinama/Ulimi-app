<?php

declare(strict_types=1);

namespace App\Controllers\Farm;

use App\Controllers\Controller;
use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\FarmContext;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\DocumentRepository;
use App\Services\Upload;
use Throwable;

final class DocumentsController extends Controller
{
    private const TYPES = ['deed', 'certificate', 'receipt', 'contract', 'photo', 'other'];

    public function __construct(private readonly DocumentRepository $documents = new DocumentRepository())
    {
    }

    public function index(Request $request): Response
    {
        $ctx = FarmContext::current();
        $type = (string) $request->query('type', '');
        $all = $this->documents->forFarm($ctx->farmId(), null);
        $counts = array_fill_keys(self::TYPES, 0);
        foreach ($all as $d) {
            $counts[$d['type']] = ($counts[$d['type']] ?? 0) + 1;
        }
        return $this->view('documents/index', [
            'title'     => 'Documents',
            'active'    => 'documents',
            'documents' => $type === '' ? $all : array_values(array_filter($all, static fn ($d) => $d['type'] === $type)),
            'types'     => self::TYPES,
            'type'      => $type,
            'counts'    => $counts,
            'total'     => count($all),
            'canManage' => $ctx->can('documents.manage') && !$ctx->isReadOnly(),
        ]);
    }

    public function store(Request $request): Response
    {
        $ctx = FarmContext::current();

        $data = $this->validate($request, [
            'name'  => ['required', 'max:200'],
            'type'  => ['required', 'in:' . implode(',', self::TYPES)],
            'notes' => ['max:500'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        $file = $request->file('document');
        if ($file === null) {
            return $this->fieldError($request, 'document', 'Choose a file to upload.');
        }

        try {
            $stored = Upload::store($file);
        } catch (Throwable $e) {
            return $this->fieldError($request, 'document', $e->getMessage());
        }

        $id = $this->documents->create($ctx->farmId(), (string) Auth::id(), [
            'name'      => trim((string) $data['name']),
            'type'      => (string) $data['type'],
            'asset_id'  => $stored['asset_id'],
            'mime_type' => $stored['mime_type'],
            'size'      => $stored['size'],
            'notes'     => isset($data['notes']) && $data['notes'] !== '' ? trim((string) $data['notes']) : null,
        ]);

        AuditLog::user('document.uploaded', (string) Auth::id(), ['document_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Document uploaded.');
        return $this->redirect(url('documents'));
    }

    public function download(Request $request): Response
    {
        $ctx = FarmContext::current();
        $doc = $this->documents->find($ctx->farmId(), (string) $request->route('id'));
        if ($doc === null) {
            return $this->view('errors/404', [], 404);
        }

        $path = Upload::path((string) $doc['asset_id']);
        if (!is_file($path)) {
            Flash::error('That file is missing from storage.');
            return $this->redirect(url('documents'));
        }

        AuditLog::user('document.downloaded', (string) Auth::id(), ['document_id' => $doc['id']], $ctx->farmId(), $request->ip());

        $body = (string) file_get_contents($path);
        $ext = pathinfo((string) $doc['asset_id'], PATHINFO_EXTENSION);
        $filename = self::asciiFilename((string) $doc['name']);
        if ($ext !== '' && !str_ends_with(strtolower($filename), '.' . strtolower($ext))) {
            $filename .= '.' . $ext;
        }

        return Response::make($body, 200)
            ->header('Content-Type', (string) $doc['mime_type'])
            ->header('Content-Length', (string) strlen($body))
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Cache-Control', 'private, no-store');
    }

    public function destroy(Request $request): Response
    {
        $ctx = FarmContext::current();
        $doc = $this->documents->find($ctx->farmId(), (string) $request->route('id'));
        if ($doc === null) {
            Flash::error('Document not found.');
            return $this->redirect(url('documents'));
        }
        $this->documents->delete($ctx->farmId(), (string) $doc['id']);
        Upload::delete((string) $doc['asset_id']);
        AuditLog::user('document.deleted', (string) Auth::id(), ['document_id' => $doc['id']], $ctx->farmId(), $request->ip());
        Flash::success('Document deleted.');
        return $this->redirect(url('documents'));
    }

    private static function asciiFilename(string $name): string
    {
        $ascii = preg_replace('/[^\x20-\x7E]/', '_', $name) ?? 'document';
        return str_replace('"', "'", $ascii);
    }
}
