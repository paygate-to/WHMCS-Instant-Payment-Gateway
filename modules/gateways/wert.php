<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function wert_MetaData()
{
    return array(
        'DisplayName' => 'wert',
        'DisableLocalCreditCardInput' => true,
    );
}

function wert_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'wert',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using Credit/debit card (including MasterCard, Visa, and Apple Pay).',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'wallet_address' => array(
            'FriendlyName' => 'USDC Polygon Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your USDC (Polygon) wallet address to receive instant payouts. Payouts maybe sent in USDC or USDT (Polygon or BEP-20) or POL native token. Same wallet should work to receive all. Make sure you use a self-custodial wallet to receive payouts.',
        ),
    );
}

function wert_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
	$email = $params['clientdetails']['email'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/wert.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$paygatedotto_wertio_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

if ($paygatedotto_wertio_currency === 'USD') {
        $paygatedotto_wertio_final_total = $amount;
		} else {
		
$paygatedotto_wertio_response = file_get_contents('https://api.paygate.to/control/convert.php?value=' . $amount . '&from=' . strtolower($paygatedotto_wertio_currency));


$paygatedotto_wertio_conversion_resp = json_decode($paygatedotto_wertio_response, true);

if ($paygatedotto_wertio_conversion_resp && isset($paygatedotto_wertio_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_wertio_final_total	= $paygatedotto_wertio_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		}
		
		
if ($paygatedotto_wertio_final_total < 1) {
return "Error: Invoice total must be $1 USD or more for the selected payment provider.";
}	
		
$paygatedotto_wertio_gen_wallet = file_get_contents('https://api.paygate.to/control/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$paygatedotto_wertio_wallet_decbody = json_decode($paygatedotto_wertio_gen_wallet, true);

 // Check if decoding was successful
    if ($paygatedotto_wertio_wallet_decbody && isset($paygatedotto_wertio_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedotto_wertio_gen_addressIn = $paygatedotto_wertio_wallet_decbody['address_in'];
        $paygatedotto_wertio_gen_polygon_addressIn = $paygatedotto_wertio_wallet_decbody['polygon_address_in'];
		$paygatedotto_wertio_gen_callback = $paygatedotto_wertio_wallet_decbody['callback_url'];
		
		
		 // Update the invoice description to include address_in
            $invoiceDescription = "Payment reference number: $paygatedotto_wertio_gen_polygon_addressIn";

            // Update the invoice with the new description
            $invoice = localAPI("GetInvoice", array('invoiceid' => $invoiceId), null);
            $invoice['notes'] = $invoiceDescription;
            localAPI("UpdateInvoice", $invoice);

		
		
    } else {
return "Error: Payment could not be processed, please try again (wallet address error)";
    }
	
	
        $paymentUrl = 'https://checkout.paygate.to/process-payment.php?address=' . $paygatedotto_wertio_gen_addressIn . '&amount=' . $paygatedotto_wertio_final_total . '&provider=wert&email=' . urlencode($email) . '&currency=' . $paygatedotto_wertio_currency;

        // Properly encode attributes for HTML output
        return '<a href="' . $paymentUrl . '" class="btn btn-primary" rel="noreferrer">' . $params['langpaynow'] . '</a>';
}

function wert_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'wert gateway activated successfully.');
}

function wert_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'wert gateway deactivated successfully.');
}

function wert_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function wert_output($vars)
{
    // Output additional information if needed
}

function wert_error($vars)
{
    // Handle errors if needed
}
