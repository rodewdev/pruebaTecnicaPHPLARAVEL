<?php

namespace App\Http\Controllers\Api;

use App\Application\Services\TransactionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function __construct(
        private TransactionService $transactionService
    ) {}

    public function transfer(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0.01|max:5000',
            'description' => 'nullable|string|max:255',
        ], [
            'receiver_id.required' => 'El receptor es obligatorio.',
            'receiver_id.integer' => 'El receptor debe ser un número entero.',
            'receiver_id.exists' => 'El receptor seleccionado no existe.',
            'amount.required' => 'El monto es obligatorio.',
            'amount.numeric' => 'El monto debe ser un número.',
            'amount.min' => 'El monto debe ser al menos 0.01.',
            'amount.max' => 'El monto no debe ser mayor que 5000.',
            'description.string' => 'La descripción debe ser texto.',
            'description.max' => 'La descripción no debe ser mayor que 255 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Usar el ID del usuario autenticado como remitente
            $senderId = $request->user()->id;
            
            $transaction = $this->transactionService->transferMoney(
                $senderId,
                $request->input('receiver_id'),
                $request->input('amount'),
                $request->input('description')
            );

            return response()->json([
                'success' => true,
                'message' => 'Transferencia realizada exitosamente',
                'data' => $transaction->toArray(),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}