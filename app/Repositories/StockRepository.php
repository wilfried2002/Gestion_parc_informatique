<?php

namespace App\Repositories;

use App\Models\Stock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class StockRepository extends BaseRepository
{
    public function __construct(Stock $stock)
    {
        parent::__construct($stock);
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Stock::withCount('activeAffectations')
            ->when(isset($filters['category']), fn($q) => $q->where('category', $filters['category']))
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['location']), fn($q) => $q->where('location', 'like', "%{$filters['location']}%"))
            ->when(isset($filters['low_stock']), fn($q) => $q->lowStock())
            ->when(isset($filters['search']), fn($q) => $q->where(function ($sq) use ($filters) {
                $sq->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('reference', 'like', "%{$filters['search']}%")
                    ->orWhere('serial_number', 'like', "%{$filters['search']}%")
                    ->orWhere('brand', 'like', "%{$filters['search']}%");
            }))
            ->orderBy('name');

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Stock
    {
        return Stock::with(['activeAffectations.user:id,name,email'])->findOrFail($id);
    }

    /**
     * Statistiques par catégorie.
     */
    public function statsByCategory(): Collection
    {
        return Stock::selectRaw('category, COUNT(*) as total, SUM(quantity) as total_quantity')
            ->groupBy('category')
            ->get();
    }

    /**
     * Liste du matériel en alerte de stock.
     */
    public function lowStockItems(): Collection
    {
        return Stock::lowStock()->where('status', '!=', Stock::STATUS_HORS_SERVICE)->get();
    }
}
