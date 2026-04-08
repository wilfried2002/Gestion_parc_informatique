<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\AffectationRequest;
use App\Http\Requests\Stock\StockRequest;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function __construct(private readonly StockService $stockService) {}

    /**
     * GET /api/stocks
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['category', 'status', 'location', 'search', 'low_stock']);
        $stocks  = $this->stockService->list($filters, $request->get('per_page', 15));

        return $this->paginated($stocks);
    }

    /**
     * POST /api/stocks   [Admin]
     */
    public function store(StockRequest $request): JsonResponse
    {
        try {
            $stock = $this->stockService->create($request->validated(), auth()->user());

            return $this->success($stock, 'Matériel ajouté au stock.', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/stocks/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            return $this->success($this->stockService->show($id));
        } catch (\Exception $e) {
            return $this->error('Matériel introuvable.', 404);
        }
    }

    /**
     * PUT /api/stocks/{id}   [Admin]
     */
    public function update(StockRequest $request, int $id): JsonResponse
    {
        try {
            $stock = $this->stockService->update($id, $request->validated(), auth()->user());

            return $this->success($stock, 'Stock mis à jour.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * DELETE /api/stocks/{id}   [Admin]
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->stockService->delete($id, auth()->user());

            return $this->success(null, 'Matériel supprimé.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * POST /api/stocks/{id}/assign   [Admin]
     * Affecte du matériel à un utilisateur.
     */
    public function assign(AffectationRequest $request, int $id): JsonResponse
    {
        try {
            $affectation = $this->stockService->assign(
                $id,
                $request->user_id,
                $request->quantity,
                auth()->user(),
                $request->notes
            );

            return $this->success($affectation, 'Matériel affecté.', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * PATCH /api/stocks/affectations/{affectationId}/return   [Admin]
     * Retour de matériel.
     */
    public function returnStock(Request $request, int $affectationId): JsonResponse
    {
        try {
            $affectation = $this->stockService->returnStock(
                $affectationId,
                auth()->user(),
                $request->notes
            );

            return $this->success($affectation, 'Matériel retourné au stock.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * GET /api/stocks/next-reference   [Admin]
     * Retourne la prochaine référence disponible.
     */
    public function nextReference(): JsonResponse
    {
        return $this->success(['reference' => $this->stockService->nextReference()]);
    }

    /**
     * GET /api/stocks/low-stock   [Admin]
     * Liste les articles en alerte de stock.
     */
    public function lowStock(): JsonResponse
    {
        $items = $this->stockService->getLowStockItems();

        return $this->success($items, 'Articles en alerte de stock.');
    }
}
