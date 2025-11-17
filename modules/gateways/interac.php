<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function interac_MetaData()
{
    return array(
        'DisplayName' => 'interac',
        'DisableLocalCreditCardInput' => true,
    );
}

function interac_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'interac',
        ),
        'description' => array(
            'FriendlyName' => 'Description',
            'Type' => 'textarea',
            'Rows' => '3',
            'Cols' => '25',
            'Default' => 'Pay using Interac CAD.',
            'Description' => 'This controls the description which the user sees during checkout.',
        ),
        'custom_domain' => array(
            'FriendlyName' => 'Custom Domain',
            'Type' => 'text',
			'Default' => 'checkout.paygate.to',
            'Description' => 'Follow the custom domain guide to use your own domain name for the checkout pages and links.',
        ),
        'wallet_address' => array(
            'FriendlyName' => 'USDC Polygon Wallet Address',
            'Type' => 'text',
            'Description' => 'Insert your USDC (Polygon) wallet address to receive instant payouts. Payouts maybe sent in ETH or USDC or USDT (Polygon or BEP-20) or POL native token. Same wallet should work to receive all. Make sure you use a self-custodial wallet to receive payouts.',
        ),
    );
}

function interac_link($params)
{
    $walletAddress = $params['wallet_address'];
    $customDomain = rtrim(str_replace(['https://','http://'], '', $params['custom_domain']), '/');
    $amount = $params['amount'];
    $invoiceId = $params['invoiceid'];
	$email = $params['clientdetails']['email'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $redirectUrl = $systemUrl . '/modules/gateways/callback/interac.php';
	$invoiceLink = $systemUrl . '/viewinvoice.php?id=' . $invoiceId;
	$paygatedotto_interaccad_currency = $params['currency'];
	$secret = hash('sha256', 'paygate_salt_' . $walletAddress);
	$sig = hash_hmac('sha256', $invoiceId, $secret);
	$callback_URL = $redirectUrl . '?invoice_id=' . $invoiceId . '&sig=' . $sig;

if ($paygatedotto_interaccad_currency !== 'CAD') {
        return "Error: Invoice currency must be CAD";
		} else {
		$paygatedotto_interaccad_final_total = $amount;
		if ($paygatedotto_interaccad_final_total < 100) {
return "Error: Invoice total must be CA$100 or more for the selected payment provider.";
}
		}
		
		
		
		
$paygatedotto_interaccad_gen_wallet = file_get_contents('https://api.paygate.to/control/wallet.php?address=' . $walletAddress .'&callback=' . urlencode($callback_URL));


	$paygatedotto_interaccad_wallet_decbody = json_decode($paygatedotto_interaccad_gen_wallet, true);

 // Check if decoding was successful
    if ($paygatedotto_interaccad_wallet_decbody && isset($paygatedotto_interaccad_wallet_decbody['address_in'])) {
        // Store the address_in as a variable
        $paygatedotto_interaccad_gen_addressIn = $paygatedotto_interaccad_wallet_decbody['address_in'];
        $paygatedotto_interaccad_gen_polygon_addressIn = $paygatedotto_interaccad_wallet_decbody['polygon_address_in'];
		$paygatedotto_interaccad_gen_callback = $paygatedotto_interaccad_wallet_decbody['callback_url'];
		
		
		 // Update the invoice description to include address_in
            $invoiceDescription = "Payment reference number: $paygatedotto_interaccad_gen_polygon_addressIn";

            // Update the invoice with the new description
            $invoice = localAPI("GetInvoice", array('invoiceid' => $invoiceId), null);
            $invoice['notes'] = $invoiceDescription;
            localAPI("UpdateInvoice", $invoice);

		
		
    } else {
return "Error: Payment could not be processed, please try again (wallet address error)";
    }
	
	
        $paymentUrl = 'https://' . $customDomain . '/process-payment.php?address=' . $paygatedotto_interaccad_gen_addressIn . '&amount=' . $paygatedotto_interaccad_final_total . '&provider=interac&email=' . urlencode($email) . '&currency=' . $paygatedotto_interaccad_currency;

        // Properly encode attributes for HTML output
        return '<a href="' . $paymentUrl . '" class="btn btn-primary" rel="noreferrer">' . $params['langpaynow'] . '</a>';
}

function interac_activate()
{
    // You can customize activation logic if needed
    return array('status' => 'success', 'description' => 'interac gateway activated successfully.');
}

function interac_deactivate()
{
    // You can customize deactivation logic if needed
    return array('status' => 'success', 'description' => 'interac gateway deactivated successfully.');
}

function interac_upgrade($vars)
{
    // You can customize upgrade logic if needed
}

function interac_output($vars)
{
    // Output additional information if needed
}

function interac_error($vars)
{
    // Handle errors if needed
}
