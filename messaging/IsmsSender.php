<?php
// iSMS Malaysia (SMS sahaja). Kunci = nama_pengguna:kata_laluan. Jawapan "2000 = SUCCESS" bila berjaya.
class IsmsSender extends MessageSender
{
    const CODE = 'isms';
    const BASE_URL = 'https://www.isms.com.my';

    public function send($phone, $text)
    {
        $this->requireKey();
        $query = http_build_query([
            'un' => $this->keyId, 'pwd' => $this->keySecret, 'dstno' => $phone, 'msg' => $text,
            'type' => preg_match('/[^\x20-\x7E\r\n]/', $text) ? 2 : 1, 'sendid' => $this->sender, 'agreedterm' => 'YES',
        ]);
        $response = $this->http('GET', $this->baseUrl() . '/isms_send_all_id.php?' . $query);
        if ($response['status'] !== 200 || strpos($response['body'], '2000') !== 0) {
            throw new MessagingException(self::CODE . ': ' . mb_substr(trim($response['body']), 0, 150, 'UTF-8'));
        }
        return trim($response['body']);
    }
}
