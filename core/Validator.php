<?php
// Pengesahan input. Mengumpul ralat ikut nama medan supaya UI boleh tanda is-invalid
// (bentuk { ok:false, errors: { medan: 'mesej' } } — PELAN 18.6).
//
//   $v = new Validator($input);
//   $v->required('name')->maxLength('name', 150)->email('email');
//   if ($v->fails()) { Response::fail(t('error.check_form'), $v->errors()); }
class Validator
{
    private $input;
    private $errors = [];

    public function __construct($input)
    {
        $this->input = is_array($input) ? $input : [];
    }

    public function required($field)
    {
        $value = $this->value($field);
        if ($value === null || $value === '' || $value === []) {
            $this->addError($field, t('error.required'));
        }
        return $this;
    }

    public function email($field)
    {
        $value = $this->value($field);
        if ($this->isFilled($value) && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->addError($field, t('auth.email_invalid'));
        }
        return $this;
    }

    public function maxLength($field, $max)
    {
        $value = $this->value($field);
        if ($this->isFilled($value) && mb_strlen((string) $value, 'UTF-8') > $max) {
            $this->addError($field, t('error.max_length', ['max' => $max]));
        }
        return $this;
    }

    public function minLength($field, $min)
    {
        $value = $this->value($field);
        if ($this->isFilled($value) && mb_strlen((string) $value, 'UTF-8') < $min) {
            $this->addError($field, t('error.min_length', ['min' => $min]));
        }
        return $this;
    }

    public function integer($field, $min = null, $max = null)
    {
        $value = $this->value($field);
        if (!$this->isFilled($value)) {
            return $this;
        }
        if (!is_int($value) && !(is_string($value) && preg_match('/^-?\d+$/', $value))) {
            $this->addError($field, t('error.integer'));
            return $this;
        }
        $number = (int) $value;
        if (($min !== null && $number < $min) || ($max !== null && $number > $max)) {
            $this->addError($field, t('error.range', ['min' => $min, 'max' => $max]));
        }
        return $this;
    }

    public function inList($field, $allowed)
    {
        $value = $this->value($field);
        if ($this->isFilled($value) && !in_array($value, $allowed, true)) {
            $this->addError($field, t('error.invalid'));
        }
        return $this;
    }

    // Tarikh YYYY-MM-DD
    public function date($field)
    {
        $value = $this->value($field);
        if (!$this->isFilled($value)) {
            return $this;
        }
        $date = DateTime::createFromFormat('Y-m-d', (string) $value);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            $this->addError($field, t('error.date'));
        }
        return $this;
    }

    public function pattern($field, $regex, $messageKey = 'error.invalid')
    {
        $value = $this->value($field);
        if ($this->isFilled($value) && !preg_match($regex, (string) $value)) {
            $this->addError($field, t($messageKey));
        }
        return $this;
    }

    // Ralat tersuai dari job (cth. pendua)
    public function addError($field, $message)
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function fails()
    {
        return count($this->errors) > 0;
    }

    public function errors()
    {
        return $this->errors;
    }

    // Medan bersarang guna titik: 'names.ms'
    private function value($field)
    {
        $current = $this->input;
        foreach (explode('.', $field) as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return null;
            }
            $current = $current[$part];
        }
        if (is_string($current)) {
            return trim($current);
        }
        return $current;
    }

    private function isFilled($value)
    {
        return $value !== null && $value !== '';
    }
}
