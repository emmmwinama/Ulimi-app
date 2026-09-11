<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\AuditLog;
use App\Core\FarmContext;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Repositories\TransactionRepository;

final class ApiFinanceController
{
    public function __construct(private readonly TransactionRepository $tx = new TransactionRepository())
    {
    }

    public function index(Request $request): Response
    {
        return Response::json(['data' => $this->tx->forFarm(FarmContext::current()->farmId())]);
    }

    public function store(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('finance.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }

        $v = Validator::make($request->all(), [
            'type' => ['required', 'in:Income,Expense'],
            'category' => ['required', 'max:80'],
            'amount' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'description' => ['required', 'max:255'],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();

        $id = $this->tx->create($ctx->farmId(), (string) ApiAuth::id(), [
            'type' => (string) $d['type'],
            'category' => (string) $d['category'],
            'amount' => (float) $d['amount'],
            'date' => date('Y-m-d', (int) strtotime((string) $d['date'])),
            'description' => (string) $d['description'],
            'source' => 'manual',
        ]);

        AuditLog::user('api.transaction.created', (string) ApiAuth::id(), ['tx_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->tx->find($ctx->farmId(), $id)], 201);
    }
}
