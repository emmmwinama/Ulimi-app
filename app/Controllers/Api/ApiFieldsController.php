<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\AuditLog;
use App\Core\ApiAuth;
use App\Core\FarmContext;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Repositories\CropFieldRepository;
use App\Repositories\FieldRepository;

final class ApiFieldsController
{
    public function __construct(
        private readonly FieldRepository $fields = new FieldRepository(),
        private readonly CropFieldRepository $crops = new CropFieldRepository(),
    ) {
    }

    public function index(Request $request): Response
    {
        return Response::json(['data' => $this->fields->forFarm(FarmContext::current()->farmId())]);
    }

    public function store(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('fields.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $v = Validator::make($request->all(), [
            'name' => ['required', 'max:160'],
            'total_area' => ['required', 'numeric', 'min:0'],
            'cultivatable_area' => ['required', 'numeric', 'min:0'],
            'soil_type' => ['required', 'max:80'],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();

        $id = $this->fields->create($ctx->farmId(), [
            'name' => (string) $d['name'],
            'total_area' => (float) $d['total_area'],
            'cultivatable_area' => (float) $d['cultivatable_area'],
            'soil_type' => (string) $d['soil_type'],
            'location_lat' => $request->input('location_lat') !== null ? (float) $request->input('location_lat') : null,
            'location_lng' => $request->input('location_lng') !== null ? (float) $request->input('location_lng') : null,
            'notes' => $request->input('notes'),
        ]);

        AuditLog::user('api.field.created', (string) ApiAuth::id(), ['field_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->fields->find($ctx->farmId(), $id)], 201);
    }

    public function update(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('fields.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        if ($this->fields->find($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Not found.'], 404);
        }

        $v = Validator::make($request->all(), [
            'name' => ['required', 'max:160'],
            'total_area' => ['required', 'numeric', 'min:0'],
            'cultivatable_area' => ['required', 'numeric', 'min:0'],
            'soil_type' => ['required', 'max:80'],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();

        $this->fields->update($ctx->farmId(), $id, [
            'name' => (string) $d['name'],
            'total_area' => (float) $d['total_area'],
            'cultivatable_area' => (float) $d['cultivatable_area'],
            'soil_type' => (string) $d['soil_type'],
            'location_lat' => $request->input('location_lat') !== null ? (float) $request->input('location_lat') : null,
            'location_lng' => $request->input('location_lng') !== null ? (float) $request->input('location_lng') : null,
            'notes' => $request->input('notes'),
        ]);

        AuditLog::user('api.field.updated', (string) ApiAuth::id(), ['field_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->fields->find($ctx->farmId(), $id)]);
    }

    public function destroy(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('fields.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $id = (string) $request->route('id');
        if ($this->fields->find($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Not found.'], 404);
        }
        if ($this->crops->forFarm($ctx->farmId(), ['field_id' => $id]) !== []) {
            return Response::json(['error' => 'Remove or reassign this field’s crop plantings before deleting it.'], 422);
        }

        $this->fields->delete($ctx->farmId(), $id);
        AuditLog::user('api.field.deleted', (string) ApiAuth::id(), ['field_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => true]);
    }
}
