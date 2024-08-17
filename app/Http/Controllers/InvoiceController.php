<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use DateTime;

class InvoiceController extends Controller
{
    private $pdf;  

    public function __construct(\App\Models\Pdf $pdf)
    {
        $this->pdf = $pdf;
    }
    
    public function show(Request $request)
    {
        $customerId = $request->input('customer_id');
        $invoiceDate = $request->input('invoice_date');

        $invoiceTotal = 0;
        $lightSailTotal = 0;
        $registrarTotal = 0;
        $billingPeriodStart = null;
        $billingPeriodEnd = null;
        $invoiceId = null;
        $invoiceTotal = null;
        $lightSailTotal = null;
        $registrarTotal = null;

        $customerRecord = Customer::where('id', $customerId)->first();
        $customerName = $customerRecord ?  $customerRecord->name : null;
        $customerAddress = $customerRecord ?  $customerRecord->address : null;

        $invoiceRecord = Invoice::where([
            ['customer_id', $customerId],
            ['date', $invoiceDate]
        ])
        ->first();

        if($invoiceRecord) {
            $billingPeriodStart = $invoiceRecord->period_start;
            $billingPeriodEnd = $invoiceRecord->period_end;

            $dateBillingPeriodStart = new DateTime($billingPeriodStart);
            $dateBillingPeriodEnd = new DateTime($billingPeriodEnd);

            $billingPeriodStart = $dateBillingPeriodStart->format('F j, Y');

            $billingPeriodEnd = $dateBillingPeriodEnd->format('F j, Y');

            $invoiceId = $invoiceRecord->id;

        }

        $invoiceLineItems = InvoiceLineItem::where('invoice_id', $invoiceId)->get();

        
        foreach($invoiceLineItems as $invoiceLineItem) {
            $invoiceTotal += $invoiceLineItem->unit_price * $invoiceLineItem->quantity;

            $lightSailTotal += $invoiceLineItem->service_id == 1 ? $invoiceLineItem->unit_price * $invoiceLineItem->quantity : 0;
            $registrarTotal += $invoiceLineItem->service_id == 2 ? $invoiceLineItem->unit_price * $invoiceLineItem->quantity : 0;
        }

        $invoiceTotal = number_format($invoiceTotal, 2);
        $lightSailTotal = number_format($lightSailTotal, 2);
        $registrarTotal = number_format($registrarTotal, 2);

        
        $logo = public_path('/imgs/logo.png');
        $this->pdf->AliasNbPages();
        $this->pdf->SetMargins(12, 10);              
        $this->pdf->AddPage('P', 'Letter');

        $border=0;
        $this->pdf->Image($logo, 10, 10, 24);
        
        $this->pdf->SetY(28); 
        $this->pdf->SetFont('Arial', '', '9'); 
        $this->pdf->Cell(80, 4, "Account number:", $border, 0, 'L' );
        $x = $this->pdf->GetX();
        $this->pdf->SetFont('Arial', '', '15');
        $this->pdf->SetXY($x,25); 
        $this->pdf->Cell(0, 6, "Amazon Web Services, Inc. Invoice", $border, 0, 'L' );
        $this->pdf->Ln();

       
        $this->pdf->SetFont('Arial', '', '16');
        $this->pdf->Cell(0, 8, "477737808775", $border, 0, 'L' );
        $this->pdf->Ln();

        $border=0;
        $y=$this->pdf->GetY();
         
        $this->pdf->SetFont('Arial', '', '9');
        $this->pdf->SetY($y-3);
        $this->pdf->Cell(80, 4, "", $border, 0, 'L' );
        $this->pdf->SetLineWidth(1);
        $this->pdf->SetFont('Arial', 'B', '12');
        $this->pdf->SetDrawColor(204,205,153);
        $this->pdf->Cell(0, 8, "Invoice Summary", "T", 0, 'L' );
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->Ln();

        $this->pdf->SetY($y);
        $this->pdf->SetFont('Arial', '', '16');
        $this->pdf->Cell(80, 4, "", $border, 0, 'L' );
        $this->pdf->SetFont('Arial', '', '9');
        $x=$this->pdf->GetX();
        $this->pdf->SetXY($x,$y+4);
        $this->pdf->Cell(35, 4, "Invoice Number:", "T", 0, 'L' );
        $this->pdf->Cell(0, 4, $invoiceId, "T", 0, 'R' );
        $this->pdf->Ln();

        $this->pdf->Cell(80, 4, "", $border, 0, 'L' );
        $this->pdf->SetFont('Arial', '', '9');
        $this->pdf->Cell(35, 4, "Invoice Date:", "B", 0, 'L' );
        $this->pdf->Cell(0, 4, $billingPeriodStart, "B", 0, 'R' );
        $this->pdf->Ln();

        $this->pdf->Cell(80, 8, "", $border, 0, 'L' );
        $this->pdf->SetFont('Arial', 'B', '10');
        $this->pdf->SetLineWidth(1);
        $this->pdf->Cell(35, 8, "TOTAL AMOUNT DUE ON $billingPeriodStart", "B", 0, 'L' );
        $this->pdf->Cell(0, 8, "TT$ $invoiceTotal", "B", 0, 'R' );
        $this->pdf->Ln();

        $y=$this->pdf->GetY();
        $border=0;
        $this->pdf->SetY($y-15);
        $this->pdf->SetFont('Arial', '', '9');
        $this->pdf->SetLineWidth(0.2);
        $this->pdf->Cell(80, 4, "Bill to Address:", $border, 0, 'L' );
        $this->pdf->Ln();

        $this->pdf->Cell(80, 4, $customerName, $border, 0, 'L' );
        $this->pdf->Ln();

        $this->pdf->Cell(80, 4, "ATT: Principal", $border, 0, 'L' );
        $this->pdf->Ln();

        $this->pdf->MultiCell(40, 4, $customerAddress, $border, 'L' );
        $this->pdf->Ln(12);

        $this->pdf->SetFont('Arial', '', '12');
        $this->pdf->Cell(0, 6, "This invoice is for the billing period $billingPeriodStart - $billingPeriodEnd", 0, 0, 'L' );
        $this->pdf->Ln();

        $this->pdf->SetFont('Arial', '', '8');
        $this->pdf->Cell(0, 6, "Greetings from Amazon Web Services, we're writing to provide you with an electronic invoice for your use of AWS services.", 0, 0, 'L' );
        $this->pdf->Ln(10);

        $border=1;
        $this->pdf->SetFont('Arial', '', '12');
        $this->pdf->SetFillColor(202,225,245);
        $this->pdf->Cell(0, 6, "Summary", $border, 0, 'L', true );
        $this->pdf->Ln();

        $this->pdf->SetTextColor(255,159,14);
        $this->pdf->Cell(5, 6, "", "LTB", 0, "L");
        $this->pdf->Cell(150, 6, "AWS Service Charges", "TB", 0, "L");
        $this->pdf->SetTextColor(0);
        $this->pdf->Cell(0, 6, "TT$ $invoiceTotal", "RTB", 0, "R");
        $this->pdf->Ln();

        $this->pdf->Cell(10, 6, "", "LTB", 0, "L");
        $this->pdf->SetFont('Arial', '', '8');
        $this->pdf->Cell(140, 6, "Charges", "TB", 0, "L");
        $this->pdf->Cell(0, 6, "TT$ $invoiceTotal", "RTB", 0, "R");
        $this->pdf->Ln();

        $this->pdf->Cell(10, 6, "", "LTB", 0, "L");
        $this->pdf->SetFont('Arial', '', '8');
        $this->pdf->Cell(140, 6, "Credits", "TB", 0, "L");
        $this->pdf->Cell(0, 6, "TT$ 0.00", "RTB", 0, "R");
        $this->pdf->Ln();

        $this->pdf->Cell(10, 6, "", "LTB", 0, "L");
        $this->pdf->SetFont('Arial', '', '8');
        $this->pdf->Cell(140, 6, "Tax", "TB", 0, "L");
        $this->pdf->Cell(0, 6, "TT$ 0.00", "RTB", 0, "R");
        $this->pdf->Ln();

        $this->pdf->SetFont('Arial', '', '12');
        $this->pdf->SetFillColor(204,205,153);
        $this->pdf->Cell(130, 6, "Total for this invoice", $border, 0, 'L', true );
        $this->pdf->Cell(0, 6, "TT$ $invoiceTotal", $border, 0, 'R', true );
        $this->pdf->Ln(15);

        $this->pdf->SetFont('Arial', '', '12');
        $this->pdf->SetFillColor(202,225,245);
        $this->pdf->Cell(0, 6, "Detail", $border, 0, 'L', true );
        $this->pdf->Ln();

        $this->pdf->SetTextColor(255,159,14);
        $this->pdf->Cell(5, 6, "", "LTB", 0, "L");
        $this->pdf->Cell(150, 6, "Amazon LightSail", "TB", 0, "L");
        $this->pdf->SetTextColor(0);
        $this->pdf->Cell(0, 6, "TT$ $lightSailTotal", "RTB", 0, "R");
        $this->pdf->Ln();

        foreach($invoiceLineItems as $invoiceLineItem) {
            if($invoiceLineItem->service_id == 1)
            {
                $startDate = new DateTime($invoiceLineItem->period_start);
                $startDate = $startDate->format('d/m/Y');
                $endDate = new DateTime($invoiceLineItem->period_end);
                $endDate = $endDate->format('d/m/Y');
                $charges = number_format($invoiceLineItem->quantity * $invoiceLineItem->unit_price);
                $this->pdf->Cell(10, 6, "", "LTB", 0, "L");
                $this->pdf->SetFont('Arial', '', '8');
                $this->pdf->Cell(140, 6, "Charges", "TB", 0, "L");
                $this->pdf->Cell(0, 6, "TT$ $charges", "RTB", 0, "R");
                $this->pdf->Ln();

                $this->pdf->Cell(10, 6, "", "LTB", 0, "L");
                $this->pdf->SetFont('Arial', '', '11');
                $this->pdf->SetTextColor(255,159,14);
                $this->pdf->Cell(140, 6, "Start Date - $startDate; End Date - $endDate", "TB", 0, "L");
                $this->pdf->SetTextColor(0);
                $this->pdf->Cell(0, 6, "TT$ $charges", "RTB", 0, "R");
                $this->pdf->Ln();
            }
        }

        

        $this->pdf->SetTextColor(255,159,14);
        $this->pdf->Cell(5, 6, "", "LTB", 0, "L");
        $this->pdf->Cell(150, 6, "Amazon Registrar", "TB", 0, "L");
        $this->pdf->SetTextColor(0);
        $this->pdf->Cell(0, 6, "TT$ $registrarTotal", "RTB", 0, "R");
        $this->pdf->Ln();

        foreach($invoiceLineItems as $invoiceLineItem) {
            if($invoiceLineItem->service_id == 2)
            {
                $charges = number_format($invoiceLineItem->quantity * $invoiceLineItem->unit_price, 2);
                $this->pdf->Cell(10, 6, "", "LTB", 0, "L");
                $this->pdf->SetFont('Arial', '', '8');
                $this->pdf->Cell(140, 6, "Charges", "TB", 0, "L");
                $this->pdf->Cell(0, 6, "TT$ $charges", "RTB", 0, "R");
                $this->pdf->Ln();

                $this->pdf->Cell(10, 6, "", "LTB", 0, "L");
                $this->pdf->SetFont('Arial', '', '11');
                $this->pdf->SetTextColor(255,159,14);
                $this->pdf->Cell(140, 6, "Start Date - $startDate; End Date - $endDate", "TB", 0, "L");
                $this->pdf->SetTextColor(0);
                $this->pdf->Cell(0, 6, "TT$ $charges", "RTB", 0, "R");
                $this->pdf->Ln();
            }
        }
        




        $this->pdf->Output('I', "$customerName invoice$invoiceId.pdf");
        exit;
    }
}
