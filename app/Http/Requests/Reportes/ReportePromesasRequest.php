<?php

namespace App\Http\Requests\Reportes;

use Illuminate\Foundation\Http\FormRequest;
use App\Application\Reportes\Promesas\PromesasReportFilters;

class ReportePromesasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from'    => ['nullable', 'date'],
            'to'      => ['nullable', 'date', 'after_or_equal:from'],
            'estado'  => ['nullable', 'string', 'max:80'],
            'gestor'  => ['nullable', 'string', 'max:120'],
            'q'       => ['nullable', 'string', 'max:160'],
            'partial' => ['nullable', 'boolean'],
        ];
    }

    public function filters(): PromesasReportFilters
    {
        return new PromesasReportFilters(
            from: $this->query('from', now()->startOfMonth()->toDateString()),
            to: $this->query('to', now()->toDateString()),
            estado: trim((string) $this->query('estado', '')),
            gestor: trim((string) $this->query('gestor', '')),
            q: trim((string) $this->query('q', '')),
        );
    }

    public function shouldRenderPartial(): bool
    {
        return $this->ajax() || $this->boolean('partial');
    }
}
