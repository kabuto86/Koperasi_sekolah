<?php
// WhatsApp Cloud API (Meta). Kunci = phone_number_id:access_token. Mesej teks biasa.
// Nota: mesej yang dimulakan perniagaan di luar tetingkap 24 jam memerlukan templat yang diluluskan Meta (PELAN 13 — tertangguh).
class WhatsappCloudSender extends MessageSender
{
    const CODE = 'whatsapp_cloud';
    const BASE_URL = 'https://graph.facebook.com/v19.0';

    public function send($phone, $text)
    {
        $this->requireKey();
        $payload = json_encode(['messaging_product' => 'whatsapp', 'to' => $phone, 'type' => 'text', 'text' => ['body' => $text]]);
        $response = $this->http('POST', $this->baseUrl() . '/' . rawurlencode($this->keyId) . '/messages', $payload,
            ['Content-Type: application/json', 'Authorization: Bearer ' . $this->keySecret]);
        if ($response['status'] !== 200 || empty($response['json']['messages'][0]['id'])) {
            throw new MessagingException(self::CODE . ': HTTP ' . $response['status'] . ' ' . mb_substr($response['body'], 0, 150, 'UTF-8'));
        }
        return (string) $response['json']['messages'][0]['id'];
    }
}
