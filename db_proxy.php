<?php
/**
 * TURBO PROJECTS — DB Proxy
 * Leitet Requests von Claude / Hub an Google Apps Script weiter.
 * Upload nach: grelleforelle.com/hub-proxy/db_proxy.php
 */

// ── CORS Headers (erlaubt Claude Sandbox + Hub) ───────────────────────────────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ── Config ────────────────────────────────────────────────────────────────────
$APPS_SCRIPT_URL = 'https://script.google.com/macros/s/AKfycbzFArhOjO7g_yZNNQz_fMjdPmwD8qyeTw8KuN6aVhtFIsYn4p71F0E0vNrNOCEJjjW7gg/exec';
$PROXY_SECRET    = 'turbo_db_2026'; // muss im Request mitgeschickt werden

// ── Request lesen ─────────────────────────────────────────────────────────────
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit();
}

// Secret Check
if (empty($data['secret']) || $data['secret'] !== $PROXY_SECRET) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit();
}

// ── Forward an Apps Script ────────────────────────────────────────────────────
$ch = curl_init($APPS_SCRIPT_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($data),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_FOLLOWLOCATION => true,   // Apps Script redirectet
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(['ok' => false, 'error' => 'cURL error: ' . $curlError]);
    exit();
}

// Response direkt durchleiten
http_response_code($httpCode ?: 200);
echo $response;
