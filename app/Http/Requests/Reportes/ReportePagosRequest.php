<?php

namespace App\Http\Requests\Reportes;

use Illuminate\Foundation\Http\FormRequest;
use App\Application\Reportes\Pagos\PagosReportFilters;

class ReportePagosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q'       => ['nullable', 'string', 'max:120'],
            'from'    => ['nullable', 'date'],
            'to'      => ['nullable', 'date', 'after_or_equal:from'],
            'gestor'  => ['nullable', 'string', 'max:120'],
            'cartera' => ['nullable', 'string', 'max:120'],
            'partial' => ['nullable', 'boolean'],
        ];
    }

    public function filters(): PagosReportFilters
    {
        return new PagosReportFilters(
            q: $this->input('q'),
            from: $this->input('from'),
            to: $this->input('to'),
            gestor: $this->input('gestor'),
            cartera: $this->input('cartera'),
        );
    }

    public function partial(): bool
    {
        return $this->boolean('partial');
    }
}
