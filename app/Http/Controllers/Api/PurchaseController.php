<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Models\ResourcePurchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Money\Money;
use Vptrading\ChapaLaravel\Facades\Chapa;
use Vptrading\ChapaLaravel\ValueObjects\Customization;
use Vptrading\ChapaLaravel\ValueObjects\User as ChapaUser;

class PurchaseController extends Controller
{
    /**
     * Start a purchase.
     */
    public function initiate(Request $request, $resourceId): JsonResponse
    {
        $user = $request->user();

        $resource = Resource::find($resourceId);

        if (!$resource) {
            return response()->json([
                'status' => 'error',
                'message' => 'Resource not found',
            ], 404);
        }

        $alreadyPurchased = ResourcePurchase::where('user_id', $user->id)
            ->where('resource_id', $resource->id)
            ->where('status', 'success')
            ->exists();

        if ($alreadyPurchased) {
            return response()->json([
                'status' => 'error',
                'message' => 'You have already purchased this resource.',
            ], 422);
        }

        $txRef = config('chapa.ref_prefix')
            . 'res'
            . $resource->id
            . '_'
            . Str::random(10);

        $purchase = ResourcePurchase::create([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
            'tx_ref' => $txRef,
            'amount' => $resource->price,
            'status' => 'pending',
        ]);

        $amountInCents = (int) round($resource->price * 100);

        try {
            $response = Chapa::acceptPayment(
                Money::ETB($amountInCents),

                new ChapaUser(
                    firstName: $user->first_name,
                    lastName: $user->last_name,
                    email: $user->email,
                    phoneNumber: $user->phone ?? '0900000000',
                ),

                // Left empty on purpose: no return_url means Chapa does not
                // redirect the browser anywhere after showing its own
                // hosted payment receipt — the user simply stays there.
                // The transaction is still confirmed and finalized on our
                // side via the webhook() method below, independent of
                // whatever the browser does.
                '',

                new Customization(
                    title: 'Asteghi Resource',
                    description: 'Purchase of ' . $resource->title,
                ),
            );

            Log::info('FULL CHAPA RESPONSE', [
                'tx_ref' => $txRef,
                'response' => $response,
            ]);

            $checkoutUrl = $response->checkout_url ?? null;
            $chapaTxRef = $response->transaction_id ?? null;
            $initStatus = $response->status ?? null;

            if (!$checkoutUrl || !$chapaTxRef || $initStatus !== 'success') {
                Log::error('Chapa initialize did not return usable data', [
                    'tx_ref' => $txRef,
                    'response' => $response,
                ]);

                $purchase->update(['status' => 'failed']);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment initialization failed. Please try again.',
                ], 502);
            }

            $purchase->update([
                'chapa_tx_ref' => $chapaTxRef,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Chapa payment initialized',
                'data' => [
                    'checkout_url' => $checkoutUrl,
                    'tx_ref' => $txRef,
                ],
            ]);

        } catch (\Throwable $e) {

            Log::error('Chapa payment initialization failed', [
                'tx_ref' => $txRef,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $purchase->update([
                'status' => 'failed',
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Could not start payment. Please try again.',
            ], 502);
        }
    }

    /**
     * No longer used as a live redirect target since return_url is empty
     * in initiate() above (Chapa has nothing to redirect back to). Left
     * in place, unused, in case return_url is reintroduced later.
     */
    public function returnFromPayment(Request $request): RedirectResponse
    {
        $txRef = $request->query('tx_ref');

        if (!$txRef) {
            return redirect()->away('https://chapa.co');
        }

        $purchase = $this->verifyAndUpdate($txRef);

        if (!$purchase || !$purchase->chapa_tx_ref) {
            return redirect()->away('https://chapa.co');
        }

        return redirect()->away(
            'https://chapa.link/payment-receipt/' . $purchase->chapa_tx_ref
        );
    }

    /**
     * Check the payment status.
     *
     * JSON — for your frontend to poll/check programmatically
     * (e.g. to unlock a download button).
     */
    public function checkStatus(Request $request, $txRef): JsonResponse
    {
        $purchase = ResourcePurchase::where('tx_ref', $txRef)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$purchase) {
            return response()->json([
                'status' => 'error',
                'message' => 'Purchase not found',
            ], 404);
        }

        $purchase = $this->verifyAndUpdate($txRef);

        if (!$purchase) {
            return response()->json([
                'status' => 'error',
                'message' => 'Purchase not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'tx_ref' => $purchase->tx_ref,
                'status' => $purchase->status,
                'resource_id' => $purchase->resource_id,
                'paid_at' => $purchase->paid_at,
                'has_access' => $purchase->status === 'success',
            ],
        ]);
    }

    /**
     * Chapa calls this server-to-server after a payment event.
     * Must stay outside auth:sanctum — Chapa is not an authenticated user.
     * This is what actually finalizes the transaction now, independent
     * of the browser/return_url flow above.
     */
   public function webhook(Request $request): JsonResponse
{
    $chapaTxRef = $request->input('tx_ref');

    Log::info('CHAPA WEBHOOK RECEIVED', $request->all());

    if (!$chapaTxRef) {
        return response()->json(['message' => 'Missing tx_ref'], 400);
    }

    $purchase = ResourcePurchase::where('chapa_tx_ref', $chapaTxRef)->first();

    if (!$purchase) {
        Log::warning('Webhook: no matching purchase found', [
            'chapa_tx_ref' => $chapaTxRef,
        ]);

        return response()->json(['message' => 'ok']);
    }

    $this->verifyAndUpdate($purchase->tx_ref);

    return response()->json(['message' => 'ok']);
}

    /**
     * Verify the transaction with Chapa and update the local purchase.
     */
    private function verifyAndUpdate(string $txRef): ?ResourcePurchase
    {
        $purchase = ResourcePurchase::where('tx_ref', $txRef)->first();

        if (!$purchase) {
            return null;
        }

        if ($purchase->status === 'success') {
            return $purchase;
        }

        if (!$purchase->chapa_tx_ref) {
            Log::warning('No chapa_tx_ref stored yet for purchase — cannot verify', [
                'tx_ref' => $txRef,
            ]);

            return $purchase;
        }

        try {
            $response = Http::withToken(config('chapa.secret_key'))
                ->acceptJson()
                ->get(
                    rtrim(config('chapa.base_url'), '/') .
                    '/transaction/verify/' .
                    urlencode($purchase->chapa_tx_ref)
                );

            Log::info('CHAPA DIRECT VERIFICATION RESPONSE', [
                'tx_ref' => $txRef,
                'chapa_tx_ref' => $purchase->chapa_tx_ref,
                'http_status' => $response->status(),
                'response' => $response->json(),
            ]);

            if (!$response->successful()) {
                return $purchase;
            }

            $data = $response->json();

            $status = data_get($data, 'data.status');

            if ($status === 'success') {
                $purchase->update([
                    'status' => 'success',
                    'paid_at' => now(),
                ]);
            } elseif ($status === 'failed') {
                $purchase->update([
                    'status' => 'failed',
                ]);
            }

            return $purchase->fresh();

        } catch (\Throwable $e) {
            Log::error('Chapa direct verification failed', [
                'tx_ref' => $txRef,
                'chapa_tx_ref' => $purchase->chapa_tx_ref,
                'error' => $e->getMessage(),
            ]);

            return $purchase;
        }
    }
}