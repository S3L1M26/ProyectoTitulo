<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

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
            // Cambiado a 'yesterday' para soportar timezones diferentes
            // La validación real de fecha/hora pasada se hace en el controlador con timezone
            'fecha' => ['required', 'date', 'after_or_equal:yesterday'],
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

    /**
     * Logging temporal para debugging
     */
    protected function passedValidation()
    {
        Log::info('✅ VALIDACIÓN FORMREQUEST PASADA', [
            'fecha' => $this->fecha,
            'hora' => $this->hora,
            'duracion_minutos' => $this->duracion_minutos,
            'topic' => $this->topic,
            'timezone' => $this->timezone,
            'today' => today()->toDateString(),
        ]);
    }

    /**
     * Logging cuando falla la validación
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        Log::warning('❌ VALIDACIÓN FORMREQUEST FALLIDA', [
            'errors' => $validator->errors()->toArray(),
            'input' => $this->all(),
        ]);
        
        parent::failedValidation($validator);
    }
}
