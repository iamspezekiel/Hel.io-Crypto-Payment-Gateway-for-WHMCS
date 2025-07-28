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
        'PaylinkId' => [
            'FriendlyName' => 'Paylink ID',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Enter your Hel.io Paylink ID here',
        ],
        'WebhookSecret' => [
            'FriendlyName' => 'Webhook Secret',
            'Type' => 'text',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Enter your Hel.io Webhook Secret for signature verification',
        ],
        'ThemeMode' => [
            'FriendlyName' => 'Theme Mode',
            'Type' => 'dropdown',
            'Options' => [
                'light' => 'Light',
                'dark' => 'Dark',
            ],
            'Default' => 'light',
            'Description' => 'Choose the theme for the payment widget',
        ],
        'DisplayMode' => [
            'FriendlyName' => 'Display Mode',
            'Type' => 'dropdown',
            'Options' => [
                'inline' => 'Inline',
                'modal' => 'Modal/Popup',
            ],
            'Default' => 'inline',
            'Description' => 'Choose how the payment widget is displayed',
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
    $paylinkId = $params['PaylinkId'];
    $themeMode = $params['ThemeMode'];
    $displayMode = $params['DisplayMode'];
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
    
    // Validate required configuration
    if (empty($paylinkId)) {
        return '<div class="alert alert-danger">Hel.io gateway not properly configured. Please contact support.</div>';
    }
    
    // Generate unique container ID to avoid conflicts
    $containerId = 'helioCheckoutContainer_' . $invoiceId;
    $statusId = 'helioPaymentStatus_' . $invoiceId;
    
    // Create the payment form with Hel.io widget
    $htmlOutput = '
    <div id="helio-payment-wrapper-' . $invoiceId . '">
        <div class="helio-payment-info" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
            <h4 style="margin: 0 0 10px 0;">Pay with Cryptocurrency</h4>
            <p style="margin: 0; color: #666;">Amount: <strong>' . number_format($amount, 2) . ' ' . strtoupper($currency) . '</strong></p>
            <p style="margin: 5px 0 0 0; color: #666;">Invoice: <strong>#' . $invoiceId . '</strong></p>
        </div>
        
        <div id="' . $containerId . '" class="helio-checkout-container"></div>
        
        <div id="' . $statusId . '" class="helio-payment-status" style="margin-top: 15px; display: none;"></div>
    </div>

    <script type="module" crossorigin src="https://embed.hel.io/assets/index-v1.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // Wait for helioCheckout to be available
        const initializeHelio = () => {
            if (typeof window.helioCheckout === "undefined") {
                setTimeout(initializeHelio, 100);
                return;
            }
            
            const container = document.getElementById("' . $containerId . '");
            const statusDiv = document.getElementById("' . $statusId . '");
            
            if (!container) {
                console.error("Helio container not found");
                return;
            }
            
            try {
                window.helioCheckout(container, {
                    paylinkId: "' . htmlspecialchars($paylinkId) . '",
                    theme: {
                        themeMode: "' . htmlspecialchars($themeMode) . '"
                    },
                    amount: "' . number_format($amount, 2, '.', '') . '",
                    display: "' . htmlspecialchars($displayMode) . '",
                    metadata: {
                        invoice_id: "' . $invoiceId . '",
                        client_name: "' . htmlspecialchars($clientName) . '",
                        client_email: "' . htmlspecialchars($clientEmail) . '",
                        whmcs_system_url: "' . htmlspecialchars($systemUrl) . '"
                    },
                    onSuccess: function(event) {
                        console.log("Payment successful:", event);
                        statusDiv.innerHTML = "<div class=\"alert alert-success\">Payment successful! Redirecting...</div>";
                        statusDiv.style.display = "block";
                        
                        // Redirect back to WHMCS after short delay
                        setTimeout(function() {
                            window.location.href = "' . htmlspecialchars($returnUrl) . '&helio_success=1&transaction_id=" + (event.transactionId || event.id || "");
                        }, 2000);
                    },
                    onError: function(event) {
                        console.error("Payment error:", event);
                        statusDiv.innerHTML = "<div class=\"alert alert-danger\">Payment failed: " + (event.message || "Unknown error") + "</div>";
                        statusDiv.style.display = "block";
                    },
                    onPending: function(event) {
                        console.log("Payment pending:", event);
                        statusDiv.innerHTML = "<div class=\"alert alert-info\">Payment is being processed. Please wait...</div>";
                        statusDiv.style.display = "block";
                    },
                    onCancel: function() {
                        console.log("Payment cancelled");
                        statusDiv.innerHTML = "<div class=\"alert alert-warning\">Payment cancelled by user.</div>";
                        statusDiv.style.display = "block";
                    },
                    onStartPayment: function() {
                        console.log("Starting payment");
                        statusDiv.innerHTML = "<div class=\"alert alert-info\">Initializing payment...</div>";
                        statusDiv.style.display = "block";
                    }
                });
            } catch (error) {
                console.error("Error initializing Helio checkout:", error);
                statusDiv.innerHTML = "<div class=\"alert alert-danger\">Failed to initialize payment widget. Please refresh the page.</div>";
                statusDiv.style.display = "block";
            }
        };
        
        initializeHelio();
    });
    </script>
    
    <style>
    .helio-payment-info {
        border-left: 4px solid #007bff;
    }
    
    .helio-checkout-container {
        min-height: 200px;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .helio-payment-status .alert {
        padding: 12px 15px;
        margin-bottom: 0;
        border-radius: 4px;
        border: 1px solid transparent;
    }
    
    .helio-payment-status .alert-success {
        color: #155724;
        background-color: #d4edda;
        border-color: #c3e6cb;
    }
    
    .helio-payment-status .alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }
    
    .helio-payment-status .alert-warning {
        color: #856404;
        background-color: #fff3cd;
        border-color: #ffeaa7;
    }
    
    .helio-payment-status .alert-info {
        color: #0c5460;
        background-color: #d1ecf1;
        border-color: #bee5eb;
    }
    
    /* Responsive design */
    @media (max-width: 768px) {
        .helio-checkout-container {
            min-height: 300px;
        }
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