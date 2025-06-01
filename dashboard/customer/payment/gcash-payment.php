<?php
include '../../../config/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /alon_at_araw/auth/login.php');
    exit;
}

// Check if payment details are set
if (!isset($_SESSION['payment_details'])) {
    header('Location: ../checkout.php');
    exit;
}

$payment_details = $_SESSION['payment_details'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GCash Payment | Alon at Araw</title>
    <link rel="stylesheet" href="/alon_at_araw/assets/global.css">
    <link rel="stylesheet" href="/alon_at_araw/assets/styles/root-customer.css">
    <link rel="stylesheet" href="/alon_at_araw/assets/styles/gcash-payment.css">
    <link rel="icon" type="image/png" href="../../../assets/images/logo/logo.png">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <a href="../checkout.php" class="back-btn">
                <span class="material-icons">arrow_back</span>
                <span>Back</span>
            </a>
            <img src="/alon_at_araw/assets/images/payment/gcash-logo.png" alt="GCash" class="payment-logo">
        </div>

        <div class="payment-details">
            <h2>Complete Your Payment</h2>
            
            <div class="amount-display">
                <div class="label">Amount to Pay</div>
                <div class="amount">₱<?= number_format($payment_details['total_amount'], 2) ?></div>
            </div>

            <div class="qr-code">
                <img src="/alon_at_araw/assets/images/payment/qr-code.png" alt="GCash QR Code">
                <p style="margin-top: 1rem; color: var(--text-secondary); font-size: 0.875rem;">
                    Scan with GCash app
                </p>
            </div>

            <div class="payment-instructions">
                <h3>Payment Instructions</h3>
                <ol>
                    <li>Open your GCash app on your phone</li>
                    <li>On the app, tap "Scan QR" and scan the code above</li>
                    <li>Enter the exact amount: ₱<?= number_format($payment_details['total_amount'], 2) ?></li>
                    <li>Review and confirm your payment</li>
                    <li>Copy the reference number from your GCash receipt</li>
                </ol>
            </div>

            <form action="../process-order.php" method="POST" class="payment-form">
                <input type="hidden" name="payment_method" value="gcash">
                <input type="hidden" name="total_amount" value="<?= $payment_details['total_amount'] ?>">
                <input type="hidden" name="delivery_method" value="<?= $payment_details['delivery_method'] ?>">
                <input type="hidden" name="delivery_address" value="<?= htmlspecialchars($payment_details['delivery_address']) ?>">
                <input type="hidden" name="contact_number" value="<?= htmlspecialchars($payment_details['contact_number']) ?>">
                <input type="hidden" name="special_instructions" value="<?= htmlspecialchars($payment_details['special_instructions']) ?>">
                
                <div class="form-group">
                    <label for="reference_number">Reference Number</label>
                    <input type="text" id="reference_number" name="reference_number" required 
                           placeholder="Enter the reference number from your GCash receipt">
                </div>

                <button type="submit" class="confirm-payment-btn">
                    <span class="material-icons">check_circle</span>
                    <span>Confirm Payment</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        // Enhanced payment validation
        document.querySelector('.payment-form').addEventListener('submit', function(e) {
            const refNumber = document.getElementById('reference_number').value.trim();
            if (!refNumber.match(/^[0-9]{10,13}$/)) {
                e.preventDefault();
                alert('Please enter a valid GCash reference number (10-13 digits)');
                document.getElementById('reference_number').focus();
            }
        });

        // Add input formatting
        document.getElementById('reference_number').addEventListener('input', function(e) {
            // Remove any non-numeric characters
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Limit to 13 digits
            if (this.value.length > 13) {
                this.value = this.value.slice(0, 13);
            }
        });
    </script>
</body>
</html> 