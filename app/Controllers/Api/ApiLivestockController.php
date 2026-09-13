<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\AuditLog;
use App\Core\Database;
use App\Core\FarmContext;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Repositories\DocumentRepository;
use App\Repositories\LivestockRepository;
use App\Repositories\TransactionRepository;
use App\Services\Upload;
use Throwable;

final class ApiLivestockController
{
    private const SEX = ['Female', 'Male', 'Unknown'];
    private const STATUS = ['Active', 'Sold', 'Dead', 'Culled', 'Lost'];
    private const ACQ = ['Born on farm', 'Purchased', 'Gift', 'Exchange', 'Other'];
    private const EVENT_TABLES = [
        'health'     => 'animal_health',
        'production' => 'animal_production',
        'weight'     => 'animal_weight',
        'expense'    => 'animal_expenses',
    ];

    public function __construct(
        private readonly LivestockRepository $repo = new LivestockRepository(),
        private readonly TransactionRepository $tx = new TransactionRepository(),
        private readonly DocumentRepository $documents = new DocumentRepository(),
    ) {
    }

    /* --------------------------------------------------------------- types */

    public function types(Request $request): Response
    {
        return Response::json(['data' => $this->repo->types(FarmContext::current()->farmId())]);
    }

    public function storeType(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('livestock.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }
        $v = Validator::make($request->all(), ['name' => ['required', 'max:80'], 'category' => ['max:60']]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();
        $id = $this->repo->createType($ctx->farmId(), trim((string) $d['name']), trim((string) ($d['category'] ?? '')), 'cow');
        AuditLog::user('api.livestock.type_created', (string) ApiAuth::id(), ['type_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->repo->findType($ctx->farmId(), $id)], 201);
    }

    public function destroyType(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('livestock.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }
        $id = (string) $request->route('id');
        if ($this->repo->findType($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Type not found.'], 404);
        }
        if ($this->repo->typeHasAnimals($ctx->farmId(), $id)) {
            return Response::json(['error' => 'Move or remove this type\'s animals first.'], 422);
        }
        $this->repo->deleteType($ctx->farmId(), $id);
        AuditLog::user('api.livestock.type_deleted', (string) ApiAuth::id(), ['type_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => true]);
    }

    /* ------------------------------------------------------------- animals */

    public function animals(Request $request): Response
    {
        $ctx = FarmContext::current();
        $data = $this->repo->animals($ctx->farmId(), [
            'type_id' => (string) $request->query('type_id', ''),
            'status'  => (string) $request->query('status', ''),
        ]);
        return Response::json(['data' => $data]);
    }

    public function showAnimal(Request $request): Response
    {
        $ctx = FarmContext::current();
        $animal = $this->repo->findAnimal($ctx->farmId(), (string) $request->route('id'));
        if ($animal === null) {
            return Response::json(['error' => 'Animal not found.'], 404);
        }
        return Response::json(['data' => $animal]);
    }

    public function storeAnimal(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('livestock.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }
        $data = $this->validatedAnimal($request);
        if ($data instanceof Response) {
            return $data;
        }
        $id = $this->repo->createAnimal($ctx->farmId(), $data);
        AuditLog::user('api.livestock.animal_created', (string) ApiAuth::id(), ['animal_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->repo->findAnimal($ctx->farmId(), $id)], 201);
    }

    public function updateAnimal(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('livestock.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }
        $id = (string) $request->route('id');
        if ($this->repo->findAnimal($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Animal not found.'], 404);
        }
        $data = $this->validatedAnimal($request);
        if ($data instanceof Response) {
            return $data;
        }
        // livestock_type_id is immutable after create, matching the web controller.
        unset($data['livestock_type_id']);
        $this->repo->updateAnimal($ctx->farmId(), $id, $data);
        AuditLog::user('api.livestock.animal_updated', (string) ApiAuth::id(), ['animal_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->repo->findAnimal($ctx->farmId(), $id)]);
    }

    public function destroyAnimal(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('livestock.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }
        $id = (string) $request->route('id');
        if ($this->repo->findAnimal($ctx->farmId(), $id) === null) {
            return Response::json(['error' => 'Animal not found.'], 404);
        }
        $this->repo->deleteAnimal($ctx->farmId(), $id);
        AuditLog::user('api.livestock.animal_deleted', (string) ApiAuth::id(), ['animal_id' => $id], $ctx->farmId(), $request->ip());
        return Response::json(['data' => true]);
    }

    /* ------------------------------------------------------------- events */

    public function events(Request $request): Response
    {
        $ctx = FarmContext::current();
        $animalId = (string) $request->route('id');
        $kind = (string) $request->route('kind');
        if (!isset(self::EVENT_TABLES[$kind]) || $this->repo->findAnimal($ctx->farmId(), $animalId) === null) {
            return Response::json(['error' => 'Not found.'], 404);
        }
        return Response::json(['data' => $this->repo->events(self::EVENT_TABLES[$kind], $ctx->farmId(), $animalId)]);
    }

    public function addEvent(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('livestock.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }
        $animalId = (string) $request->route('id');
        $kind = (string) $request->route('kind');

        if ($this->repo->findAnimal($ctx->farmId(), $animalId) === null || !isset(self::EVENT_TABLES[$kind])) {
            return Response::json(['error' => 'Not found.'], 404);
        }

        $row = $this->eventRow($request, $kind);
        if ($row instanceof Response) {
            return $row;
        }

        $eventId = $this->repo->addEvent(self::EVENT_TABLES[$kind], $ctx->farmId(), $animalId, $row);

        if ($kind === 'weight') {
            $this->repo->updateAnimal($ctx->farmId(), $animalId, ['weight' => $row['weight']]);
        }
        if ($kind === 'health') {
            $this->attachHealthMedia($request, $ctx->farmId(), $eventId);
        }

        AuditLog::user('api.livestock.event_added', (string) ApiAuth::id(), ['animal_id' => $animalId, 'kind' => $kind], $ctx->farmId(), $request->ip());
        return Response::json(['data' => ['id' => $eventId]], 201);
    }

    public function deleteEvent(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('livestock.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }
        $kind = (string) $request->route('kind');
        $eventId = (string) $request->route('eventId');

        $table = self::EVENT_TABLES[$kind] ?? null;
        if ($table === null) {
            return Response::json(['error' => 'Unknown record type.'], 422);
        }
        if ($kind === 'health') {
            foreach ($this->documents->forLinked($ctx->farmId(), 'animal_health', $eventId) as $doc) {
                Upload::delete((string) $doc['asset_id']);
                $this->documents->delete($ctx->farmId(), (string) $doc['id']);
            }
        }
        $this->repo->deleteEvent($table, $ctx->farmId(), $eventId);
        AuditLog::user('api.livestock.event_deleted', (string) ApiAuth::id(), ['event_id' => $eventId, 'kind' => $kind], $ctx->farmId(), $request->ip());
        return Response::json(['data' => true]);
    }

    /* --------------------------------------------------------------- sale */

    public function sellAnimal(Request $request): Response
    {
        $ctx = FarmContext::current();
        if (!$ctx->can('livestock.manage') || $ctx->isReadOnly()) {
            return Response::json(['error' => 'Not permitted.'], 403);
        }
        $animalId = (string) $request->route('id');
        $animal = $this->repo->findAnimal($ctx->farmId(), $animalId);
        if ($animal === null) {
            return Response::json(['error' => 'Animal not found.'], 404);
        }

        $v = Validator::make($request->all(), [
            'sale_date'      => ['required', 'date'],
            'total_amount'   => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'quantity'       => ['integer', 'min:1', 'max:100000'],
            'weight_at_sale' => ['numeric', 'min:0', 'max:100000'],
            'price_per_kg'   => ['numeric', 'min:0', 'max:100000000'],
            'buyer'          => ['max:160'],
            'notes'          => ['max:500'],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $data = $v->validated();

        $total = round((float) $data['total_amount'], 2);
        $saleDate = date('Y-m-d', (int) strtotime((string) $data['sale_date']));

        Database::instance()->transaction(function () use ($ctx, $animal, $animalId, $data, $total, $saleDate): void {
            $txId = $this->tx->create($ctx->farmId(), (string) ApiAuth::id(), [
                'type'        => 'Income',
                'category'    => 'Livestock sales',
                'amount'      => $total,
                'date'        => $saleDate,
                'description' => 'Livestock sale: ' . trim(((string) ($animal['tag'] ?? '')) . ' ' . ((string) ($animal['type_name'] ?? 'animal')))
                                 . (($data['buyer'] ?? '') !== '' ? ' to ' . trim((string) $data['buyer']) : ''),
                'source'      => 'inventory_sale',
            ]);

            $this->repo->addSale($ctx->farmId(), $animalId, [
                'sale_date'      => $saleDate,
                'quantity'       => (int) ($data['quantity'] ?? 1),
                'weight_at_sale' => isset($data['weight_at_sale']) && $data['weight_at_sale'] !== '' ? (float) $data['weight_at_sale'] : null,
                'price_per_kg'   => isset($data['price_per_kg']) && $data['price_per_kg'] !== '' ? (float) $data['price_per_kg'] : null,
                'total_amount'   => $total,
                'buyer'          => ($data['buyer'] ?? '') !== '' ? trim((string) $data['buyer']) : null,
                'notes'          => ($data['notes'] ?? '') !== '' ? trim((string) $data['notes']) : null,
            ], $txId);

            $this->repo->updateAnimal($ctx->farmId(), $animalId, ['status' => 'Sold']);
        });

        AuditLog::user('api.livestock.sold', (string) ApiAuth::id(), ['animal_id' => $animalId, 'total' => $total], $ctx->farmId(), $request->ip());
        return Response::json(['data' => $this->repo->findAnimal($ctx->farmId(), $animalId)]);
    }

    /* ------------------------------------------------------------ helpers */

    /** @return array<string,mixed>|Response */
    private function validatedAnimal(Request $request): array|Response
    {
        $ctx = FarmContext::current();
        $v = Validator::make($request->all(), [
            'livestock_type_id' => ['required'],
            'tag'               => ['max:60'],
            'name'              => ['max:80'],
            'animal_group'      => ['max:80'],
            'sex'               => ['required', 'in:' . implode(',', self::SEX)],
            'birth_date'        => ['date'],
            'acquisition_date'  => ['required', 'date'],
            'acquisition_type'  => ['required', 'in:' . implode(',', self::ACQ)],
            'acquisition_cost'  => ['numeric', 'min:0', 'max:1000000000'],
            'status'            => ['required', 'in:' . implode(',', self::STATUS)],
            'breed'             => ['max:80'],
            'colour'            => ['max:60'],
            'weight'            => ['numeric', 'min:0', 'max:100000'],
            'notes'             => ['max:500'],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $data = $v->validated();

        if ($this->repo->findType($ctx->farmId(), (string) $data['livestock_type_id']) === null) {
            return Response::json(['errors' => ['livestock_type_id' => ['Choose a valid type.']]], 422);
        }
        $parentId = (string) $request->input('parent_id', '');
        if ($parentId !== '' && $this->repo->findAnimal($ctx->farmId(), $parentId) === null) {
            $parentId = '';
        }

        $opt = static fn (string $k) => isset($data[$k]) && $data[$k] !== '' ? trim((string) $data[$k]) : null;
        $optNum = static fn (string $k) => isset($data[$k]) && $data[$k] !== '' ? (float) $data[$k] : null;
        $optDate = static fn (string $k) => isset($data[$k]) && $data[$k] !== '' ? date('Y-m-d', (int) strtotime((string) $data[$k])) : null;

        return [
            'livestock_type_id' => (string) $data['livestock_type_id'],
            'tag'               => $opt('tag'),
            'name'              => $opt('name'),
            'animal_group'      => $opt('animal_group'),
            'sex'               => (string) $data['sex'],
            'birth_date'        => $optDate('birth_date'),
            'acquisition_date'  => date('Y-m-d', (int) strtotime((string) $data['acquisition_date'])),
            'acquisition_type'  => (string) $data['acquisition_type'],
            'acquisition_cost'  => $optNum('acquisition_cost'),
            'status'            => (string) $data['status'],
            'breed'             => $opt('breed'),
            'colour'            => $opt('colour'),
            'weight'            => $optNum('weight'),
            'notes'             => $opt('notes'),
            'parent_id'         => $parentId ?: null,
        ];
    }

    /** @return array<string,scalar|null>|Response */
    private function eventRow(Request $request, string $kind): array|Response
    {
        return match ($kind) {
            'health' => $this->buildHealth($request),
            'production' => $this->buildProduction($request),
            'weight' => $this->buildWeight($request),
            'expense' => $this->buildExpense($request),
            default => Response::json(['error' => 'Unknown record type.'], 422),
        };
    }

    /** @return array<string,scalar|null>|Response */
    private function buildHealth(Request $request): array|Response
    {
        $v = Validator::make($request->all(), [
            'type'          => ['required', 'max:60'],
            'description'   => ['required', 'max:255'],
            'veterinarian'  => ['max:120'],
            'cost'          => ['numeric', 'min:0', 'max:100000000'],
            'date'          => ['required', 'date'],
            'next_due_date' => ['date'],
            'notes'         => ['max:500'],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();
        return [
            'type' => trim((string) $d['type']),
            'description' => trim((string) $d['description']),
            'veterinarian' => ($d['veterinarian'] ?? '') !== '' ? trim((string) $d['veterinarian']) : null,
            'cost' => round((float) ($d['cost'] ?? 0), 2),
            'date' => date('Y-m-d', (int) strtotime((string) $d['date'])),
            'next_due_date' => ($d['next_due_date'] ?? '') !== '' ? date('Y-m-d', (int) strtotime((string) $d['next_due_date'])) : null,
            'notes' => ($d['notes'] ?? '') !== '' ? trim((string) $d['notes']) : null,
        ];
    }

    /** @return array<string,scalar|null>|Response */
    private function buildProduction(Request $request): array|Response
    {
        $v = Validator::make($request->all(), [
            'type'           => ['required', 'max:60'],
            'quantity'       => ['required', 'numeric', 'min:0', 'max:100000000'],
            'unit'           => ['required', 'max:20'],
            'date'           => ['required', 'date'],
            'price_per_unit' => ['numeric', 'min:0', 'max:100000000'],
            'notes'          => ['max:500'],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();
        $qty = (float) $d['quantity'];
        $ppu = ($d['price_per_unit'] ?? '') !== '' ? (float) $d['price_per_unit'] : null;
        return [
            'type' => trim((string) $d['type']),
            'quantity' => $qty,
            'unit' => trim((string) $d['unit']),
            'date' => date('Y-m-d', (int) strtotime((string) $d['date'])),
            'price_per_unit' => $ppu,
            'total_value' => $ppu !== null ? round($qty * $ppu, 2) : null,
            'notes' => ($d['notes'] ?? '') !== '' ? trim((string) $d['notes']) : null,
        ];
    }

    /** @return array<string,scalar|null>|Response */
    private function buildWeight(Request $request): array|Response
    {
        $v = Validator::make($request->all(), [
            'weight' => ['required', 'numeric', 'min:0', 'max:100000'],
            'unit'   => ['required', 'in:kg,lb'],
            'date'   => ['required', 'date'],
            'notes'  => ['max:300'],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();
        return [
            'weight' => (float) $d['weight'],
            'unit' => (string) $d['unit'],
            'date' => date('Y-m-d', (int) strtotime((string) $d['date'])),
            'notes' => ($d['notes'] ?? '') !== '' ? trim((string) $d['notes']) : null,
        ];
    }

    /** @return array<string,scalar|null>|Response */
    private function buildExpense(Request $request): array|Response
    {
        $v = Validator::make($request->all(), [
            'category'    => ['required', 'max:60'],
            'description' => ['required', 'max:200'],
            'amount'      => ['required', 'numeric', 'min:0', 'max:100000000'],
            'date'        => ['required', 'date'],
            'notes'       => ['max:500'],
        ]);
        if ($v->fails()) {
            return Response::json(['errors' => $v->errors()], 422);
        }
        $d = $v->validated();
        return [
            'category' => trim((string) $d['category']),
            'description' => trim((string) $d['description']),
            'amount' => round((float) $d['amount'], 2),
            'date' => date('Y-m-d', (int) strtotime((string) $d['date'])),
            'notes' => ($d['notes'] ?? '') !== '' ? trim((string) $d['notes']) : null,
        ];
    }

    /** Optional photo/voice-note attachment on a health record — additive, never blocks saving the record itself. */
    private function attachHealthMedia(Request $request, string $farmId, string $healthId): void
    {
        $file = $request->file('attachment');
        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return;
        }
        try {
            $stored = Upload::store($file);
        } catch (Throwable) {
            return;
        }
        $this->documents->create($farmId, (string) ApiAuth::id(), [
            'name'        => 'Health record attachment — ' . date('Y-m-d'),
            'type'        => str_starts_with($stored['mime_type'], 'audio/') ? 'other' : 'photo',
            'asset_id'    => $stored['asset_id'],
            'mime_type'   => $stored['mime_type'],
            'size'        => $stored['size'],
            'linked_to'   => $healthId,
            'linked_type' => 'animal_health',
        ]);
    }
}
