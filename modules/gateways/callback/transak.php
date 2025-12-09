<?php
// Retrieve the invoice ID from the query string
$invoiceId = $_GET['invoice_id'];

if (empty($invoiceId)) {
    die("Invalid invoice ID");
}

// Load WHMCS configuration
require_once __DIR__ . '/../../../init.php';

// Retrieve the invoice information
$secret = hash('sha256', 'paygate_salt_' . $gatewayParams['wallet_address']);
if(!hash_equals(hash_hmac('sha256', $invoiceId, $secret), ($_GET['sig'] ?? ''))) { die('Invalid callback signature'); }
$invoice = localAPI('GetInvoice', array('invoiceid' => $invoiceId));

if ($invoice['result'] == 'success' && $invoice['status'] != 'Paid') {
    // Mark the invoice as paid
    $paymentSuccess = array(
        'invoiceid' => $invoiceId,
        'transid' => 'transak_payment_' . time(), // You may want to replace this with the actual transaction ID
        'date' => date('Y-m-d H:i:s'),
    );

    $result = localAPI('AddInvoicePayment', $paymentSuccess);

    if ($result['result'] == 'success') {
        header("HTTP/1.1 200 OK");
        header("Content-Type: text/plain");
        echo "*ok*";
        exit;
    } else {
        // Redirect to the invoice page
        $invoiceLink = $CONFIG['SystemURL'] . '/viewinvoice.php?id=' . $invoiceId;
        header("Location: $invoiceLink");
        exit;
    }
} else {
    // Redirect to the invoice page
        $invoiceLink = $CONFIG['SystemURL'] . '/viewinvoice.php?id=' . $invoiceId;
        header("Location: $invoiceLink");
        exit;}