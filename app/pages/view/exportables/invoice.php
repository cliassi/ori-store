<?php
require_once("../../../../env.php");
require_once("../../../../core/config.php");
require_once("../../../../core/f.inc.php");
require "../../../../../framework/core/vendor/fpdf/fpdf.php";
date_default_timezone_set('Asia/Kuala_Lumpur');
$user = R::load("sys_user", 1);
$id = $_GET['id'];
$parts = explode(",", $id);
vd($parts);
$ref = "".date("y", time()).zerofill(substr($id,5,4),5);
$users = userList();
$total = 0;
$font1 = 'Anton';
$font2 = 'Arial';
define('COMPANY', '');
define('REG_NO', '');
define('ADDRESS_1', '');
define('ADDRESS_2', '');
class PDF extends FPDF
{
    // Load data
    function Header()
    {
        global $base;
        global $font1;
        global $font2;
        global $ref;
        global $object;
        global $company;
        global $customer;
        $this->AddFont($font1);
        $pageWidth = 210;
        $pageHeight = 148;
        $margin = 5;
        global $object;
        //$header = "../../images/header.jpg";
        $title = "INVOICE";
        //$this->Image($header,15,6, 180);
        $this->Image("../../../../assets/header.jpg", 0, 0, 210);
        //$this->Image("$base".APP."/assets/qrcode_{$object->company}.png", 10, 95, 20);

        // Arial bold 15
        // $this->SetFont($font1, '', 20);
        // $this->Cell(0, 0, $company->name, 0, 0, 'C');
        // $this->SetFont($font2, '', 6);
        // $this->Ln(4);
        // $this->Cell(60, 0, ""); $this->Cell(0, 0, REG_NO, 0, 0, 'C');
        // $this->SetFont($font2, '', 7);
        // $this->Ln(3);
        // $this->Cell(0, 0, $company->address1, 0, 0, 'C');
        // $this->Ln(3);
        // $this->Cell(0, 0, $company->address2, 0, 0, 'C');

        global $id;
        $ids = $id;
        $ids = explode(",", $ids);

        $invoice = R::load("invoice", $ids[0]);

        $this->Ln(35);
        $this->SetFont($font2, '', 14);
        // $this->Cell(80, 6, $title, 0, 0);
        $this->Cell(48, 6, "Receipt No: $ref", 'B');
        $this->Cell(100, 6, '');
        $this->Cell(43, 6, 'Date : '.df($invoice->invoice_date), 'B', 0, 'R');

        // $this->Ln(5);
        // $this->Cell(0, 0, $customer->name);
        // $this->Cell(0, 0, "Date: ".df2($object->date), 0, 0, 'R');
        
    }

    // Page footer
    function Footer()
    {
        $this->SetFont('','B', 20);
        $this->Ln(15);
        $this->Cell(0, 5, 'THANK YOU!');
        $this->SetFont('','B', 10);
        $this->Ln(7);        $this->Cell(0, 5, 'Call us @ +60 1122-440000');
        $this->Ln(5);        $this->Cell(0, 5, 'Email us @ apurewater02@gmail.com');

    }
    function LoadData($file)
    {
        $lines = file($file);
        $data = array();
        foreach($lines as $line){
            $data[] = explode(';',trim($line));
        }
        return $data;
    }
    function FancyTable()
    {
        
        global $id;
        $ids = $id;
        $id = explode(",", $id);
        global $ref;
        $lineHeight = 89;
        $fillAlpha = .3;

        $invoice = R::load("invoice", $id[0]);

        $customer = R::load("customer", $invoice->customer_id);

        $company_name = $customer->company;
        $address = $customer->address . $customer->city;
        // $address = explode(PHP_EOL, $hotel->address);


        // $statement = R::LOAD("hotel_statement", $invoice->statement);
        // $hourly = $statement->hourly == 1;
        // $workers = R::find("hotel_statement_worker", "statement=?", [$invoice->statement]);

        // $this->Cell(0, 0, '', 'B');
        $this->Ln(1);
        $this->SetFont('','B', 9);
        $this->Cell(20, 6, 'Shop Name'); $this->Cell(2, 6, ':'); $this->Cell(50, 6, $company_name); $this->Cell(50, 6, ''); $this->Cell(30, 6, 'Payment Method'); $this->Cell(2, 6, ':'); $this->Cell(50, 6, 'Bank Transfer');
        $this->Ln(5);
        $this->Cell(20, 6, 'Area'); $this->Cell(2, 6, ':'); $this->Cell(50, 6, $customer->city); $this->Cell(50, 6, ''); $this->Cell(30, 6, 'Bank'); $this->Cell(2, 6, ':'); $this->Cell(50, 6, 'MayBank');
        $this->Ln(5);
        $this->Cell(20, 6, 'Name'); $this->Cell(2, 6, ':'); $this->Cell(50, 6, $customer->contact); $this->Cell(50, 6, ''); $this->Cell(30, 6, 'Account Nmae'); $this->Cell(2, 6, ':'); $this->Cell(50, 6, 'A PURE WATER SDN BHD');
        $this->Ln(5);
        $this->Cell(20, 6, 'Mobile No.'); $this->Cell(2, 6, ':'); $this->Cell(50, 6, "+6$customer->mobile"); $this->Cell(50, 6, ''); $this->Cell(30, 6, 'Account Number'); $this->Cell(2, 6, ':'); $this->Cell(50, 6, '562311512009');

        
        $this->Ln(10);
        // $this->Cell(180, 6, 'Attention: '.$hotel->attn_to, '', '', 'C');
        $this->Ln(7);
        // $this->SetFont('','B', 9);
        // $this->Cell(180, 6, 'TAX INVOICE FOR '.date("d F, Y", strtotime($invoice->start_date)).' Until '.date("d F, Y", strtotime($invoice->end_date)), '', '', 'C');
        // $this->Ln(10);



        $cw = [15, 87, 40, 26, 22]; //190
        $headers = ['No.', 'Item Description', 'Price', 'Qty', 'TOTAL Rm'];

    //     $this->SetFont('','B', 9); $this->SetFillColor(235);

    //     //$this->Ln(.3);
        $left_margin = 10;
        foreach ($headers as $key => $value) {
            $this->cell($cw[$key], 5, $value, 1, 0, 'C');
            $left_margin += $cw[$key];
        }

    //     $items = R::find("invoice_item", "invoice=?", [$object->id]);
    //     $workers = R::find("worker", "id in(SELECT worker from invoice_worker where invoice=?)", [$object->id]);

    //     //var_dump($workers);
    //     $total = 0;

        $row_count = 10;// count($workers);
    //     $this->SetFont('','', 9); 
        $i = 0;

        $total_days = $total_salary = $total_hours = 0;
        $this->SetFont('','', 9);


        $invoices = R::find('invoice', 'id IN ('.$ids.')');
        $i = 1;
        $total = 0;
        foreach ($invoices as $key => $invoice) {
            // $item = R::findOne('invoice_item', 'invoice_id=?', [$invoice->id]);
            // $this->Ln(5);
            // $cell = 0;
            // $extra = "";
            // $this->Cell($cw[$cell++], 5, zerofill($i++,2), 1, 0, 'C');
            // $this->Cell($cw[$cell++], 5, $item->description, 1, 0);
            // $this->Cell($cw[$cell++], 5, nf($item->price), 1, 0, 'C');
            // $this->Cell($cw[$cell++], 5, $item->quantity, 1, 0, 'C');
            // $this->Cell($cw[$cell++], 5, nf($item->price * $item->quantity), 1, 0, 'R');
            // $total += $item->price * $item->quantity;
            
            $items = R::find('invoice_item', 'invoice_id=?', [$invoice->id]);

            foreach($items as $item){
                $this->Ln(5);
                $cell = 0;
                $extra = "";
                $this->Cell($cw[$cell++], 5, zerofill($i++,2), 1, 0, 'C');
                $this->Cell($cw[$cell++], 5, $item->description, 1, 0);
                $this->Cell($cw[$cell++], 5, nf($item->price), 1, 0, 'C');
                $this->Cell($cw[$cell++], 5, $item->quantity, 1, 0, 'C');
                $this->Cell($cw[$cell++], 5, nf($item->price * $item->quantity), 1, 0, 'R');
                $total += $item->price * $item->quantity;
            }
        }

        

        $this->SetFont('','B', 9);
        $this->Ln(5);
        $cell = 0;
        $this->Cell($cw[$cell++], 6, '', 1, 0);
        $this->Cell($cw[$cell++], 6,  '' , 1, 0, 'R');
        $this->Cell($cw[$cell++], 6, "", 1, 0, 'C');
        $this->Cell($cw[$cell++], 6, 'TOTAL RM', 1, 0, 'R');
        $this->Cell($cw[$cell++], 6, nf($total), 1, 0, 'R');

        

        /*if($hotel->commisson > 0){
            $this->Ln(6);
            $cell = 0;
            $this->Cell($cw[$cell++], 6, '', 1, 0);
            $this->Cell($cw[$cell++], 6, '', 1, 0);
            $this->Cell($cw[$cell++], 6, '', 1, 0);
            $this->Cell($cw[$cell++], 6, 'SST @ 8%', 1, 0, 'R');
            $this->Cell($cw[$cell++], 6, nf($total_salary * .08), 1, 0, 'R');

            $this->Ln(6);
            $cell = 0;
            $this->Cell($cw[$cell++], 6, '', 1, 0);
            $this->Cell($cw[$cell++], 6, '', 1, 0);
            $this->Cell($cw[$cell++], 6, '', 1, 0);
            $this->Cell($cw[$cell++], 6, 'NET TOTAL', 1, 0, 'R');
            $this->Cell($cw[$cell++], 6, nf($total_salary * 1.08), 1, 0, 'R');
        } 
        *//*
        if($hourly && $hotel->sst){
            $this->Ln(6);
            $cell = 0;
            $this->Cell($cw[$cell++], 6, '', 1, 0);
            $this->Cell($cw[$cell++], 6, '', 1, 0);
            $this->Cell($cw[$cell++], 6, '', 1, 0);
            $this->Cell($cw[$cell++], 6, 'SST @ 8%', 1, 0, 'R');
            $this->Cell($cw[$cell++], 6, nf($total_salary * .08), 1, 0, 'R');
            $total_salary += $total_salary * .08;
        }

        $this->Ln(6);
        $cell = 0;
        $this->Cell($cw[$cell++], 6, '', 1, 0);
        $this->Cell($cw[$cell++], 6, '', 1, 0);
        $this->Cell($cw[$cell++], 6, '', 1, 0);
        $this->Cell($cw[$cell++], 6, 'NET TOTAL', 1, 0, 'R');
        $this->Cell($cw[$cell++], 6, nf($total_salary), 1, 0, 'R');



        $this->Ln(15);
        $this->SetFont('','B', 10);
        $this->Cell(110, 6, 'Payment Term 7 days upon receiving invoice');
        $this->Cell('', 6, 'Please issue cheque payable to:');
        $this->Ln(6);
        $this->Cell(120, ''); $this->Cell(0, 6, 'NEAT & CLEAN SERVICE SDN BHD');
        $this->Ln(5);
        $this->Cell(120, ''); $this->Cell(0, 6, 'Bank: RHB ISLAMIC BANK BERHAD');
        $this->Ln(5);
        $this->Cell(120, ''); $this->Cell(0, 6, 'Account No: 21439460011795');
        $this->Ln(5);
        $this->Cell(100, ''); $this->Cell(0, 6, 'OR', '', '', 'C');
        $this->Ln(5);
        $this->Cell(120, ''); $this->Cell(0, 6, 'Bank: MAYBANK BERHAD');
        $this->Ln(5);
        $this->Cell(120, ''); $this->Cell(0, 6, 'Account No: 562311326877');


        $this->Image("../../assets/nnc_sign.png", 10, $this->GetY() - 20, 70);

    //     $this->Ln(7);
    //     $lineHeight += 7;
    //     $left_margin = 10;
    //     $this->SetFont('','B', 9); 
    //     foreach ($headers as $key => $value) {
    //         $this->SetAlpha($fillAlpha); $this->Rect($left_margin, $lineHeight, $cw[$key] - 1, 6, 'F');
    //         $this->SetAlpha(1);
    //         if($key == 5){
    //             $this->cell($cw[$key], 0, "TOTAL");    
    //         } elseif($key == 6){
    //             $this->cell($cw[$key], 0, nf($total), 0, 0, 'R');    
    //         } else{
    //             $this->cell($cw[$key], 0, "");    
    //         }
    //         $left_margin += $cw[$key];
    //     }
*/
    }
}


class AlphaPDF extends PDF
{
    var $extgstates = array();

    // alpha: real value from 0 (transparent) to 1 (opaque)
    // bm:    blend mode, one of the following:
    //          Normal, Multiply, Screen, Overlay, Darken, Lighten, ColorDodge, ColorBurn,
    //          HardLight, SoftLight, Difference, Exclusion, Hue, Saturation, Color, Luminosity
    function SetAlpha($alpha, $bm='Normal')
    {
        // set alpha for stroking (CA) and non-stroking (ca) operations
        $gs = $this->AddExtGState(array('ca'=>$alpha, 'CA'=>$alpha, 'BM'=>'/'.$bm));
        $this->SetExtGState($gs);
    }

    function AddExtGState($parms)
    {
        $n = count($this->extgstates)+1;
        $this->extgstates[$n]['parms'] = $parms;
        return $n;
    }

    function SetExtGState($gs)
    {
        $this->_out(sprintf('/GS%d gs', $gs));
    }

    function _enddoc()
    {
        if(!empty($this->extgstates) && $this->PDFVersion<'1.4')
            $this->PDFVersion='1.4';
        parent::_enddoc();
    }

    function _putextgstates()
    {
        for ($i = 1; $i <= count($this->extgstates); $i++)
        {
            $this->_newobj();
            $this->extgstates[$i]['n'] = $this->n;
            $this->_out('<</Type /ExtGState');
            $parms = $this->extgstates[$i]['parms'];
            $this->_out(sprintf('/ca %.3F', $parms['ca']));
            $this->_out(sprintf('/CA %.3F', $parms['CA']));
            $this->_out('/BM '.$parms['BM']);
            $this->_out('>>');
            $this->_out('endobj');
        }
    }

    function _putresourcedict()
    {
        parent::_putresourcedict();
        $this->_out('/ExtGState <<');
        foreach($this->extgstates as $k=>$extgstate)
            $this->_out('/GS'.$k.' '.$extgstate['n'].' 0 R');
        $this->_out('>>');
    }

    function _putresources()
    {
        $this->_putextgstates();
        parent::_putresources();
    }
}

$pdf = new AlphaPDF();
$pdf->SetFont($font2,'',9);
$pdf->AddPage('P', 'A4');
$pdf->Ln(2);

$pdf->Ln(5);
$pdf->SetFont($font2,'',8);
$pdf->FancyTable();

$pdf->Ln(15);
$pdf->SetFont($font2,'',8);
$pdf->Cell(140, 0, "");
// $pdf->Cell(0, 6, "Issued by: ".username($object->entry_by), 'T', 0, 'R');

// $pdf->Output('I', COMPANY."$ref.pdf");
$pdf->Output("docs/$ref.pdf",'F');
convertPdfToImages("docs/$ref.pdf", $ref);
unlink("docs/$ref.pdf");
header("location:http://store.apurewater.com/store/app/pages/view/exportables/docs/$ref.jpg");


function convertPdfToImages($pdfFilename, $outputName){
    $pdfPath = __DIR__ . '/' . $pdfFilename;
    $outputBase = __DIR__ . '/' . $outputName;
    $pdftoppm = 'C:\\poppler\\Library\\bin\\pdftoppm.exe'; // Use your actual path

    if (!file_exists($pdfPath)) {
        throw new Exception("PDF file not found: $pdfPath");
    }

    $cmd = "\"$pdftoppm\" -jpeg \"$pdfPath\" \"$outputBase\" 2>&1";
    $output = shell_exec($cmd);

    // Output files will be: outputName-1.jpg, outputName-2.jpg, etc.
    $generatedFiles = glob($outputBase . '-*.jpg');

    // Optionally rename them to remove the -1/-2 suffix if only one page
    if (count($generatedFiles) === 1) {
        $finalName = __DIR__ . '/docs/' . $outputName . '.jpg';
        rename($generatedFiles[0], $finalName);
        return [$finalName];
    }

    return $generatedFiles;
}

?>
