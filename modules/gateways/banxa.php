<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function banxa_MetaData()
{
    return array(
        'DisplayName' => 'banxa',
        'DisableLocalCreditCardInput' => true,
    );
}

function banxa_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'banxa',
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
            'Description' => 'Insert your USDC Polygon Wallet address.',
        ),
    );
}

function banxa_link($params)
{
    $walletAddress = $params['wallet_address'];
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
	$email = $params['clientdetails']['email'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/banxa.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$paygatedotto_banxacom_currency = $params['currency'];
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId;
	$paygatedotto_banxacom_final_total = $amount;
				
$paygatedotto_banxacom_gen_wallet = file_get_contents('https://api.paygate.to/control/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$paygatedotto_banxacom_wallet_decbody = json_decode($paygatedotto_banxacom_gen_wallet, true);

 // Check if decoding was successful
    if ($paygatedotto_banxacom_wallet_decbody && isset($paygatedotto_banxacom_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedotto_banxacom_gen_addressIn = $paygatedotto_banxacom_wallet_decbody['address_in'];
        $paygatedotto_banxacom_gen_polygon_addressIn = $paygatedotto_banxacom_wallet_decbody['polygon_address_in'];
		$paygatedotto_banxacom_gen_callback = $paygatedotto_banxacom_wallet_decbody['callback_url'];
		
		
		 // Update the invoice description to include address_in
            $invoiceDescription = "Payment reference number: $paygatedotto_banxacom_gen_polygon_addressIn";

            // Update the invoice with the new description
            $invoice = localAPI("GetInvoice", array('invoiceid' => $invoiceId), null);
            $invoice['notes'] = $invoiceDescription;
            localAPI("UpdateInvoice", $invoice);

		
		
    } else {
return "Error: Payment could not be processed, please try again (wallet address error)";
    }
	
	
        $paymentUrl = 'https://checkout.paygate.to/process-payment.php?address=' . $paygatedotto_banxacom_gen_addressIn . '&amount=' . $paygatedotto_banxacom_final_total . '&provider=banxa&email=' . urlencode($email) . '&currency=' . $paygatedotto_banxacom_currency;

        // Properly encode attributes for HTML output
        return '<a href="' . $paymentUrl . '" class="btn btn-primary" rel="noreferrer">' . $params['langpaynow'] . '</a>';
}

function banxa_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'banxa gateway activated successfully.');
}

function banxa_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'banxa gateway deactivated successfully.');
}

function banxa_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function banxa_output($vars)
{
    // Output additional information if needed
}

function banxa_error($vars)
{
    // Handle errors if needed
}
