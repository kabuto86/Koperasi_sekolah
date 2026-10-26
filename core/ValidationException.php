<?php
// Ralat yang dijangka dari job (input salah, pendua, kata laluan salah).
// Pengendali global dalam bootstrap.php menukarnya kepada Response::fail(mesej, errors, status) dalam api/.
//
//   throw new ValidationException(t('error.check_form'), ['email' => t('error.duplicate')]);
class ValidationException extends Exception
{
    private $errors;
    private $status;
    private $extra;

    public function __construct($message, $errors = [], $status = 422, $extra = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
        $this->status = $status;
        $this->extra = $extra;
    }

    public function errors()
    {
        return $this->errors;
    }

    public function status()
    {
        return $this->status;
    }

    public function extra()
    {
        return $this->extra;
    }

    // Pembantu: gagal jika Validator ada ralat
    public static function throwIfFails(Validator $validator)
    {
        if ($validator->fails()) {
            throw new self(t('error.check_form'), $validator->errors());
        }
    }
}
