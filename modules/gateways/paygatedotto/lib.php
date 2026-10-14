<?php
/**
 * PayGate.to card gateways (paygatedottohosted, paygatedottocustom) - callback security.
 *
 * Two independent checks protect every payment callback:
 *
 *  1. A per-invoice HMAC in the callback URL (?invoice_id=..&sig=..). The key is a
 *     random secret generated on first use and stored only in this WHMCS database,
 *     so nobody outside the server can compute it. (Older versions derived the key
 *     from the payout wallet address, which is public; those signatures are still
 *     recognised so invoices already in progress can settle, see check 2.)
 *
 *  2. PayGate's RSA signature (X-PayGate-Signature / X-PayGate-Key-Id) over the exact
 *     URL PayGate requested, verified with the public key shipped in ./keys/. This
 *     proves the callback came from PayGate and that no parameter (notably
 *     value_coin) was changed. It is required on every callback.
 *
 * Scheme: https://paygate.to/docs/payment-gateway-api/#callback-event
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

if (!defined('PAYGATEDOTTO_KEYS_DIR')) {
    define('PAYGATEDOTTO_KEYS_DIR', __DIR__ . '/keys');
}

/**
 * Random per-installation secret for callback HMACs, created on first use and kept
 * in the mod_paygatedotto table (never shown to clients, never sent anywhere).
 */
function paygatedotto_cb_secret()
{
    static $secret = null;
    if ($secret !== null) {
        return $secret;
    }

    try {
        if (!Capsule::schema()->hasTable('mod_paygatedotto')) {
            Capsule::schema()->create('mod_paygatedotto', function ($table) {
                $table->string('name', 64)->primary();
                $table->text('value');
            });
        }
    } catch (\Exception $e) {
        // Another request may have created the table at the same moment.
    }

    $value = Capsule::table('mod_paygatedotto')->where('name', 'callback_secret')->value('value');
    if (!is_string($value) || strlen($value) < 32) {
        $value = bin2hex(random_bytes(32));
        try {
            Capsule::table('mod_paygatedotto')->insert(array('name' => 'callback_secret', 'value' => $value));
        } catch (\Exception $e) {
            // Lost a race with a parallel request: use the secret it stored.
            $value = Capsule::table('mod_paygatedotto')->where('name', 'callback_secret')->value('value');
        }
    }

    $secret = (string) $value;
    return $secret;
}

/**
 * Callback signature for an invoice. Deterministic, so every page view of the same
 * invoice registers the same callback URL (PayGate then returns the same address).
 */
function paygatedotto_cb_sig($invoiceId)
{
    return hash_hmac('sha256', 'invoice:' . (int) $invoiceId, paygatedotto_cb_secret());
}

/**
 * True if $sig is this invoice's callback signature, either the current one or the
 * one older versions of the module generated (accepted for invoices already in
 * progress; those callbacks must still carry a valid PayGate RSA signature).
 */
function paygatedotto_cb_sig_ok($invoiceId, $walletAddress, $sig)
{
    $sig = (string) $sig;
    if ($sig === '') {
        return false;
    }
    if (hash_equals(paygatedotto_cb_sig($invoiceId), $sig)) {
        return true;
    }
    $legacy = hash_hmac('sha256', (string) $invoiceId, hash('sha256', 'paygate_salt_' . $walletAddress));
    return hash_equals($legacy, $sig);
}

/**
 * Verify PayGate's RSA signature on the current request.
 *
 * $registeredBase is the scheme, host and path of the callback URL the module
 * registered (no query). The signed string is that base plus the raw query string
 * exactly as received; nothing is rebuilt from parsed parameters.
 *
 * @return array verified (bool), reason (string)
 */
function paygatedotto_verify_paygate_signature($registeredBase)
{
    $b64 = isset($_SERVER['HTTP_X_PAYGATE_SIGNATURE']) ? trim((string) $_SERVER['HTTP_X_PAYGATE_SIGNATURE']) : '';
    if ($b64 === '') {
        return array('verified' => false, 'reason' => 'signature_absent');
    }
    if (!function_exists('openssl_verify')) {
        return array('verified' => false, 'reason' => 'openssl_unavailable');
    }

    $keyId = isset($_SERVER['HTTP_X_PAYGATE_KEY_ID']) ? trim((string) $_SERVER['HTTP_X_PAYGATE_KEY_ID']) : '';
    if ($keyId === '') {
        $keyId = 'v1';
    }
    if (!preg_match('/^[A-Za-z0-9_-]{1,32}$/', $keyId)) {
        return array('verified' => false, 'reason' => 'unknown_key_id');
    }
    $pemFile = PAYGATEDOTTO_KEYS_DIR . '/paygate_callback_public_' . $keyId . '.pem';
    $pem = is_readable($pemFile) ? file_get_contents($pemFile) : false;
    if (!is_string($pem) || $pem === '') {
        return array('verified' => false, 'reason' => 'unknown_key_id');
    }
    $publicKey = openssl_pkey_get_public($pem);
    if ($publicKey === false) {
        return array('verified' => false, 'reason' => 'bad_public_key');
    }

    $raw = base64_decode($b64, true);
    if ($raw === false || $raw === '') {
        return array('verified' => false, 'reason' => 'signature_not_base64');
    }

    $query  = isset($_SERVER['QUERY_STRING']) ? (string) $_SERVER['QUERY_STRING'] : '';
    $signed = ($query === '') ? $registeredBase : $registeredBase . '?' . $query;

    $result = openssl_verify($signed, $raw, $publicKey, OPENSSL_ALGO_SHA256);
    if ($result === 1) {
        return array('verified' => true, 'reason' => '');
    }
    return array('verified' => false, 'reason' => ($result === -1) ? 'openssl_error' : 'signature_mismatch');
}

/**
 * Stop the callback with an HTTP status and a short plain-text reason.
 */
function paygatedotto_cb_fail($status, $message)
{
    $texts = array(400 => 'Bad Request', 403 => 'Forbidden', 404 => 'Not Found', 409 => 'Conflict', 502 => 'Bad Gateway');
    header('HTTP/1.1 ' . (int) $status . ' ' . (isset($texts[$status]) ? $texts[$status] : 'Error'));
    header('Content-Type: text/plain');
    echo $message;
    exit;
}
