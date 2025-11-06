<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmarMentoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled via policy in controller
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'hora' => ['required', 'date_format:H:i'],
            'duracion_minutos' => ['required', 'integer', 'min:30', 'max:180'],
            'topic' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.after_or_equal' => 'La fecha no puede ser en el pasado.',
            'hora.date_format' => 'La hora debe tener el formato HH:MM.',
            'duracion_minutos.min' => 'La duración mínima es de 30 minutos.',
            'duracion_minutos.max' => 'La duración máxima es de 180 minutos.',
        ];
    }
}
