<?php

namespace App\Http\Controllers;

use App\Services\PayWayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Redirect to PayWay with auto-submit form
     */
    public function redirectToPayWay()
    {
        $paymentData = session('payway_payment_data');
        $orderId = session('payway_order_id');

        if (!$paymentData || !$orderId) {
            return redirect()->route('cart-products')->with('error', 'Payment session expired');
        }

        return view('payment.payway-redirect', compact('paymentData', 'orderId'));
    }

    /**
     * Process ABA Pay payment (redirect to PayWay)
     * This method is deprecated - use redirectToPayWay instead
     */
    public function processAbaPayment()
    {
        // Redirect to the correct PayWay flow
        Log::info('Legacy processAbaPayment called - redirecting to correct flow');
        return redirect()->route('payment.aba-pay.redirect');
    }

    /**
     * Handle ABA Pay return (success)
     */
    public function abaPayReturn(Request $request)
    {
        try {
            // Log the return data for debugging
            Log::info('ABA Pay return callback received', [
                'request_data' => $request->all()
            ]);

            $transactionId = $request->get('tran_id');

            if (!$transactionId) {
                return redirect()->route('cart')
                    ->with('error', 'Invalid payment response - missing transaction ID');
            }

            // Use PayWayService to verify transaction
            $payWayService = new PayWayService();
            $statusResult = $payWayService->getTransactionDetails($transactionId);

            if ($statusResult['success']) {
                // Check if payment was successful
                $transactionData = $statusResult['data'];

                // PayWay uses status codes: '00' = success, '01' = pending, '02' = failed, '03' = cancelled
                if (isset($transactionData['status']['code']) && $transactionData['status']['code'] === '00') {
                    // Extract order ID from transaction ID (format: ORD-{order_id}-{timestamp})
                    if (preg_match('/ORD-(\d+)-/', $transactionId, $matches)) {
                        $orderId = $matches[1];

                        // Update order payment status
                        $order = \App\Models\Order::find($orderId);
                        if ($order) {
                            $order->update(['payment_status' => 'paid']);

                            return redirect()->route('success', ['order_id' => $orderId])
                                ->with('success', 'Payment completed successfully!');
                        }
                    }

                    return redirect()->route('cart-products')
                        ->with('error', 'Order not found. Please contact support.');
                } else {
                    // Payment failed or pending
                    return redirect()->route('cart-products')
                        ->with('error', 'Payment was not successful. Please try again.');
                }
            } else {
                // Could not verify transaction
                Log::error('Could not verify transaction status', [
                    'transaction_id' => $transactionId,
                    'error' => $statusResult['error']
                ]);

                return redirect()->route('cart')
                    ->with('error', 'Could not verify payment status. Please contact support.');
            }

        } catch (\Exception $e) {
            Log::error('ABA Pay return processing failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return redirect()->route('cart')
                ->with('error', 'Payment processing error. Please contact support.');
        }
    }

    /**
     * Handle ABA Pay cancel callback
     */
    public function abaPayCancel(Request $request)
    {
        Log::info('ABA Pay cancel callback received', [
            'request_data' => $request->all()
        ]);

        return redirect()->route('cart-products')
            ->with('error', 'Payment was cancelled.');
    }

    /**
     * Handle ABA Pay success callback
     */
    public function abaPaySuccess(Request $request)
    {
        Log::info('ABA Pay Success Callback', [
            'transaction_id' => $request->get('tran_id'),
            'all_params' => $request->all()
        ]);

        $transactionId = $request->get('tran_id');

        if ($transactionId) {
            // Extract order ID from transaction ID (format: ORD-{order_id}-{timestamp})
            if (preg_match('/ORD-(\d+)-/', $transactionId, $matches)) {
                $orderId = $matches[1];
                return redirect()->route('success', ['order_id' => $orderId])
                    ->with('success', 'Payment completed successfully!');
            }
        }

        return view('payment.success', [
            'transaction_id' => $transactionId,
            'message' => 'Payment completed successfully!'
        ]);
    }

    /**
     * Handle ABA Pay webhook/callback (for payment status updates)
     */
    public function abaPayWebhook(Request $request)
    {
        try {
            Log::info('PayWay Webhook Received', [
                'method' => $request->method(),
                'all_params' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            $payWayService = new PayWayService();

            // Verify webhook authenticity
            if (!$payWayService->verifyCallback($request->all())) {
                Log::error('PayWay webhook verification failed', $request->all());
                return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
            }

            $transactionId = $request->get('tran_id');

            // Get latest transaction details from PayWay
            $result = $payWayService->getTransactionDetails($transactionId);

            if ($result['success']) {
                $this->updateOrderFromWebhook($transactionId, $result['data']);
            }

            return response()->json(['status' => 'received'], 200);

        } catch (\Exception $e) {
            Log::error('PayWay webhook processing failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    private function updateOrderFromWebhook($transactionId, $payWayData)
    {
        // Extract order ID from transaction ID (format: ORD-{order_id}-{timestamp})
        if (preg_match('/ORD-(\d+)-/', $transactionId, $matches)) {
            $orderId = $matches[1];
            $order = \App\Models\Order::find($orderId);

            if ($order) {
                $status = $this->mapPayWayStatus($payWayData['status']['code']);
                $order->update([
                    'payment_status' => $status,
                    'payment_data' => $payWayData
                ]);

                // Update AbaPayTransaction record
                $abaTransaction = \App\Models\AbaPayTransaction::where('transaction_id', $transactionId)->first();
                if ($abaTransaction) {
                    $abaTransaction->update([
                        'status' => $status === 'paid' ? \App\Models\AbaPayTransaction::STATUS_COMPLETED : $status,
                        'payway_status_code' => $payWayData['status']['code'],
                        'payway_response' => $payWayData,
                        'webhook_data' => $payWayData,
                        'webhook_received_at' => now(),
                        'processed_at' => now()
                    ]);
                }

                // Trigger events based on status
                if ($status === 'paid') {
                    // event(new \App\Events\PaymentCompleted($order));
                    Log::info('Payment completed for order', ['order_id' => $orderId]);
                }
            }
        }
    }

    private function mapPayWayStatus($payWayStatusCode)
    {
        return match($payWayStatusCode) {
            '00' => 'paid',
            '01' => 'pending',
            '02' => 'failed',
            '03' => 'cancelled',
            default => 'pending'
        };
    }
}