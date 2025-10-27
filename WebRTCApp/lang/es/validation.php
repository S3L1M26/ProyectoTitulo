<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Líneas de idioma para validación
    |--------------------------------------------------------------------------
    |
    | Las siguientes líneas de idioma contienen los mensajes de error por defecto
    | utilizados por la clase validadora.
    |
    */

    'accepted' => 'El campo :attribute debe ser aceptado.',
    'active_url' => 'El campo :attribute no es una URL válida.',
    'after' => 'El campo :attribute debe ser una fecha posterior a :date.',
    'after_or_equal' => 'El campo :attribute debe ser una fecha posterior o igual a :date.',
    'alpha' => 'El campo :attribute solo puede contener letras.',
    'alpha_dash' => 'El campo :attribute solo puede contener letras, números, guiones y guiones bajos.',
    'alpha_num' => 'El campo :attribute solo puede contener letras y números.',
    'array' => 'El campo :attribute debe ser un array.',
    'before' => 'El campo :attribute debe ser una fecha anterior a :date.',
    'before_or_equal' => 'El campo :attribute debe ser una fecha anterior o igual a :date.',
    'between' => [
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
        'file' => 'El archivo :attribute debe pesar entre :min y :max kilobytes.',
        'string' => 'El campo :attribute debe tener entre :min y :max caracteres.',
        'array' => 'El campo :attribute debe tener entre :min y :max elementos.',
    ],
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'date' => 'El campo :attribute no es una fecha válida.',
    'date_equals' => 'El campo :attribute debe ser una fecha igual a :date.',
    'date_format' => 'El campo :attribute no coincide con el formato :format.',
    'different' => 'Los campos :attribute y :other deben ser diferentes.',
    'digits' => 'El campo :attribute debe tener :digits dígitos.',
    'digits_between' => 'El campo :attribute debe tener entre :min y :max dígitos.',
    'dimensions' => 'Las dimensiones de la imagen :attribute no son válidas.',
    'distinct' => 'El campo :attribute contiene un valor duplicado.',
    'email' => 'El campo :attribute debe ser una dirección de correo válida.',
    'ends_with' => 'El campo :attribute debe terminar con uno de los siguientes valores: :values.',
    'exists' => 'El campo :attribute seleccionado no es válido.',
    'file' => 'El campo :attribute debe ser un archivo.',
    'filled' => 'El campo :attribute es obligatorio.',
    'gt' => [
        'numeric' => 'El campo :attribute debe ser mayor que :value.',
        'file' => 'El archivo :attribute debe ser mayor que :value kilobytes.',
        'string' => 'El campo :attribute debe ser mayor que :value caracteres.',
        'array' => 'El campo :attribute debe tener más de :value elementos.',
    ],
    'gte' => [
        'numeric' => 'El campo :attribute debe ser como mínimo :value.',
        'file' => 'El archivo :attribute debe ser como mínimo :value kilobytes.',
        'string' => 'El campo :attribute debe ser como mínimo :value caracteres.',
        'array' => 'El campo :attribute debe tener como mínimo :value elementos.',
    ],
    'image' => 'El campo :attribute debe ser una imagen.',
    'in' => 'El campo :attribute seleccionado no es válido.',
    'in_array' => 'El campo :attribute no existe en :other.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'ip' => 'El campo :attribute debe ser una dirección IP válida.',
    'ipv4' => 'El campo :attribute debe ser una dirección IPv4 válida.',
    'ipv6' => 'El campo :attribute debe ser una dirección IPv6 válida.',
    'json' => 'El campo :attribute debe ser una cadena JSON válida.',
    'lt' => [
        'numeric' => 'El campo :attribute debe ser menor que :value.',
        'file' => 'El archivo :attribute debe ser menor que :value kilobytes.',
        'string' => 'El campo :attribute debe ser menor que :value caracteres.',
        'array' => 'El campo :attribute debe tener menos de :value elementos.',
    ],
    'lte' => [
        'numeric' => 'El campo :attribute debe ser como máximo :value.',
        'file' => 'El archivo :attribute debe ser como máximo :value kilobytes.',
        'string' => 'El campo :attribute debe ser como máximo :value caracteres.',
        'array' => 'El campo :attribute debe tener como máximo :value elementos.',
    ],
    'max' => [
        'numeric' => 'El campo :attribute no debe ser mayor a :max.',
        'file' => 'El archivo :attribute no debe ser mayor que :max kilobytes.',
        'string' => 'El campo :attribute no debe ser mayor que :max caracteres.',
        'array' => 'El campo :attribute no debe tener más de :max elementos.',
    ],
    'mimes' => 'El campo :attribute debe ser un archivo con formato: :values.',
    'mimetypes' => 'El campo :attribute debe ser un archivo con formato: :values.',
    'min' => [
        'numeric' => 'El campo :attribute debe ser como mínimo :min.',
        'file' => 'El archivo :attribute debe ser como mínimo :min kilobytes.',
        'string' => 'El campo :attribute debe ser como mínimo :min caracteres.',
        'array' => 'El campo :attribute debe tener como mínimo :min elementos.',
    ],
    'not_in' => 'El campo :attribute seleccionado no es válido.',
    'not_regex' => 'El formato del campo :attribute no es válido.',
    'numeric' => 'El campo :attribute debe ser numérico.',
    'password' => 'La contraseña es incorrecta.',
    'present' => 'El campo :attribute debe estar presente.',
    'regex' => 'El formato de :attribute es inválido.',
    'required' => 'El campo :attribute es obligatorio.',
    'required_if' => 'El campo :attribute es obligatorio cuando :other es :value.',
    'required_unless' => 'El campo :attribute es obligatorio a menos que :other esté en :values.',
    'required_with' => 'El campo :attribute es obligatorio cuando :values está presente.',
    'required_with_all' => 'El campo :attribute es obligatorio cuando :values están presentes.',
    'required_without' => 'El campo :attribute es obligatorio cuando :values no está presente.',
    'required_without_all' => 'El campo :attribute es obligatorio cuando ninguno de :values estén presentes.',
    'same' => 'Los campos :attribute y :other deben coincidir.',
    'size' => [
        'numeric' => 'El campo :attribute debe ser :size.',
        'file' => 'El archivo :attribute debe pesar :size kilobytes.',
        'string' => 'El campo :attribute debe tener :size caracteres.',
        'array' => 'El campo :attribute debe contener :size elementos.',
    ],
    'starts_with' => 'El campo :attribute debe comenzar con uno de los siguientes valores: :values.',
    'string' => 'El campo :attribute debe ser una cadena de caracteres.',
    'timezone' => 'El campo :attribute debe ser una zona horaria válida.',
    'unique' => 'El campo :attribute ya ha sido registrado.',
    'uploaded' => 'Subir :attribute ha fallado.',
    'url' => 'El formato :attribute es inválido.',
    'uuid' => 'El campo :attribute debe ser un UUID válido.',

    /*
    |--------------------------------------------------------------------------
    | Atributos personalizados de validación
    |--------------------------------------------------------------------------
    |
    | Las siguientes líneas de idioma se utilizan para intercambiar los marcadores
    | de posición de atributo con algo más fácil de leer.
    |
    */

    'attributes' => [
        'name' => 'nombre',
        'username' => 'nombre de usuario',
        'email' => 'correo electrónico',
        'first_name' => 'nombre',
        'last_name' => 'apellido',
        'password' => 'contraseña',
        'password_confirmation' => 'confirmación de la contraseña',
        'city' => 'ciudad',
        'country' => 'país',
        'address' => 'dirección',
        'phone' => 'teléfono',
        'mobile' => 'móvil',
        'age' => 'edad',
        'sex' => 'sexo',
        'gender' => 'género',
        'day' => 'día',
        'month' => 'mes',
        'year' => 'año',
        'hour' => 'hora',
        'minute' => 'minuto',
        'second' => 'segundo',
        'title' => 'título',
        'content' => 'contenido',
        'description' => 'descripción',
        'excerpt' => 'extracto',
        'date' => 'fecha',
        'time' => 'hora',
        'available' => 'disponible',
        'size' => 'tamaño',
    ],
];