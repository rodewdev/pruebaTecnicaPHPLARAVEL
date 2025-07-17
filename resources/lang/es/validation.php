<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser una cadena de texto.',
    'max' => [
        'string' => 'El campo :attribute no debe ser mayor que :max caracteres.',
    ],
    'email' => 'El campo :attribute debe ser una dirección de correo válida.',
    'numeric' => 'El campo :attribute debe ser un número.',
    'min' => [
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'exists' => 'El :attribute seleccionado no existe.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'nullable' => 'El campo :attribute puede ser nulo.',

    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'balance' => 'saldo',
        'password' => 'contraseña',
        'receiver_id' => 'receptor',
        'amount' => 'monto',
        'description' => 'descripción',
    ],
];