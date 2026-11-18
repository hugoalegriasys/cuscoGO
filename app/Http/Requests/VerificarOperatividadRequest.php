<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

/**
 * Valida la hora de consulta opcional para la evaluación de
 * estado de operatividad de un sitio patrimonial (CUS-03).
 */
class VerificarOperatividadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hora_consulta' => ['nullable', 'string', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'hora_consulta.string' => 'La hora de consulta debe ser un texto.',
            'hora_consulta.date_format' => 'La hora de consulta debe tener el formato HH:MM (ejemplo: 10:30) y estar dentro del rango 00:00 a 23:59.',
        ];
    }

    public function attributes(): array
    {
        return [
            'hora_consulta' => 'hora de consulta',
        ];
    }

    /**
     * Responde con el formato JSON estandarizado del sistema cuando la
     * validación falla en peticiones AJAX, en lugar del formato por defecto.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json(
                [
                    'exitoso' => false,
                    'mensaje' => 'Los datos de consulta enviados no son válidos.',
                    'errores' => $validator->errors()->all(),
                ],
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY
            )
        );
    }

    /**
     * Devuelve la hora de consulta normalizada o null cuando el
     * turista desea consultar con la hora actual del sistema.
     */
    public function horaConsulta(): ?string
    {
        $hora = $this->input('hora_consulta');

        if (! is_string($hora) || trim($hora) === '') {
            return null;
        }

        return trim($hora);
    }
}
