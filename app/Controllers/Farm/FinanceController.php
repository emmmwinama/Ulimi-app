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
use App\Repositories\CropFieldRepository;
use App\Repositories\FieldRepository;
use App\Repositories\OverheadRepository;
use App\Repositories\TransactionRepository;
use App\Services\FinanceSummary;

final class FinanceController extends Controller
{
    private const INCOME_CATEGORIES  = ['Crop sales', 'Livestock sales', 'Grants', 'Loans', 'Other income'];
    private const EXPENSE_CATEGORIES = ['Seed', 'Fertiliser', 'Chemicals', 'Fuel', 'Labour', 'Transport', 'Equipment', 'Land rent', 'Other'];
    private const OVERHEAD_CATEGORIES = ['Salary', 'Rent', 'Utilities', 'Insurance', 'Loan', 'Other'];

    public function __construct(
        private readonly TransactionRepository $tx = new TransactionRepository(),
        private readonly OverheadRepository $overheads = new OverheadRepository(),
        private readonly CropFieldRepository $crops = new CropFieldRepository(),
        private readonly FieldRepository $fields = new FieldRepository(),
        private readonly FinanceSummary $summary = new FinanceSummary(),
    ) {
    }

    /* --------------------------------------------------------------- overview */

    public function overview(Request $request): Response
    {
        $ctx = FarmContext::current();
        $season = (string) $request->query('season', '');

        $totals = $this->summary->totals($ctx->farmId(), $season ?: null);
        $yieldKg = $this->summary->totalYieldKg($ctx->farmId(), $season ?: null);
        $area = $this->summary->cultivatableArea($ctx->farmId());

        return $this->view('finance/overview', [
            'title'        => 'Finance',
            'active'       => 'finance',
            'season'       => $season,
            'seasons'      => $this->crops->seasons($ctx->farmId()),
            'totals'       => $totals,
            'trend'        => $this->summary->monthlyTrend($ctx->farmId()),
            'costPerHa'    => $area > 0 ? $totals['total_cost'] / $area : null,
            'costPerKg'    => $yieldKg > 0 ? $totals['total_cost'] / $yieldKg : null,
            'yieldKg'      => $yieldKg,
            'showPerHa'    => $ctx->feature('cost_per_hectare'),
            'showAnalytics'=> $ctx->feature('season_analytics'),
        ]);
    }

    /* ----------------------------------------------------------- transactions */

    public function transactions(Request $request): Response
    {
        $ctx = FarmContext::current();
        $filters = [
            'type'   => (string) $request->query('type', ''),
            'season' => (string) $request->query('season', ''),
        ];
        $canManage = $ctx->can('finance.manage') && !$ctx->isReadOnly();

        return $this->view('finance/transactions', [
            'title'     => 'Transactions',
            'active'    => 'finance',
            'rows'      => $this->tx->forFarm($ctx->farmId(), $filters),
            'seasons'   => $this->crops->seasons($ctx->farmId()),
            'filters'   => $filters,
            'canManage' => $canManage,
            'txFields'    => $canManage ? $this->fields->forFarm($ctx->farmId()) : [],
            'txCrops'     => $canManage ? $this->crops->forFarm($ctx->farmId()) : [],
            'incomeCats'  => self::INCOME_CATEGORIES,
            'expenseCats' => self::EXPENSE_CATEGORIES,
        ]);
    }

    public function createTransaction(Request $request): Response
    {
        $ctx = FarmContext::current();
        return $this->view('finance/transaction-form', [
            'title'      => 'Add transaction',
            'active'     => 'finance',
            'row'        => null,
            'fields'     => $this->fields->forFarm($ctx->farmId()),
            'crops'      => $this->crops->forFarm($ctx->farmId()),
            'incomeCats' => self::INCOME_CATEGORIES,
            'expenseCats'=> self::EXPENSE_CATEGORIES,
        ]);
    }

    public function storeTransaction(Request $request): Response
    {
        $ctx = FarmContext::current();
        $data = $this->validatedTransaction($request);
        if ($data instanceof Response) {
            return $data;
        }
        $id = $this->tx->create($ctx->farmId(), (string) Auth::id(), $data);
        AuditLog::user('transaction.created', (string) Auth::id(), ['tx_id' => $id, 'amount' => $data['amount']], $ctx->farmId(), $request->ip());
        Flash::success('Transaction recorded.');
        return $this->redirect(url('finance/transactions'));
    }

    public function editTransaction(Request $request): Response
    {
        $ctx = FarmContext::current();
        $row = $this->tx->find($ctx->farmId(), (string) $request->route('id'));
        if ($row === null) {
            Flash::error('Transaction not found.');
            return $this->redirect(url('finance/transactions'));
        }
        if (($row['source'] ?? 'manual') !== 'manual') {
            Flash::error('This transaction was generated from an inventory sale and can’t be edited directly.');
            return $this->redirect(url('finance/transactions'));
        }
        return $this->view('finance/transaction-form', [
            'title'      => 'Edit transaction',
            'active'     => 'finance',
            'row'        => $row,
            'fields'     => $this->fields->forFarm($ctx->farmId()),
            'crops'      => $this->crops->forFarm($ctx->farmId()),
            'incomeCats' => self::INCOME_CATEGORIES,
            'expenseCats'=> self::EXPENSE_CATEGORIES,
        ]);
    }

    public function updateTransaction(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        $existing = $this->tx->find($ctx->farmId(), $id);
        if ($existing === null || ($existing['source'] ?? 'manual') !== 'manual') {
            Flash::error('Transaction not found or not editable.');
            return $this->redirect(url('finance/transactions'));
        }
        $data = $this->validatedTransaction($request);
        if ($data instanceof Response) {
            return $data;
        }
        $this->tx->update($ctx->farmId(), $id, $data);
        AuditLog::user('transaction.updated', (string) Auth::id(), ['tx_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Transaction updated.');
        return $this->redirect(url('finance/transactions'));
    }

    public function destroyTransaction(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->tx->find($ctx->farmId(), $id) === null) {
            Flash::error('Transaction not found.');
            return $this->redirect(url('finance/transactions'));
        }
        $this->tx->delete($ctx->farmId(), $id);
        AuditLog::user('transaction.deleted', (string) Auth::id(), ['tx_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Transaction deleted.');
        return $this->redirect(url('finance/transactions'));
    }

    /* -------------------------------------------------------------- overheads */

    public function overheadsIndex(Request $request): Response
    {
        $ctx = FarmContext::current();
        return $this->view('finance/overheads', [
            'title'     => 'Overheads',
            'active'    => 'finance',
            'rows'      => $this->overheads->forFarm($ctx->farmId()),
            'categories'=> self::OVERHEAD_CATEGORIES,
            'canManage' => $ctx->can('finance.manage') && !$ctx->isReadOnly(),
        ]);
    }

    public function storeOverhead(Request $request): Response
    {
        $ctx = FarmContext::current();
        $data = $this->validate($request, [
            'description' => ['required', 'max:200'],
            'category'    => ['required', 'in:' . implode(',', self::OVERHEAD_CATEGORIES)],
            'amount'      => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'date'        => ['required', 'date'],
            'notes'       => ['max:500'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }
        $id = $this->overheads->create($ctx->farmId(), [
            'description' => trim((string) $data['description']),
            'category'    => (string) $data['category'],
            'amount'      => (float) $data['amount'],
            'date'        => date('Y-m-d', (int) strtotime((string) $data['date'])),
            'recurring'   => $request->boolean('recurring') ? 1 : 0,
            'notes'       => isset($data['notes']) && $data['notes'] !== '' ? trim((string) $data['notes']) : null,
        ]);
        AuditLog::user('overhead.created', (string) Auth::id(), ['overhead_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Overhead added.');
        return $this->redirect(url('finance/overheads'));
    }

    public function destroyOverhead(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->overheads->find($ctx->farmId(), $id) === null) {
            Flash::error('Overhead not found.');
            return $this->redirect(url('finance/overheads'));
        }
        $this->overheads->delete($ctx->farmId(), $id);
        AuditLog::user('overhead.deleted', (string) Auth::id(), ['overhead_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Overhead deleted.');
        return $this->redirect(url('finance/overheads'));
    }

    /* ---------------------------------------------------------------- helpers */

    /** @return array<string,mixed>|Response */
    private function validatedTransaction(Request $request): array|Response
    {
        $ctx = FarmContext::current();
        $data = $this->validate($request, [
            'type'           => ['required', 'in:Income,Expense'],
            'category'       => ['required', 'max:80'],
            'payment_status' => ['required', 'in:unpaid,partial,paid'],
            'amount'         => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'date'           => ['required', 'date'],
            'description'    => ['required', 'max:255'],
            'season'         => ['max:60'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        $fieldId = (string) $request->input('field_id', '');
        if ($fieldId !== '' && $this->fields->find($ctx->farmId(), $fieldId) === null) {
            $fieldId = '';
        }
        $cropFieldId = (string) $request->input('crop_field_id', '');
        if ($cropFieldId !== '' && $this->crops->find($ctx->farmId(), $cropFieldId) === null) {
            $cropFieldId = '';
        }

        return [
            'type'         => (string) $data['type'],
            'category'     => trim((string) $data['category']),
            'payment_status' => (string) $data['payment_status'],
            'amount'       => (float) $data['amount'],
            'date'         => date('Y-m-d', (int) strtotime((string) $data['date'])),
            'description'  => trim((string) $data['description']),
            'season'       => isset($data['season']) && $data['season'] !== '' ? trim((string) $data['season']) : null,
            'field_id'     => $fieldId ?: null,
            'crop_field_id'=> $cropFieldId ?: null,
            'source'       => 'manual',
        ];
    }
}
