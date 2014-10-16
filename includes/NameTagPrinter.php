<?php
require 'fpdf/fpdf.php';

class NameTagPrinter {
	public $savedirectory;

	private function CreatePDFRendering($name, $company, $file) {
		$pdf=new FPDF('L','pt',array(162,288)); //2.25"x4"

		$pdf->AddPage();
		$pdf->SetFont('Arial','B',22); //name font style
		$pdf->Cell(0,22,$name,0,1,'C'); //name
		$pdf->Cell(0,10,'',0,1,'C'); //blank line
		$pdf->SetFont('Arial','I',16); //company font style
		$pdf->Cell(0,16,$company,0,1,'C'); //company name
		$pdf->Cell(0,28,'',0,1,'C'); //blank line
		$pdf->Image('images/logo.png', $pdf->GetX() + 165, $pdf->GetY(), 65);

		if  ( $file == true ) {	
			
			//set file name to hash of object
			$fullpath = $this->savedirectory . md5(spl_object_hash($pdf)) . '.pdf';
			
			$pdf->Output($fullpath, 'F');
			$pdf->Close();
			
			$cmd = 'lpr ' . $fullpath;
			$cmdout = shell_exec($cmd);
			
			return $fullpath;	
			
		} else {	
			return $pdf->Output();		
		}		
	}
	
	public function SendToPrinter($name, $company)  { 
		return $this->CreatePDFRendering($name, $company, true);	
	}
	
	public function SetSaveDirectory($directory) {
		$this->savedirectory = $directory;
		
	}

	public function GetSaveDirectory() {
		return $this->savedirectory;
	}
	
}
?>