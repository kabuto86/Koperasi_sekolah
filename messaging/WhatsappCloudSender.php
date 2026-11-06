<?php
// WhatsApp Cloud API (Meta). Kunci = phone_number_id:access_token. Mesej teks biasa (send) atau mesej templat yang
// diluluskan Meta (sendTemplate — W3): wajib untuk mesej yang dimulakan perniagaan di luar tetingkap 24 jam.
class WhatsappCloudSender extends MessageSender
{
    const CODE = 'whatsapp_cloud';
    const BASE_URL = 'https://graph.facebook.com/v19.0';

    public function send($phone, $text)
    {
        $this->requireKey();
        return $this->post(['messaging_product' => 'whatsapp', 'to' => $phone, 'type' => 'text', 'text' => ['body' => $text]]);
    }

    // Templat Meta: {{1}}, {{2}}, … = $params ikut tertib (MessagingSettings::META_PARAMS)
    public function sendTemplate($phone, $name, $language, array $params)
    {
        $this->requireKey();
        $parameters = [];
        foreach ($params as $value) {
            $parameters[] = ['type' => 'text', 'text' => (string) $value];
        }
        return $this->post([
            'messaging_product' => 'whatsapp',
            'to'                => $phone,
            'type'              => 'template',
            'template'          => [
                'name'       => (string) $name,
                'language'   => ['code' => (string) $language],
                'components' => [['type' => 'body', 'parameters' => $parameters]],
            ],
        ]);
    }

    private function post(array $body)
    {
        $payload = json_encode($body, JSON_UNESCAPED_UNICODE);
        $response = $this->http('POST', $this->baseUrl() . '/' . rawurlencode($this->keyId) . '/messages', $payload,
            ['Content-Type: application/json', 'Authorization: Bearer ' . $this->keySecret]);
        if ($response['status'] !== 200 || empty($response['json']['messages'][0]['id'])) {
            throw new MessagingException(self::CODE . ': HTTP ' . $response['status'] . ' ' . mb_substr($response['body'], 0, 150, 'UTF-8'));
        }
        return (string) $response['json']['messages'][0]['id'];
    }
}
