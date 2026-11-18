<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CalcularCostoTransporteRequest extends FormRequest
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
            'tipo_servicio' => ['required', 'string', 'in:BUS_COLECTIVO,TREN,PRIVADO'],
            'pasajeros' => ['required', 'integer', 'min:1'],
            'precio_unitario' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo_servicio.in' => 'Tipo de transporte no reconocido',
            'pasajeros.min' => 'La cantidad de pasajeros debe ser mayor a cero',
            'precio_unitario.min' => 'El precio unitario debe ser un monto válido mayor o igual a cero',
        ];
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
