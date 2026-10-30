<?php
// Tambah / sunting berita (vendor/news/save — PELAN 18.13): tajuk ≤ 150, isi wajib, tarikh terbit 'YYYY-MM-DDTHH:MM'.
// Gambar: data URL = baharu (Uploader) · kosong = buang · URL sedia ada = kekal. E-mel yang sudah dihantar tidak dihantar semula.
class SaveNewsJob
{
    private $vendorId;
    private $cooperativeId;
    private $userId;
    private $input;

    public function __construct($vendorId, $cooperativeId, $userId, array $input)
    {
        $this->vendorId = (int) $vendorId;
        $this->cooperativeId = (int) $cooperativeId;
        $this->userId = (int) $userId;
        $this->input = $input;
    }

    public function run()
    {
        $id = (int) ($this->input['id'] ?? 0);
        $current = $id > 0 ? NewsPosts::find($this->vendorId, $this->cooperativeId, $id) : null;
        if ($id > 0 && $current === null) {
            throw new ValidationException(t('error.not_found'), [], 404);
        }
        $data = $this->validate();
        $oldImage = $current['image_path'] ?? null;
        $newImage = $this->saveImage($oldImage);

        Database::beginTransaction();
        try {
            $values = [$data['title'], $data['body'], $newImage, $data['published_at'], $data['is_active']];
            if ($current !== null) {
                // E-mel sudah dihantar → send_email kekal 1
                Database::execute(
                    "UPDATE news_posts SET title = ?, body = ?, image_path = ?, published_at = ?, is_active = ?,
                            send_email = IF(email_sent_at IS NULL, ?, 1)
                      WHERE id = ? AND vendor_id = ? AND cooperative_id = ?",
                    array_merge($values, [$data['send_email'], $id, $this->vendorId, $this->cooperativeId])
                );
            } else {
                $id = Database::insert(
                    "INSERT INTO news_posts (vendor_id, cooperative_id, title, body, image_path, published_at, is_active, send_email)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    array_merge([$this->vendorId, $this->cooperativeId], $values, [$data['send_email']])
                );
            }
            $saved = NewsPosts::find($this->vendorId, $this->cooperativeId, $id);
            $queued = NewsPosts::queueEmail($saved);
            PushNotifications::news($saved);
            (new WriteActivityLogJob($this->vendorId, $this->cooperativeId, $this->userId, 'news_save', 'news_post', $id, $data['title']))->run();
            Database::commit();
        } catch (Throwable $exception) {
            Database::rollBack();
            if ($newImage !== $oldImage) {
                Uploader::delete($newImage);
            }
            throw $exception;
        }
        if ($oldImage && $oldImage !== $newImage) {
            Uploader::delete($oldImage);
        }
        return ['post' => NewsPosts::shape(NewsPosts::find($this->vendorId, $this->cooperativeId, $id)), 'emails_queued' => $queued];
    }

    private function validate()
    {
        $v = new Validator($this->input);
        $v->required('title')->maxLength('title', 150)
          ->required('body')->maxLength('body', 10000)
          ->required('published_at')
          ->pattern('published_at', '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/');
        $published = trim((string) ($this->input['published_at'] ?? ''));
        $time = DateTime::createFromFormat('Y-m-d\TH:i', substr($published, 0, 16));
        if ($published !== '' && ($time === false || $time->format('Y-m-d\TH:i') !== substr($published, 0, 16))) {
            $v->addError('published_at', t('error.date'));
        }
        ValidationException::throwIfFails($v);
        return [
            'title'        => trim((string) $this->input['title']),
            'body'         => trim((string) $this->input['body']),
            'published_at' => $time->format('Y-m-d H:i:00'),
            'is_active'    => !empty($this->input['is_active']) ? 1 : 0,
            'send_email'   => !empty($this->input['send_email']) ? 1 : 0,
        ];
    }

    // image_url: data URL = gambar baharu · kosong = buang · URL sedia ada = kekal
    private function saveImage($oldImage)
    {
        $value = $this->input['image_url'] ?? null;
        if (Uploader::isDataUrl($value)) {
            return Uploader::saveImage($value, $this->vendorId . '/' . $this->cooperativeId . '/news', 'image_url');
        }
        if ($value === null || $value === '') {
            return null;
        }
        return $oldImage;
    }
}
