<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDonationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $this->canManage($request)) {
            return response()->json(['success' => false, 'message' => 'Acesso negado'], 403);
        }

        $donations = Donation::query()
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Donation $d): array => $this->formatDonation($d))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $donations,
            'editWindowMinutes' => $this->editWindowMinutes(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->canManage($request)) {
            return response()->json(['success' => false, 'message' => 'Acesso negado'], 403);
        }

        $validated = $request->validate([
            'donorName'     => ['required', 'string', 'max:255'],
            'amount'        => ['required', 'numeric', 'min:0.01'],
            'date'          => ['required', 'date'],
            'paymentMethod' => ['required', 'string', 'in:pix,card,boleto,cash'],
            'isConfirmed'   => ['required', 'boolean'],
            'notes'         => ['nullable', 'string', 'max:1000'],
        ]);

        $donation = Donation::create([
            'donor_name'     => $validated['donorName'],
            'amount'         => $validated['amount'],
            'date'           => $validated['date'],
            'payment_method' => $validated['paymentMethod'],
            'status'         => $validated['isConfirmed'] ? 'confirmed' : 'pending',
            'notes'          => $validated['notes'] ?? null,
            'created_by'     => $request->user()?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Doação registrada com sucesso',
            'data'    => $this->formatDonation($donation),
        ], 201);
    }

    public function update(Request $request, Donation $donation): JsonResponse
    {
        if (! $this->canManage($request)) {
            return response()->json(['success' => false, 'message' => 'Acesso negado'], 403);
        }

        if (! $this->withinEditWindow($donation)) {
            return response()->json([
                'success' => false,
                'message' => sprintf(
                    'Esta doação não pode mais ser editada. O prazo de %d minuto(s) após o registro expirou.',
                    $this->editWindowMinutes()
                ),
                'code' => 'EDIT_WINDOW_EXPIRED',
            ], 422);
        }

        $validated = $request->validate([
            'donorName'     => ['required', 'string', 'max:255'],
            'amount'        => ['required', 'numeric', 'min:0.01'],
            'date'          => ['required', 'date'],
            'paymentMethod' => ['required', 'string', 'in:pix,card,boleto,cash'],
            'isConfirmed'   => ['required', 'boolean'],
            'notes'         => ['nullable', 'string', 'max:1000'],
        ]);

        $donation->update([
            'donor_name'     => $validated['donorName'],
            'amount'         => $validated['amount'],
            'date'           => $validated['date'],
            'payment_method' => $validated['paymentMethod'],
            'status'         => $validated['isConfirmed'] ? 'confirmed' : 'pending',
            'notes'          => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Doação atualizada com sucesso',
            'data'    => $this->formatDonation($donation->fresh()),
        ]);
    }

    public function destroy(Request $request, Donation $donation): JsonResponse
    {
        if (! $this->canManage($request)) {
            return response()->json(['success' => false, 'message' => 'Acesso negado'], 403);
        }

        if (! $this->withinEditWindow($donation)) {
            return response()->json([
                'success' => false,
                'message' => sprintf(
                    'Esta doação não pode mais ser excluída. O prazo de %d minuto(s) após o registro expirou.',
                    $this->editWindowMinutes()
                ),
                'code' => 'EDIT_WINDOW_EXPIRED',
            ], 422);
        }

        $donation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Doação excluída com sucesso',
        ]);
    }

    private function withinEditWindow(Donation $donation): bool
    {
        $window = $this->editWindowMinutes();

        if ($window <= 0) {
            return true;
        }

        return $donation->created_at !== null
            && $donation->created_at->diffInMinutes(now()) <= $window;
    }

    private function editWindowMinutes(): int
    {
        return (int) config('donations.edit_window_minutes', 60);
    }

    private function canManage(Request $request): bool
    {
        $role = (string) ($request->user()?->role ?? '');

        return in_array($role, ['manager', 'publisher'], true);
    }

    private function formatDonation(Donation $donation): array
    {
        $window = $this->editWindowMinutes();
        $canEdit = $window <= 0
            || ($donation->created_at !== null && $donation->created_at->diffInMinutes(now()) <= $window);

        return [
            'id'            => $donation->id,
            'donorName'     => (string) $donation->donor_name,
            'amount'        => (float) $donation->amount,
            'date'          => $donation->date?->toDateString(),
            'paymentMethod' => (string) $donation->payment_method,
            'status'        => (string) $donation->status,
            'notes'         => $donation->notes,
            'canEdit'       => $canEdit,
            'createdAt'     => $donation->created_at?->toISOString(),
        ];
    }
}
