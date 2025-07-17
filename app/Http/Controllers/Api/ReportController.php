<?php

namespace App\Http\Controllers\Api;

use App\Infrastructure\Repositories\EloquentTransactionRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    public function __construct(
        private EloquentTransactionRepository $transactionRepository
    ) {}

    public function exportTransactionsCsv(Request $request): Response
    {
        $filters = $request->only(['start_date', 'end_date', 'status', 'type']);
        
        $csv = $this->transactionRepository->exportToCsv($filters);
        
        $filename = 'transacciones_' . date('Y-m-d_H-i-s') . '.csv';
        
        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function getTransferTotalsByUser(): JsonResponse
    {
        try {
            $totals = $this->transactionRepository->getTransferTotalsByUser();
            
            return response()->json([
                'success' => true,
                'message' => 'Totales de transferencias por usuario obtenidos exitosamente',
                'data' => $totals,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener totales de transferencias',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAverageAmountByUser(): JsonResponse
    {
        try {
            $averages = $this->transactionRepository->getAverageAmountByUser();
            
            return response()->json([
                'success' => true,
                'message' => 'Promedios de montos por usuario obtenidos exitosamente',
                'data' => $averages,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener promedios de montos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}