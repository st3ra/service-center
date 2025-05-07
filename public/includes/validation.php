<?php

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? '' : 'Неверный формат email';
}

function validate_phone($phone) {
    return preg_match('/^\+?[0-9]{10,15}$/', $phone) ? '' : 'Неверный формат телефона';
}

function validate_password($password) {
    return strlen($password) >= 8 ? '' : 'Пароль должен быть не менее 8 символов';
}

function validate_password_confirm($password, $password_confirm) {
    return $password === $password_confirm ? '' : 'Пароли не совпадают';
}

function validate_form($fields) {
    $errors = [];
    foreach ($fields as $field => $value) {
        if (empty($value)) {
            $errors[$field] = 'Поле обязательно для заполнения';
        }
    }
    return $errors;
}