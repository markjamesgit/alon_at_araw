<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Store payment details in session
$_SESSION['payment_details'] = [
    'payment_method' => $_POST['payment_method'],
    'total_amount' => $_POST['total_amount'],
    'delivery_method' => $_POST['delivery_method'],
    'delivery_address' => $_POST['delivery_address'],
    'contact_number' => $_POST['contact_number'],
    'special_instructions' => $_POST['special_instructions']
];

echo json_encode([
    'success' => true,
    'message' => 'Payment details stored successfully'
]); 