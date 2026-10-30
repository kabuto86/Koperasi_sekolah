<?php
// Twilio (SMS atau WhatsApp). Kunci = account_sid:auth_token. Saluran whatsapp → awalan "whatsapp:" pada nombor.
class TwilioSender extends MessageSender
{
    const CODE = 'twilio';
    const BASE_URL = 'https://api.twilio.com/2010-04-01';

    public function send($phone, $text)
    {
        $this->requireKey();
        $prefix = $this->channel === 'whatsapp' ? 'whatsapp:+' : '+';
        $response = $this->http('POST', $this->baseUrl() . '/Accounts/' . rawurlencode($this->keyId) . '/Messages.json',
            ['To' => $prefix . $phone, 'From' => $prefix . $this->sender, 'Body' => $text], [], $this->keyId . ':' . $this->keySecret);
        if ($response['status'] < 200 || $response['status'] >= 300 || empty($response['json']['sid'])) {
            throw new MessagingException(self::CODE . ': HTTP ' . $response['status'] . ' ' . mb_substr($response['body'], 0, 150, 'UTF-8'));
        }
        return (string) $response['json']['sid'];
    }
}
