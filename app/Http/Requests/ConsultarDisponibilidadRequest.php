<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Contracts\DisponibilidadServiceInterface;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConsultarDisponibilidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'sitio_id' => ['nullable', 'integer', 'exists:sitios,id'],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'hora_actual' => ['required', 'string', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/'],
            'apertura' => ['nullable', 'required_without:sitio_id', 'string', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/'],
            'cierre' => ['nullable', 'required_without:sitio_id', 'string', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/'],
            'estado' => ['prohibited'],
            'abierto' => ['prohibited'],
            'operativo' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sitio_id.integer' => 'El sitio seleccionado no es válido.',
            'sitio_id.exists' => 'El sitio seleccionado no existe en el sistema.',
            'fecha.date_format' => 'La fecha debe tener el formato AAAA-MM-DD.',
            'hora_actual.required' => 'La hora a evaluar es obligatoria.',
            'hora_actual.regex' => 'La hora a evaluar debe tener el formato HH:MM (00:00 a 23:59).',
            'apertura.required_without' => 'La hora de apertura es obligatoria cuando no se selecciona un sitio.',
            'apertura.regex' => 'La hora de apertura debe tener el formato HH:MM (00:00 a 23:59).',
            'cierre.required_without' => 'La hora de cierre es obligatoria cuando no se selecciona un sitio.',
            'cierre.regex' => 'La hora de cierre debe tener el formato HH:MM (00:00 a 23:59).',
            'estado.prohibited' => 'No está permitido enviar el estado desde el cliente.',
            'abierto.prohibited' => 'No está permitido enviar el resultado desde el cliente.',
            'operativo.prohibited' => 'No está permitido enviar el resultado desde el cliente.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sitio_id' => 'sitio patrimonial',
            'fecha' => 'fecha de visita',
            'hora_actual' => 'hora a evaluar',
            'apertura' => 'hora de apertura',
            'cierre' => 'hora de cierre',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $datos = $validator->getData();

            if (! empty($datos['sitio_id'])) {
                return;
            }

            $apertura = $datos['apertura'] ?? null;
            $cierre = $datos['cierre'] ?? null;

            if (! is_string($apertura) || ! is_string($cierre)) {
                return;
            }

            $servicio = app(DisponibilidadServiceInterface::class);

            if (! $servicio->esHorarioValido($apertura, $cierre)) {
                $validator->errors()->add(
                    'cierre',
                    'La hora de cierre debe ser posterior a la hora de apertura.',
                );
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson() || $this->is('api/*')) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Los datos proporcionados no son válidos.',
                    'errors' => $validator->errors(),
                ], 422),
            );
        }

        parent::failedValidation($validator);
    }
}
