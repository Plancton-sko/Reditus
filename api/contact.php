<?php
// Reditus Contábil - endpoint simples de leads para hospedagem HostGator/cPanel.
// Requer PHP com função mail() habilitada. Para maior entregabilidade, prefira SMTP autenticado.

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Método não permitido.']);
    exit;
}

function clean($value, $max = 500) {
    $value = is_string($value) ? trim($value) : '';
    $value = strip_tags($value);
    return mb_substr($value, 0, $max);
}

// Honeypot anti-spam: bots normalmente preenchem este campo oculto.
if (!empty($_POST['website'])) {
    echo json_encode(['ok' => true]);
    exit;
}

$name = clean($_POST['name'] ?? $_POST['nome'] ?? '', 120);
$email = clean($_POST['email'] ?? '', 180);
$phone = clean($_POST['phone'] ?? $_POST['telefone'] ?? '', 40);
$company = clean($_POST['company'] ?? $_POST['empresa'] ?? '', 160);
$service = clean($_POST['service'] ?? '', 120);
$preference = clean($_POST['responsePreference'] ?? 'não informado', 30);
$message = clean($_POST['message'] ?? $_POST['mensagem'] ?? '', 800);
$state = clean($_POST['estado'] ?? '', 2);
$city = clean($_POST['municipio'] ?? '', 120);
$source = clean($_POST['source'] ?? 'site', 80);

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Preencha nome, e-mail e telefone corretamente.']);
    exit;
}

$recipient = 'contato@redituscontabil.com.br';
$subject = 'Nova solicitação de proposta - Reditus Contábil';

$body = "Nova solicitação recebida pelo site\n\n";
$body .= "Nome: {$name}\n";
$body .= "E-mail: {$email}\n";
$body .= "Telefone/WhatsApp: {$phone}\n";
$body .= "Empresa: {$company}\n";
$body .= "Serviço: {$service}\n";
$body .= "Preferência de retorno: {$preference}\n";
if ($state !== '' || $city !== '') $body .= "Localização: {$city}/{$state}\n";
$body .= "Origem: {$source}\n";
$body .= "Mensagem: {$message}\n";
$body .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'não disponível') . "\n";

$domain = $_SERVER['HTTP_HOST'] ?? 'redituscontabil.com.br';
$domain = preg_replace('/^www\./', '', preg_replace('/[^a-zA-Z0-9.-]/', '', $domain));
$headers = [
    'From: Site Reditus <no-reply@' . $domain . '>',
    'Reply-To: ' . $name . ' <' . $email . '>',
    'Content-Type: text/plain; charset=UTF-8',
    'MIME-Version: 1.0'
];

$sent = @mail($recipient, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));

if (!$sent) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Não foi possível enviar o e-mail neste servidor.']);
    exit;
}

echo json_encode(['ok' => true]);
