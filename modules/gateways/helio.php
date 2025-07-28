<?php
/**
 * Hel.io Crypto Payment Gateway for WHMCS
 * 
 * This module integrates WHMCS with Hel.io's cryptocurrency payment platform
 * using their JavaScript widget for seamless crypto payments.
 * 
 * @author WHMCS Community
 * @version 1.0.0
 * @license MIT
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Define module related meta data.
 */
function helio_MetaData()
{
    return [
        'DisplayName' => 'Hel.io Crypto Gateway',
        'APIVersion' => '1.1',
        'DisableLocalCreditCardInput' => true,
    ];
}

/**
 * Define gateway configuration options.
 */
function helio_config()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Hel.io Crypto Payment Gateway',
        ],
        'PublicKey' => [
            'FriendlyName' => 'Public Key',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Enter your Hel.io Public Key here',
        ],
        'WebhookSecret' => [
            'FriendlyName' => 'Webhook Secret',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Enter your Hel.io Webhook Secret for signature verification',
        ],
        'TestMode' => [
            'FriendlyName' => 'Test Mode',
            'Type' => 'yesno',
            'Description' => 'Enable test mode for development',
        ],
    ];
}

/**
 * Payment link generation.
 * 
 * This function generates the payment interface using Hel.io's JavaScript widget.
 * It creates a popup widget that handles the crypto payment process.
 */
function helio_link($params)
{
    // Gateway configuration
    $publicKey = $params['PublicKey'];
    $testMode = $params['TestMode'];
    
    // Invoice parameters
    $invoiceId = $params['invoiceid'];
    $amount = $params['amount'];
    $currency = $params['currency'];
    $returnUrl = $params['returnurl'];
    $systemUrl = $params['systemurl'];
    
    // Client details
    $clientName = $params['clientdetails']['firstname'] . ' ' . $params['clientdetails']['lastname'];
    $clientEmail = $params['clientdetails']['email'];
    
    // Webhook URL for payment notifications
    $webhookUrl = $systemUrl . '/modules/gateways/callback/helio.php';
    
    // Generate unique transaction reference
    $transactionRef = 'WHMCS-' . $invoiceId . '-' . time();
    
    // Create the payment form with Hel.io widget
    $htmlOutput = '
    <div id="helio-payment-container">
        <div class="helio-payment-info" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
            <h4 style="margin: 0 0 10px 0;">Pay with Cryptocurrency</h4>
            <p style="margin: 0; color: #666;">Amount: <strong>' . number_format($amount, 2) . ' ' . strtoupper($currency) . '</strong></p>
            <p style="margin: 5px 0 0 0; color: #666;">Invoice: <strong>#' . $invoiceId . '</strong></p>
        </div>
        
        <button id="helio-pay-button" class="btn btn-primary btn-lg" style="width: 100%; padding: 12px;">
            Pay with Crypto via Hel.io
        </button>
        
        <div id="helio-payment-status" style="margin-top: 15px; display: none;"></div>
    </div>

    <script src="https://pay.hel.io/v1/pay.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const payButton = document.getElementById("helio-pay-button");
        const statusDiv = document.getElementById("helio-payment-status");
        
        payButton.addEventListener("click", function() {
            // Initialize Hel.io payment widget
            const helio = new HelioCheckout({
                publicKey: "' . htmlspecialchars($publicKey) . '",
                amount: ' . $amount . ',
                currency: "' . strtoupper($currency) . '",
                ' . ($testMode ? 'testMode: true,' : '') . '
                metadata: {
                    invoice_id: "' . $invoiceId . '",
                    transaction_ref: "' . $transactionRef . '",
                    client_name: "' . htmlspecialchars($clientName) . '",
                    client_email: "' . htmlspecialchars($clientEmail) . '",
                    webhook_url: "' . $webhookUrl . '"
                },
                onSuccess: function(transaction) {
                    statusDiv.innerHTML = "<div class=\"alert alert-success\">Payment successful! Redirecting...</div>";
                    statusDiv.style.display = "block";
                    
                    // Store transaction ID for reference
                    console.log("Hel.io Transaction ID:", transaction.id);
                    
                    // Redirect back to WHMCS after short delay
                    setTimeout(function() {
                        window.location.href = "' . $returnUrl . '&transaction_id=" + transaction.id;
                    }, 2000);
                },
                onError: function(error) {
                    statusDiv.innerHTML = "<div class=\"alert alert-danger\">Payment failed: " + error.message + "</div>";
                    statusDiv.style.display = "block";
                    console.error("Hel.io Payment Error:", error);
                },
                onCancel: function() {
                    statusDiv.innerHTML = "<div class=\"alert alert-warning\">Payment cancelled by user.</div>";
                    statusDiv.style.display = "block";
                }
            });
            
            // Open the payment popup
            helio.open();
        });
    });
    </script>
    
    <style>
    .helio-payment-info {
        border-left: 4px solid #007bff;
    }
    
    #helio-pay-button {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        font-weight: bold;
        transition: all 0.3s ease;
    }
    
    #helio-pay-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    #helio-pay-button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    </style>';
    
    return $htmlOutput;
}

/**
 * Refund transaction.
 * 
 * Called when a refund is requested for a transaction.
 * Note: Hel.io may not support automatic refunds depending on the cryptocurrency used.
 */
function helio_refund($params)
{
    // Transaction and gateway parameters
    $transactionId = $params['transid'];
    $refundAmount = $params['amount'];
    $currency = $params['currency'];
    
    // Log the refund attempt
    logTransaction($params['paymentmethod'], [
        'Transaction ID' => $transactionId,
        'Refund Amount' => $refundAmount,
        'Currency' => $currency,
        'Status' => 'Refund Requested - Manual Processing Required'
    ], 'Refund Request');
    
    // Return failure with message as crypto refunds typically require manual processing
    return [
        'status' => 'error',
        'rawdata' => 'Cryptocurrency refunds require manual processing. Please contact Hel.io support with transaction ID: ' . $transactionId,
        'transid' => $transactionId,
    ];
}
?>