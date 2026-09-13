<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\AuditLog;
use App\Core\FarmContext;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Repositories\DocumentRepository;
use App\Services\Upload;
use Throwable;

final class ApiDocumentsController
{
    private const TYPES = ['deed', 'certificate', 'receipt', 'contract', 'photo', 'other'];

    public function __construct(private readonly DocumentRepository $documents = new DocumentRepository())
    {
    }

    public function index(Request $request): Response
    {
        $type = (string) $request->query('type', '');
        $all = $this->documents->forFarm(FarmContext::current()->farmId(), $type !== '' ? $type : null);
        return Response::json(['data' => $all]);
    }

    public function store(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('documents.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $v = Validator::make($request->all(), [
            'name' => ['required', 'max:200'],
            'type' => ['required', 'in:' . implode(',', self::TYPES)],
            'notes' => ['max:500'],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();

        $file = $request->file('document');
        if ($file === null) {
            return Response::json(['errors' => ['document' => ['Choose a file to upload.']]], 422);
        }

        try {
            $stored = Upload::store($file);
        } catch (Throwable $e) {
            return Response::json(['errors' => ['document' => [$e->getMessage()]]], 422);
        }

        $id = $this->documents->create($ctx->farmId(), (string) ApiAuth::id(), [
            'name' => trim((string) $d['name']),
            'type' => (string) $d['type'],
            'asset_id' => $stored['asset_id'],
            'mime_type' => $stored['mime_type'],
            'size' => $stored['size'],
            'notes' => isset($d['notes']) && $d['notes'] !== '' ? trim((string) $d['notes']) : null,
        ]);

        AuditLog::user('api.document.uploaded', (string) ApiAuth::id(), ['document_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->documents->find($ctx->farmId(), $id)], 201);
    }

    public function download(Request $request): Response
    {
        $ctx = FarmContext::current();
        $doc = $this->documents->find($ctx->farmId(), (string) $request->route('id'));
        if ($doc === null) {
            return Response::json(['error' => 'Document not found.'], 404);
        }

        $path = Upload::path((string) $doc['asset_id']);
        if (!is_file($path)) {
            return Response::json(['error' => 'That file is missing from storage.'], 404);
        }

        AuditLog::user('api.document.downloaded', (string) ApiAuth::id(), ['document_id' => $doc['id']], $ctx->farmId(), $request->ip());

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
        if (!$ctx->can('documents.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $doc = $this->documents->find($ctx->farmId(), (string) $request->route('id'));
        if ($doc === null) {
            return Response::json(['error' => 'Document not found.'], 404);
        }

        $this->documents->delete($ctx->farmId(), (string) $doc['id']);
        Upload::delete((string) $doc['asset_id']);
        AuditLog::user('api.document.deleted', (string) ApiAuth::id(), ['document_id' => $doc['id']], $ctx->farmId(), $request->ip());
        return Response::json(['data' => true]);
    }

    private static function asciiFilename(string $name): string
    {
        $ascii = preg_replace('/[^\x20-\x7E]/', '_', $name) ?? 'document';
        return str_replace('"', "'", $ascii);
    }
}
