<?php

namespace App\Services;

use App\Models\Affectation;
use App\Models\Stock;
use App\Models\User;
use App\Repositories\StockRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function __construct(
        private readonly StockRepository $stockRepository,
        private readonly ActivityLogService $logService
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->stockRepository->paginate($perPage, $filters);
    }

    public function show(int $id): Stock
    {
        return $this->stockRepository->findById($id);
    }

    public function create(array $data, User $creator): Stock
    {
        if (empty($data['reference'])) {
            $data['reference'] = $this->stockRepository->generateReference();
        }

        $stock = $this->stockRepository->create($data);
        $this->logService->log('created', $creator, Stock::class, $stock->id, null, $stock->toArray());

        return $stock;
    }

    public function nextReference(): string
    {
        return $this->stockRepository->generateReference();
    }

    public function update(int $id, array $data, User $updater): Stock
    {
        $old     = $this->stockRepository->findById($id)->toArray();
        $updated = $this->stockRepository->update($id, $data);

        $this->logService->log('updated', $updater, Stock::class, $id, $old, $data);

        return $updated;
    }

    public function delete(int $id, User $user): void
    {
        $stock = $this->stockRepository->findById($id);

        if ($stock->activeAffectations()->count() > 0) {
            throw new \Exception("Impossible de supprimer : ce matériel a des affectations actives.", 422);
        }

        $this->stockRepository->delete($id);
        $this->logService->log('deleted', $user, Stock::class, $id, $stock->toArray(), null);
    }

    /**
     * Affecte du matériel à un utilisateur.
     */
    public function assign(int $stockId, int $userId, int $quantity, User $admin, ?string $notes = null): Affectation
    {
        return DB::transaction(function () use ($stockId, $userId, $quantity, $admin, $notes) {
            $stock = $this->stockRepository->findById($stockId);

            if ($stock->quantity < $quantity) {
                throw new \Exception("Quantité insuffisante en stock. Disponible : {$stock->quantity}", 422);
            }

            // Créer l'affectation
            $affectation = Affectation::create([
                'stock_id'    => $stockId,
                'user_id'     => $userId,
                'assigned_by' => $admin->id,
                'quantity'    => $quantity,
                'notes'       => $notes,
                'status'      => Affectation::STATUS_ACTIVE,
                'assigned_at' => now(),
            ]);

            // Décrémenter le stock
            $stock->decrement('quantity', $quantity);

            // Mettre à jour le statut du stock si nécessaire
            if ($stock->fresh()->quantity === 0) {
                $stock->update(['status' => Stock::STATUS_AFFECTE]);
            }

            $this->logService->log('assigned', $admin, Stock::class, $stockId, null, [
                'user_id'  => $userId,
                'quantity' => $quantity,
            ]);

            return $affectation->load(['stock:id,name', 'user:id,name,email']);
        });
    }

    /**
     * Retour de matériel affecté.
     */
    public function returnStock(int $affectationId, User $admin, ?string $notes = null): Affectation
    {
        return DB::transaction(function () use ($affectationId, $admin, $notes) {
            $affectation = Affectation::with('stock')->findOrFail($affectationId);

            if ($affectation->status !== Affectation::STATUS_ACTIVE) {
                throw new \Exception("Cette affectation n'est pas active.", 422);
            }

            $affectation->update([
                'status'      => Affectation::STATUS_RETURNED,
                'returned_at' => now(),
                'notes'       => $notes ?? $affectation->notes,
            ]);

            // Réincrémenter le stock
            $affectation->stock->increment('quantity', $affectation->quantity);

            if ($affectation->stock->fresh()->quantity > 0) {
                $affectation->stock->update(['status' => Stock::STATUS_DISPONIBLE]);
            }

            $this->logService->log('returned', $admin, Stock::class, $affectation->stock_id, null, [
                'affectation_id' => $affectationId,
            ]);

            return $affectation->fresh()->load(['stock:id,name', 'user:id,name,email']);
        });
    }

    public function getLowStockItems(): Collection
    {
        return $this->stockRepository->lowStockItems();
    }
}
