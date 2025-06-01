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
    <title>BDO Payment | Alon at Araw</title>
    <link rel="stylesheet" href="/alon_at_araw/assets/global.css">
    <link rel="stylesheet" href="/alon_at_araw/assets/styles/root-customer.css">
    <link rel="stylesheet" href="/alon_at_araw/assets/styles/payment.css">
    <link rel="icon" type="image/png" href="../../../assets/images/logo/logo.png">
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <a href="../checkout.php" class="back-btn">← Back to Checkout</a>
            <img src="/alon_at_araw/assets/images/payment/bdo-logo.png" alt="BDO" class="payment-logo">
        </div>

        <div class="payment-details">
            <h2>BDO Online Banking Payment</h2>
            <div class="amount-display">
                <span>Amount to Pay:</span>
                <span class="amount">₱<?= number_format($payment_details['total_amount'], 2) ?></span>
            </div>

            <div class="payment-instructions">
                <h3>Bank Account Details:</h3>
                <div class="bank-details">
                    <p><strong>Account Name:</strong> Alon at Araw</p>
                    <p><strong>Account Number:</strong> 1234-5678-9012</p>
                    <p><strong>Bank:</strong> BDO (Banco de Oro)</p>
                    <p><strong>Branch:</strong> Main Branch</p>
                </div>

                <h3>How to Pay:</h3>
                <ol>
                    <li>Log in to your BDO Online Banking account</li>
                    <li>Go to "Send Money/Transfer"</li>
                    <li>Select "Transfer to Other BDO Account"</li>
                    <li>Enter the account number above</li>
                    <li>Enter the exact amount: ₱<?= number_format($payment_details['total_amount'], 2) ?></li>
                    <li>Complete the transfer</li>
                    <li>Enter the reference number below</li>
                </ol>
            </div>

            <form action="../process-order.php" method="POST" class="payment-form">
                <input type="hidden" name="payment_method" value="bdo">
                <input type="hidden" name="total_amount" value="<?= $payment_details['total_amount'] ?>">
                <input type="hidden" name="delivery_method" value="<?= $payment_details['delivery_method'] ?>">
                <input type="hidden" name="delivery_address" value="<?= htmlspecialchars($payment_details['delivery_address']) ?>">
                <input type="hidden" name="contact_number" value="<?= htmlspecialchars($payment_details['contact_number']) ?>">
                <input type="hidden" name="special_instructions" value="<?= htmlspecialchars($payment_details['special_instructions']) ?>">
                
                <div class="form-group">
                    <label for="reference_number">BDO Reference Number</label>
                    <input type="text" id="reference_number" name="reference_number" required 
                           placeholder="Enter your BDO reference number">
                </div>

                <button type="submit" class="confirm-payment-btn">Confirm Payment</button>
            </form>
        </div>
    </div>

    <script>
        // Add any necessary JavaScript for payment validation
        document.querySelector('.payment-form').addEventListener('submit', function(e) {
            const refNumber = document.getElementById('reference_number').value;
            if (!refNumber.match(/^[0-9]{12}$/)) {
                e.preventDefault();
                alert('Please enter a valid BDO reference number (12 digits)');
            }
        });
    </script>
</body>
</html> 