<?php
/**
 * Hel.io Crypto Payment Gateway Callback Handler
 * 
 * This file handles webhook notifications from Hel.io when payments are completed.
 * It verifies the webhook signature and updates the invoice status in WHMCS.
 * 
 * @author WHMCS Community
 * @version 1.0.0
 * @license MIT
 */

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

// Retrieve webhook secret from configuration
$webhookSecret = $gatewayParams['WebhookSecret'];

if (empty($webhookSecret)) {
    http_response_code(400);
    die("Webhook secret not configured");
}

/**
 * Verify webhook signature
 * 
 * @param string $payload The raw POST data
 * @param string $signature The signature from the X-HELIO-SIGNATURE header
 * @param string $secret The webhook secret
 * @return bool
 */
function verifyWebhookSignature($payload, $signature, $secret)
{
    // Remove 'sha256=' prefix if present
    $signature = str_replace('sha256=', '', $signature);
    
    // Calculate expected signature
    $expectedSignature = hash_hmac('sha256', $payload, $secret);
    
    // Use hash_equals to prevent timing attacks
    return hash_equals($expectedSignature, $signature);
}

/**
 * Log webhook activity
 * 
 * @param string $message
 * @param array $data
 * @param string $level
 */
function logWebhookActivity($message, $data = [], $level = 'info')
{
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'data' => $data,
        'level' => $level
    ];
    
    logTransaction('helio', $logData, 'Webhook ' . ucfirst($level));
}

// Get the raw POST data
$rawPayload = file_get_contents('php://input');

// Check if we have POST data
if (empty($rawPayload)) {
    http_response_code(400);
    logWebhookActivity('Empty webhook payload received', [], 'error');
    die("No data received");
}

// Get the signature header
$signature = $_SERVER['HTTP_X_HELIO_SIGNATURE'] ?? '';

if (empty($signature)) {
    http_response_code(400);
    logWebhookActivity('Missing signature header', [], 'error');
    die("Missing signature header");
}

// Verify the webhook signature
if (!verifyWebhookSignature($rawPayload, $signature, $webhookSecret)) {
    http_response_code(401);
    logWebhookActivity('Invalid webhook signature', [
        'received_signature' => $signature,
        'payload_length' => strlen($rawPayload)
    ], 'error');
    die("Invalid signature");
}

// Parse the JSON payload
$webhookData = json_decode($rawPayload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    logWebhookActivity('Invalid JSON payload', [
        'json_error' => json_last_error_msg(),
        'payload' => substr($rawPayload, 0, 500)
    ], 'error');
    die("Invalid JSON");
}

// Log the received webhook
logWebhookActivity('Webhook received', [
    'event_type' => $webhookData['event'] ?? 'unknown',
    'transaction_id' => $webhookData['transaction']['id'] ?? 'unknown'
]);

// Check if this is a payment success event
$validSuccessEvents = ['payment.success', 'payment.completed', 'transaction.success', 'transaction.completed'];
$eventType = $webhookData['event'] ?? $webhookData['type'] ?? 'unknown';

if (!in_array($eventType, $validSuccessEvents)) {
    http_response_code(200);
    logWebhookActivity('Ignoring non-payment event', [
        'event_type' => $eventType
    ]);
    die("Event ignored");
}

// Extract transaction data (Hel.io webhook format)
$transaction = $webhookData['transaction'] ?? $webhookData;
$metadata = $transaction['metadata'] ?? [];

// Get required fields - handle both possible formats
$transactionId = $transaction['id'] ?? $transaction['transactionId'] ?? '';
$amount = $transaction['amount'] ?? $transaction['paidAmount'] ?? 0;
$currency = $transaction['currency'] ?? $transaction['paidCurrency'] ?? '';
$status = $transaction['status'] ?? 'completed';
$invoiceId = $metadata['invoice_id'] ?? $transaction['invoice_id'] ?? '';

// Validate required fields
if (empty($transactionId) || empty($invoiceId) || $amount <= 0) {
    http_response_code(400);
    logWebhookActivity('Missing required transaction data', [
        'transaction_id' => $transactionId,
        'invoice_id' => $invoiceId,
        'amount' => $amount
    ], 'error');
    die("Missing required data");
}

// Validate invoice ID
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

if (!$invoiceId) {
    http_response_code(400);
    logWebhookActivity('Invalid invoice ID', [
        'provided_invoice_id' => $metadata['invoice_id'] ?? 'none'
    ], 'error');
    die("Invalid invoice ID");
}

// Check if transaction has already been processed
$existingTransaction = checkCbTransID($transactionId);

if ($existingTransaction) {
    http_response_code(200);
    logWebhookActivity('Transaction already processed', [
        'transaction_id' => $transactionId,
        'invoice_id' => $invoiceId
    ]);
    die("Transaction already processed");
}

// Verify payment status - be more flexible with status values
$validStatuses = ['completed', 'confirmed', 'success', 'paid'];
if (!in_array(strtolower($status), $validStatuses)) {
    http_response_code(400);
    logWebhookActivity('Payment not completed', [
        'transaction_id' => $transactionId,
        'status' => $status,
        'invoice_id' => $invoiceId
    ], 'error');
    die("Payment not completed");
}

try {
    // Add the payment to WHMCS
    $paymentSuccess = addInvoicePayment(
        $invoiceId,
        $transactionId,
        $amount,
        0, // No fees
        $gatewayModuleName
    );

    if ($paymentSuccess) {
        // Log successful payment
        logTransaction($gatewayParams['name'], [
            'Transaction ID' => $transactionId,
            'Invoice ID' => $invoiceId,
            'Amount' => $amount,
            'Currency' => $currency,
            'Status' => $status,
            'Crypto Address' => $transaction['crypto_address'] ?? 'N/A',
            'Crypto Amount' => $transaction['crypto_amount'] ?? 'N/A',
            'Crypto Currency' => $transaction['crypto_currency'] ?? 'N/A',
            'Network' => $transaction['network'] ?? 'N/A',
            'Block Hash' => $transaction['block_hash'] ?? 'N/A',
            'Confirmations' => $transaction['confirmations'] ?? 'N/A'
        ], 'Successful');

        logWebhookActivity('Payment processed successfully', [
            'transaction_id' => $transactionId,
            'invoice_id' => $invoiceId,
            'amount' => $amount
        ]);

        http_response_code(200);
        echo "Payment processed successfully";
        
    } else {
        throw new Exception("Failed to add payment to invoice");
    }

} catch (Exception $e) {
    // Log the error
    logWebhookActivity('Payment processing failed', [
        'transaction_id' => $transactionId,
        'invoice_id' => $invoiceId,
        'error' => $e->getMessage()
    ], 'error');

    logTransaction($gatewayParams['name'], [
        'Transaction ID' => $transactionId,
        'Invoice ID' => $invoiceId,
        'Error' => $e->getMessage(),
        'Raw Data' => $rawPayload
    ], 'Failed');

    http_response_code(500);
    die("Payment processing failed: " . $e->getMessage());
}
?>