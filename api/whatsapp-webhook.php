<?php
/**
 * Automação básica para WhatsApp Business Platform (Cloud API / Meta).
 *
 * Configure estas variáveis no ambiente da hospedagem ou substitua os valores
 * com cuidado em um arquivo privado fora do public_html:
 *   WHATSAPP_VERIFY_TOKEN
 *   WHATSAPP_ACCESS_TOKEN
 *   WHATSAPP_PHONE_NUMBER_ID
 *
 * Depois cadastre esta URL como webhook na Meta:
 *   https://SEU-DOMINIO.com.br/api/whatsapp-webhook.php
 */

header('Content-Type: application/json; charset=utf-8');

$verifyToken = getenv('WHATSAPP_VERIFY_TOKEN') ?: '';
$accessToken = getenv('WHATSAPP_ACCESS_TOKEN') ?: '';
$phoneNumberId = getenv('WHATSAPP_PHONE_NUMBER_ID') ?: '';

// Verificação inicial exigida pela Meta.
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? '';

    if ($mode === 'subscribe' && $verifyToken !== '' && hash_equals($verifyToken, $token)) {
        header('Content-Type: text/plain; charset=utf-8');
        echo $challenge;
        exit;
    }

    http_response_code(403);
    echo json_encode(['ok' => false]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$message = $payload['entry'][0]['changes'][0]['value']['messages'][0] ?? null;

// Webhooks de status não precisam de resposta.
if (!$message) {
    echo json_encode(['ok' => true]);
    exit;
}

$from = $message['from'] ?? '';
$text = strtolower(trim($message['text']['body'] ?? ''));

if ($from === '' || $accessToken === '' || $phoneNumberId === '') {
    // Retorna 200 para evitar reenvios contínuos da Meta enquanto não estiver configurado.
    echo json_encode(['ok' => true, 'configured' => false]);
    exit;
}

function replyText($text) {
    if (preg_match('/\b(1|abrir|abertura|cnpj)\b/u', $text)) {
        return "Ótimo. Para abertura de empresa, informe por favor: cidade/UF, atividade principal e se terá sócios. Um especialista continuará o atendimento.";
    }
    if (preg_match('/\b(2|trocar|contador|migra)\b/u', $text)) {
        return "Para troca de contador, informe: regime tributário, faturamento aproximado e quantidade de funcionários. Nossa equipe avaliará a migração.";
    }
    if (preg_match('/\b(3|proposta|orçamento|orcamento|valor)\b/u', $text)) {
        return "Para preparar uma proposta, envie: nome da empresa, atividade, regime tributário, faturamento aproximado e número de funcionários. Não é necessário enviar documentos neste primeiro contato.";
    }
    if (preg_match('/\b(4|reforma|tribut)\b/u', $text)) {
        return "Para consultoria tributária, descreva brevemente sua atividade, regime atual e principal dúvida. Um especialista dará sequência ao atendimento.";
    }
    return "Olá! Sou o atendimento automático da Reditus Contábil. Para agilizar, responda com:\n1 - Abrir empresa\n2 - Trocar de contador\n3 - Receber proposta\n4 - Consultoria tributária\n\nSe preferir, escreva sua dúvida em uma mensagem.";
}

$responseText = replyText($text);
$url = 'https://graph.facebook.com/v23.0/' . rawurlencode($phoneNumberId) . '/messages';
$data = json_encode([
    'messaging_product' => 'whatsapp',
    'to' => $from,
    'type' => 'text',
    'text' => ['preview_url' => false, 'body' => $responseText]
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_TIMEOUT => 15
]);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode < 200 || $httpCode >= 300) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'meta_response' => $result]);
    exit;
}

echo json_encode(['ok' => true]);
