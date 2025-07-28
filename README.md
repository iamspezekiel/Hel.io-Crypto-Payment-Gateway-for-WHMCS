# Hel.io Crypto Payment Gateway for WHMCS

A comprehensive WHMCS payment gateway module that integrates with Hel.io's cryptocurrency payment platform, enabling seamless crypto payments through their JavaScript widget.

## 📋 Overview

This module allows WHMCS users to accept cryptocurrency payments via Hel.io's secure payment platform. It features a popup widget interface for smooth user experience and webhook-based payment confirmation for reliable transaction processing.

### Key Features

- ✅ **Popup Widget Integration** - Uses Hel.io's JavaScript widget for seamless payment experience
- ✅ **Multi-Currency Support** - Accepts payments in various cryptocurrencies
- ✅ **Webhook Verification** - Secure signature-based webhook validation
- ✅ **Real-time Updates** - Automatic invoice status updates upon payment confirmation
- ✅ **Test Mode Support** - Development-friendly testing capabilities
- ✅ **Transaction Logging** - Comprehensive logging for debugging and audit trails
- ✅ **WHMCS 8.0+ Compatible** - Built for modern WHMCS versions

## 📁 File Structure

```
/modules/gateways/
├── helio.php                    # Main gateway module
└── callback/
    └── helio.php               # Webhook callback handler
```

## 🚀 Installation

### Step 1: Download and Extract Files

1. Download the module files
2. Extract the contents to maintain the directory structure

### Step 2: Upload Files to WHMCS

Upload the files to your WHMCS installation directory:

```bash
# Upload main gateway file
/path/to/whmcs/modules/gateways/helio.php

# Upload callback handler
/path/to/whmcs/modules/gateways/callback/helio.php
```

### Step 3: Set File Permissions

Ensure proper file permissions (typically 644):

```bash
chmod 644 /path/to/whmcs/modules/gateways/helio.php
chmod 644 /path/to/whmcs/modules/gateways/callback/helio.php
```

## ⚙️ Configuration

### Step 1: Activate the Gateway in WHMCS

1. Log in to your WHMCS Admin Area
2. Navigate to **Setup → Payments → Payment Gateways**
3. Find "Hel.io Crypto Gateway" in the list
4. Click **Activate**

### Step 2: Configure Gateway Settings

Fill in the following configuration fields:

| Field | Description | Required |
|-------|-------------|----------|
| **Public Key** | Your Hel.io public API key | ✅ Yes |
| **Webhook Secret** | Secret key for webhook signature verification | ✅ Yes |
| **Test Mode** | Enable for development/testing | ❌ Optional |

### Step 3: Configure Webhook URL in Hel.io

1. Log in to your Hel.io dashboard
2. Navigate to your API/Webhook settings
3. Set the webhook URL to: `https://yourdomain.com/modules/gateways/callback/helio.php`
4. Enable the following events:
   - `payment.success`
   - `payment.failed` (optional)
5. Set the webhook secret to match the one in your WHMCS configuration

## 🔧 How It Works

### Payment Flow

1. **Invoice Generation**: Customer receives an invoice with Hel.io as payment option
2. **Payment Initiation**: Customer clicks "Pay with Crypto via Hel.io"
3. **Widget Display**: Hel.io's popup widget opens with payment details
4. **Crypto Payment**: Customer completes payment using their preferred cryptocurrency
5. **Webhook Notification**: Hel.io sends payment confirmation to your webhook URL
6. **Invoice Update**: WHMCS automatically marks the invoice as paid
7. **Customer Redirect**: Customer is redirected back to WHMCS with confirmation

### Security Features

- **Signature Verification**: All webhooks are verified using HMAC-SHA256 signatures
- **Transaction Deduplication**: Prevents duplicate payment processing
- **Input Validation**: Comprehensive validation of all incoming data
- **Error Logging**: Detailed logging for troubleshooting and audit purposes

## 🛠️ Advanced Configuration

### Custom Styling

The payment interface includes default styling, but you can customize it by modifying the CSS in the `helio_link()` function within `helio.php`.

### Webhook Events

Currently supported events:
- `payment.success` - Payment completed successfully
- Other events are logged but ignored

### Error Handling

The module includes comprehensive error handling:
- Invalid signatures return HTTP 401
- Missing data returns HTTP 400
- Processing errors return HTTP 500
- All errors are logged for debugging

## 🔍 Troubleshooting

### Common Issues

**1. Payments not updating invoices**
- Verify webhook URL is correctly configured in Hel.io
- Check webhook secret matches between WHMCS and Hel.io
- Review WHMCS activity logs for error messages

**2. JavaScript widget not loading**
- Ensure `https://pay.hel.io/v1/pay.js` is accessible
- Check browser console for JavaScript errors
- Verify public key is correctly configured

**3. Webhook signature verification failing**
- Confirm webhook secret is identical in both systems
- Check for extra whitespace in configuration fields
- Verify Hel.io is sending the `X-HELIO-SIGNATURE` header

### Debugging

1. **Enable Debug Logging**: Check WHMCS Activity Log under **Utilities → Logs → Module Log**
2. **Webhook Testing**: Use Hel.io's webhook testing tools to verify connectivity
3. **JavaScript Console**: Check browser console for client-side errors

### Log Locations

- **Gateway Activity**: WHMCS Admin → Utilities → Logs → Gateway Log
- **Module Activity**: WHMCS Admin → Utilities → Logs → Module Log
- **System Activity**: WHMCS Admin → Utilities → Logs → Activity Log

## 📝 Development

### Testing

1. Enable **Test Mode** in gateway configuration
2. Use Hel.io's testnet/sandbox environment
3. Test with small amounts first
4. Verify webhook functionality with test transactions

## Features

- Lightweight and easy to set up
- Uses Hel.io's official payment widget
- Supports automatic callback/webhook verification
- Secure, reusable, and dynamic (host URL auto-detected)

### Customization

The module is designed to be easily customizable:
- Modify `helio_link()` for custom payment interfaces
- Extend `helio_config()` for additional settings
- Update webhook handler for custom processing logic

##  Support

### Getting Help

1. **WHMCS Documentation**: Review WHMCS gateway development docs
2. **Hel.io Support**: Contact Hel.io for API-related issues
3. **Community Forums**: WHMCS and cryptocurrency payment communities

### Reporting Issues

When reporting issues, please include:
- WHMCS version
- Module version
- Error messages from logs
- Steps to reproduce the issue
- Browser and version (for widget issues)

## 📄 License

This module is released under the MIT License.

```
MIT License

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

## 👨‍💻 Author

**WHMCS Community**
- Created for the WHMCS community
- Contributions welcome
- Open-source development

**Contact Author**
- **Sylvanus P. Ezekiel**  
- Email: `iamspezekiel@gmail.com`  
- Website: [https://iamspezekiel.pw](https://iamspezekiel.pw)
- Donate Via PayPal: [https://iamspezekiel.pw/paypal](https://isabi.click/DMgs)
- Donate Via Crypto: [https://iamspezekiel.pw/usdc](https://app.hel.io/pay/6887f3a2102ee9501a1d61e3)

## 📈 Version History

### v1.0.0
- Initial release
- Hel.io JavaScript widget integration
- Webhook-based payment confirmation
- WHMCS 8.0+ compatibility
- Comprehensive error handling and logging
