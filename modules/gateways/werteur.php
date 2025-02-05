<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function werteur_MetaData()
{
    return array(
        'DisplayName' => 'werteur',
        'DisableLocalCreditCardInput' => true,
    );
}

function werteur_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'werteur',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using wert.io (EUR).',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'wallet_address' => array(
            'FriendlyName' => 'USDC Polygon Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your USDC (Polygon) wallet address to receive instant payouts. Payouts maybe sent in USDC or USDT (Polygon or BEP-20) or POL native token. Same wallet should work to receive all. Make sure you use a self-custodial wallet to receive payouts.',
        ),
    );
}

function werteur_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
	$email = $params['clientdetails']['email'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/werteur.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$paygatedotto_wertioeur_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;

if ($paygatedotto_wertioeur_currency === 'EUR') {
        $paygatedotto_wertioeur_final_total = $amount;
		} else {
		
$paygatedotto_wertioeur_response = file_get_contents('https://api.paygate.to/crypto/erc20/eurc/convert.php?value=' . $amount . '&from=' . strtolower($paygatedotto_wertioeur_currency));


$paygatedotto_wertioeur_conversion_resp = json_decode($paygatedotto_wertioeur_response, true);

if ($paygatedotto_wertioeur_conversion_resp && isset($paygatedotto_wertioeur_conversion_resp['value_coin'])) {
    // Escape output
    $paygatedotto_wertioeur_final_total	= $paygatedotto_wertioeur_conversion_resp['value_coin'];      
} else {
	return "Error: Payment could not be processed, please try again (unsupported store currency)";
}	
		}
		
if ($paygatedotto_wertioeur_final_total < 1) {
return "Error: Invoice total must be €1 or more for the selected payment provider.";
}		
		
$paygatedotto_wertioeur_gen_wallet = file_get_contents('https://api.paygate.to/control/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$paygatedotto_wertioeur_wallet_decbody = json_decode($paygatedotto_wertioeur_gen_wallet, true);

 // Check if decoding was successful
    if ($paygatedotto_wertioeur_wallet_decbody && isset($paygatedotto_wertioeur_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedotto_wertioeur_gen_addressIn = $paygatedotto_wertioeur_wallet_decbody['address_in'];
        $paygatedotto_wertioeur_gen_polygon_addressIn = $paygatedotto_wertioeur_wallet_decbody['polygon_address_in'];
		$paygatedotto_wertioeur_gen_callback = $paygatedotto_wertioeur_wallet_decbody['callback_url'];
		
		
		 // Update the invoice description to include address_in
            $invoiceDescription = "Payment reference number: $paygatedotto_wertioeur_gen_polygon_addressIn";

            // Update the invoice with the new description
            $invoice = localAPI("GetInvoice", array('invoiceid' => $invoiceId), null);
            $invoice['notes'] = $invoiceDescription;
            localAPI("UpdateInvoice", $invoice);

		
		
    } else {
return "Error: Payment could not be processed, please try again (wallet address error)";
    }
	
	
        $paymentUrl = 'https://checkout.paygate.to/process-payment.php?address=' . $paygatedotto_wertioeur_gen_addressIn . '&amount=' . $paygatedotto_wertioeur_final_total . '&provider=werteur&email=' . urlencode($email) . '&currency=' . $paygatedotto_wertioeur_currency;

        // Properly encode attributes for HTML output
        return '<a href="' . $paymentUrl . '" class="btn btn-primary" rel="noreferrer">' . $params['langpaynow'] . '</a>';
}

function werteur_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'werteur gateway activated successfully.');
}

function werteur_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'werteur gateway deactivated successfully.');
}

function werteur_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function werteur_output($vars)
{
    // Output additional information if needed
}

function werteur_error($vars)
{
    // Handle errors if needed
}
