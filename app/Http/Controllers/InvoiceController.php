<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    private $pdf;  

    public function __construct(\App\Models\Pdf $pdf)
    {
        $this->pdf = $pdf;
    }
    
    public function show(Request $request)
    {
        $this->pdf->AliasNbPages();
        $this->pdf->SetMargins(10, 10);              
        $this->pdf->AddPage('P', 'Letter');

        $this->pdf->Output('I', 'Invoice.pdf');
        exit;
    }
}
