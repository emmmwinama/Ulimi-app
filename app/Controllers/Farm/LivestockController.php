<?php

declare(strict_types=1);

namespace App\Controllers\Farm;

use App\Controllers\Controller;
use App\Core\AuditLog;
use App\Core\Auth;
use App\Core\Database;
use App\Core\FarmContext;
use App\Core\Flash;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\LivestockRepository;
use App\Repositories\TransactionRepository;
use App\Services\LivestockStats;

final class LivestockController extends Controller
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
        private readonly LivestockStats $stats = new LivestockStats(),
    ) {
    }

    public function index(Request $request): Response
    {
        $ctx = FarmContext::current();
        return $this->view('livestock/index', [
            'title'     => 'Livestock',
            'active'    => 'livestock',
            'stats'     => $this->stats->forFarm($ctx->farmId()),
            'types'     => $this->repo->types($ctx->farmId()),
            'animals'   => $this->repo->animals($ctx->farmId(), ['status' => (string) $request->query('status', '')]),
            'statusF'   => (string) $request->query('status', ''),
            'canManage' => $ctx->can('livestock.manage') && !$ctx->isReadOnly(),
        ]);
    }

    /* --------------------------------------------------------------- types */

    public function storeType(Request $request): Response
    {
        $ctx = FarmContext::current();
        $data = $this->validate($request, [
            'name'     => ['required', 'max:80'],
            'category' => ['max:60'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }
        $id = $this->repo->createType(
            $ctx->farmId(),
            trim((string) $data['name']),
            trim((string) ($data['category'] ?? '')),
            'cow',
        );
        AuditLog::user('livestock.type_created', (string) Auth::id(), ['type_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Livestock type added.');
        return $this->redirect(url('livestock'));
    }

    public function destroyType(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->repo->findType($ctx->farmId(), $id) === null) {
            Flash::error('Type not found.');
            return $this->redirect(url('livestock'));
        }
        if ($this->repo->typeHasAnimals($ctx->farmId(), $id)) {
            Flash::error('Move or remove this type’s animals first.');
            return $this->redirect(url('livestock'));
        }
        $this->repo->deleteType($ctx->farmId(), $id);
        Flash::success('Type removed.');
        return $this->redirect(url('livestock'));
    }

    /* ------------------------------------------------------------- animals */

    public function createAnimal(Request $request): Response
    {
        $ctx = FarmContext::current();
        $types = $this->repo->types($ctx->farmId());
        if ($types === []) {
            Flash::info('Add a livestock type first.');
            return $this->redirect(url('livestock'));
        }
        return $this->animalForm($ctx, null);
    }

    public function storeAnimal(Request $request): Response
    {
        $ctx = FarmContext::current();
        $data = $this->validatedAnimal($request);
        if ($data instanceof Response) {
            return $data;
        }
        $id = $this->repo->createAnimal($ctx->farmId(), $data);
        AuditLog::user('livestock.animal_created', (string) Auth::id(), ['animal_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Animal added.');
        return $this->redirect(url('livestock/animals/' . $id));
    }

    public function showAnimal(Request $request): Response
    {
        $ctx = FarmContext::current();
        $animal = $this->repo->findAnimal($ctx->farmId(), (string) $request->route('id'));
        if ($animal === null) {
            Flash::error('Animal not found.');
            return $this->redirect(url('livestock'));
        }
        return $this->view('livestock/animal', [
            'title'      => trim(((string) ($animal['tag'] ?? '')) . ' ' . ((string) ($animal['name'] ?? ''))) ?: 'Animal',
            'active'     => 'livestock',
            'a'          => $animal,
            'health'     => $this->repo->events('animal_health', $ctx->farmId(), (string) $animal['id']),
            'production' => $this->repo->events('animal_production', $ctx->farmId(), (string) $animal['id']),
            'weights'    => $this->repo->events('animal_weight', $ctx->farmId(), (string) $animal['id']),
            'expenses'   => $this->repo->events('animal_expenses', $ctx->farmId(), (string) $animal['id']),
            'sales'      => $this->repo->events('animal_sales', $ctx->farmId(), (string) $animal['id']),
            'canManage'  => $ctx->can('livestock.manage') && !$ctx->isReadOnly(),
        ]);
    }

    public function editAnimal(Request $request): Response
    {
        $ctx = FarmContext::current();
        $animal = $this->repo->findAnimal($ctx->farmId(), (string) $request->route('id'));
        if ($animal === null) {
            Flash::error('Animal not found.');
            return $this->redirect(url('livestock'));
        }
        return $this->animalForm($ctx, $animal);
    }

    public function updateAnimal(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->repo->findAnimal($ctx->farmId(), $id) === null) {
            Flash::error('Animal not found.');
            return $this->redirect(url('livestock'));
        }
        $data = $this->validatedAnimal($request);
        if ($data instanceof Response) {
            return $data;
        }
        unset($data['livestock_type_id']);
        $this->repo->updateAnimal($ctx->farmId(), $id, $data);
        AuditLog::user('livestock.animal_updated', (string) Auth::id(), ['animal_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Animal updated.');
        return $this->redirect(url('livestock/animals/' . $id));
    }

    public function destroyAnimal(Request $request): Response
    {
        $ctx = FarmContext::current();
        $id = (string) $request->route('id');
        if ($this->repo->findAnimal($ctx->farmId(), $id) === null) {
            Flash::error('Animal not found.');
            return $this->redirect(url('livestock'));
        }
        $this->repo->deleteAnimal($ctx->farmId(), $id);
        AuditLog::user('livestock.animal_deleted', (string) Auth::id(), ['animal_id' => $id], $ctx->farmId(), $request->ip());
        Flash::success('Animal removed.');
        return $this->redirect(url('livestock'));
    }

    /* ------------------------------------------------------------- events */

    public function addEvent(Request $request): Response
    {
        $ctx = FarmContext::current();
        $animalId = (string) $request->route('id');
        $kind = (string) $request->route('kind');

        $animal = $this->repo->findAnimal($ctx->farmId(), $animalId);
        if ($animal === null || !isset(self::EVENT_TABLES[$kind])) {
            Flash::error('Not found.');
            return $this->redirect(url('livestock'));
        }

        $row = $this->eventRow($request, $kind);
        if ($row instanceof Response) {
            return $row;
        }

        $this->repo->addEvent(self::EVENT_TABLES[$kind], $ctx->farmId(), $animalId, $row);

        // A recorded weight also updates the animal's current weight.
        if ($kind === 'weight') {
            $this->repo->updateAnimal($ctx->farmId(), $animalId, ['weight' => $row['weight']]);
        }

        AuditLog::user('livestock.event_added', (string) Auth::id(), ['animal_id' => $animalId, 'kind' => $kind], $ctx->farmId(), $request->ip());
        Flash::success(ucfirst($kind) . ' record added.');
        return $this->redirect(url('livestock/animals/' . $animalId));
    }

    public function deleteEvent(Request $request): Response
    {
        $ctx = FarmContext::current();
        $animalId = (string) $request->route('id');
        $kind = (string) $request->route('kind');
        $eventId = (string) $request->route('eventId');

        $table = self::EVENT_TABLES[$kind] ?? ($kind === 'sale' ? 'animal_sales' : null);
        if ($table === null) {
            Flash::error('Unknown record type.');
            return $this->redirect(url('livestock/animals/' . $animalId));
        }
        $this->repo->deleteEvent($table, $ctx->farmId(), $eventId);
        Flash::success('Record removed.');
        return $this->redirect(url('livestock/animals/' . $animalId));
    }

    /* --------------------------------------------------------------- sale */

    public function sellAnimal(Request $request): Response
    {
        $ctx = FarmContext::current();
        $animalId = (string) $request->route('id');
        $animal = $this->repo->findAnimal($ctx->farmId(), $animalId);
        if ($animal === null) {
            Flash::error('Animal not found.');
            return $this->redirect(url('livestock'));
        }

        $data = $this->validate($request, [
            'sale_date'      => ['required', 'date'],
            'total_amount'   => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'quantity'       => ['integer', 'min:1', 'max:100000'],
            'weight_at_sale' => ['numeric', 'min:0', 'max:100000'],
            'price_per_kg'   => ['numeric', 'min:0', 'max:100000000'],
            'buyer'          => ['max:160'],
            'notes'          => ['max:500'],
        ]);
        if ($data instanceof Response) {
            return $data;
        }

        $total = round((float) $data['total_amount'], 2);
        $saleDate = date('Y-m-d', (int) strtotime((string) $data['sale_date']));

        Database::instance()->transaction(function () use ($ctx, $animal, $animalId, $data, $total, $saleDate): void {
            $txId = $this->tx->create($ctx->farmId(), (string) Auth::id(), [
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

        AuditLog::user('livestock.sold', (string) Auth::id(), ['animal_id' => $animalId, 'total' => $total], $ctx->farmId(), $request->ip());
        Flash::success('Sale recorded — animal marked Sold and an income transaction was created.');
        return $this->redirect(url('livestock/animals/' . $animalId));
    }

    /* ---------------------------------------------------------------- helpers */

    private function animalForm(FarmContext $ctx, ?array $animal): Response
    {
        return $this->view('livestock/animal-form', [
            'title'    => $animal === null ? 'Add animal' : 'Edit animal',
            'active'   => 'livestock',
            'a'        => $animal,
            'types'    => $this->repo->types($ctx->farmId()),
            'parents'  => $this->repo->animals($ctx->farmId(), ['status' => 'Active']),
            'sexes'    => self::SEX,
            'statuses' => self::STATUS,
            'acqTypes' => self::ACQ,
        ]);
    }

    /** @return array<string,mixed>|Response */
    private function validatedAnimal(Request $request): array|Response
    {
        $ctx = FarmContext::current();
        $data = $this->validate($request, [
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
        if ($data instanceof Response) {
            return $data;
        }
        if ($this->repo->findType($ctx->farmId(), (string) $data['livestock_type_id']) === null) {
            return $this->fieldError($request, 'livestock_type_id', 'Choose a valid type.');
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
            default => Response::view('errors/404', [], 404),
        };
    }

    /** @return array<string,scalar|null>|Response */
    private function buildHealth(Request $request): array|Response
    {
        $d = $this->validate($request, [
            'type'          => ['required', 'max:60'],
            'description'   => ['required', 'max:255'],
            'veterinarian'  => ['max:120'],
            'cost'          => ['numeric', 'min:0', 'max:100000000'],
            'date'          => ['required', 'date'],
            'next_due_date' => ['date'],
            'notes'         => ['max:500'],
        ]);
        if ($d instanceof Response) {
            return $d;
        }
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
        $d = $this->validate($request, [
            'type'           => ['required', 'max:60'],
            'quantity'       => ['required', 'numeric', 'min:0', 'max:100000000'],
            'unit'           => ['required', 'max:20'],
            'date'           => ['required', 'date'],
            'price_per_unit' => ['numeric', 'min:0', 'max:100000000'],
            'notes'          => ['max:500'],
        ]);
        if ($d instanceof Response) {
            return $d;
        }
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
        $d = $this->validate($request, [
            'weight' => ['required', 'numeric', 'min:0', 'max:100000'],
            'unit'   => ['required', 'in:kg,lb'],
            'date'   => ['required', 'date'],
            'notes'  => ['max:300'],
        ]);
        if ($d instanceof Response) {
            return $d;
        }
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
        $d = $this->validate($request, [
            'category'    => ['required', 'max:60'],
            'description' => ['required', 'max:200'],
            'amount'      => ['required', 'numeric', 'min:0', 'max:100000000'],
            'date'        => ['required', 'date'],
            'notes'       => ['max:500'],
        ]);
        if ($d instanceof Response) {
            return $d;
        }
        return [
            'category' => trim((string) $d['category']),
            'description' => trim((string) $d['description']),
            'amount' => round((float) $d['amount'], 2),
            'date' => date('Y-m-d', (int) strtotime((string) $d['date'])),
            'notes' => ($d['notes'] ?? '') !== '' ? trim((string) $d['notes']) : null,
        ];
    }
}
