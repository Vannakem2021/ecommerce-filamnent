<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: #4CAF50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .transaction-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }
        .transaction-info h3 {
            margin-top: 0;
            color: #333;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .label {
            font-weight: 600;
            color: #555;
        }
        .value {
            color: #333;
        }
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            margin: 10px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5a6fd8;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">
            ✓
        </div>
        <h1>Payment Successful!</h1>
        <p>{{ $message ?? 'Your payment has been processed successfully.' }}</p>
        
        @if(isset($transaction_id))
        <div class="transaction-info">
            <h3>Transaction Details</h3>
            <div class="info-row">
                <span class="label">Transaction ID:</span>
                <span class="value">{{ $transaction_id }}</span>
            </div>
            <div class="info-row">
                <span class="label">Status:</span>
                <span class="value">Completed</span>
            </div>
            <div class="info-row">
                <span class="label">Date:</span>
                <span class="value">{{ now()->format('M d, Y H:i') }}</span>
            </div>
        </div>
        @endif
        
        <div style="margin-top: 30px;">
            <a href="{{ route('home') }}" class="btn">Continue Shopping</a>
            @auth
                <a href="{{ route('my-orders') }}" class="btn btn-secondary">View Orders</a>
            @endauth
        </div>
        
        <p style="font-size: 12px; color: #999; margin-top: 20px;">
            You will receive an email confirmation shortly.
        </p>
    </div>
</body>
</html>
