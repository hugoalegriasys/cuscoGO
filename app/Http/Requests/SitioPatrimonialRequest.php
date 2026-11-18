<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\SitioPatrimonial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida los datos de registro y actualización de un sitio patrimonial.
 *
 * Nota de seguridad: el cliente solo envía nombre, dirección, descripción,
 * horario de atención y estado; no se aceptan campos sensibles adicionales.
 */
class SitioPatrimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sitio = $this->route('sitio');

        $reglaNombre = $sitio instanceof SitioPatrimonial
            ? ['required', 'string', 'min:3', 'max:150', Rule::unique('sitios_patrimoniales', 'nombre')->ignore($sitio->getKey())]
            : ['required', 'string', 'min:3', 'max:150', Rule::unique('sitios_patrimoniales', 'nombre')];

        return [
            'nombre' => $reglaNombre,
            'direccion' => ['required', 'string', 'min:5', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'hora_apertura' => ['required', 'date_format:H:i'],
            'hora_cierre' => ['required', 'date_format:H:i', 'after:hora_apertura'],
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del sitio patrimonial es obligatorio.',
            'nombre.string' => 'El nombre del sitio patrimonial debe ser un texto.',
            'nombre.min' => 'El nombre del sitio patrimonial debe tener al menos :min caracteres.',
            'nombre.max' => 'El nombre del sitio patrimonial no puede superar los :max caracteres.',
            'nombre.unique' => 'Ya existe un sitio patrimonial registrado con ese nombre.',
            'direccion.required' => 'La dirección del sitio patrimonial es obligatoria.',
            'direccion.string' => 'La dirección del sitio patrimonial debe ser un texto.',
            'direccion.min' => 'La dirección debe tener al menos :min caracteres.',
            'direccion.max' => 'La dirección no puede superar los :max caracteres.',
            'descripcion.string' => 'La descripción del sitio patrimonial debe ser un texto.',
            'descripcion.max' => 'La descripción no puede superar los :max caracteres.',
            'hora_apertura.required' => 'La hora de apertura es obligatoria.',
            'hora_apertura.date_format' => 'La hora de apertura debe tener el formato HH:MM (ejemplo: 09:00).',
            'hora_cierre.required' => 'La hora de cierre es obligatoria.',
            'hora_cierre.date_format' => 'La hora de cierre debe tener el formato HH:MM (ejemplo: 17:00).',
            'hora_cierre.after' => 'La hora de cierre debe ser posterior a la hora de apertura.',
            'activo.boolean' => 'El estado del sitio patrimonial debe ser verdadero o falso.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre del sitio',
            'direccion' => 'dirección',
            'descripcion' => 'descripción',
            'hora_apertura' => 'hora de apertura',
            'hora_cierre' => 'hora de cierre',
            'activo' => 'estado del sitio',
        ];
    }

    /**
     * Devuelve únicamente los campos permitidos para persistir.
     */
    public function datosSitio(): array
    {
        $datos = $this->validated();

        $datos['activo'] = array_key_exists('activo', $datos)
            ? (bool) $datos['activo']
            : true;

        return $datos;
    }
}
