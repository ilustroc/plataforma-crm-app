<?php

namespace App\Http\Controllers;

use App\Models\CnaSolicitud;
use Illuminate\Support\Facades\View;

class CnaPdfController extends Controller
{
    // Descarga: CNA_[DNI].pdf
    public function download(CnaSolicitud $cna)
    {
        $data = ['cna' => $cna];
        $filename = 'CNA_'.$cna->dni.'.pdf';

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $html = View::make('pdf.cna', $data)->render(); // <- vista correcta
            return \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
                ->setPaper('A4')
                ->download($filename);
        }

        // Fallback si aún no instalas dompdf
        return response()->view('pdf.cna', $data)
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }
}
