<?php
require_once $STUDIP_BASE_PATH.'/vendor/tcpdf/tcpdf.php';

class DFBPDF extends TCPDF {

    //Page header
    public function Header() {
            //rect
            $this->Rect(15, 8, 180, 30, 'F', '', array(0, 152, 101));
            
            // Logo
            $this->Image('plugins_packages/virtUOS/VipsPlugin/images/DFB-Logo_4c.png', 20, 15, 15, '', '', '', '', false, 300);
            // Set font
            $this->SetFont('helvetica', '', 16);
            $this->SetTextColor(255,255,255);
            // Title
            $this->SetLeftMargin(40);
            $this->Ln(16);
            $this->writeHTMLCell(140, '', 40, 12, $this->dfbtitle1, 0, 1, 1, true, 'J', true);
            $this->Ln(8);
            $this->writeHTMLCell(140, '', 40, 20, $this->dfbtitle2, 0, 1, 1, true, 'J', true);
    }

    // Page footer
    public function Footer() {
            // Position at 15 mm from bottom
            $this->SetY(-15);
            // Set font
            $this->SetFont('helvetica', '', 10);
            $this->SetTextColor(0,127,75);
            // Page number
            $this->Cell(0, 0, '               SEITE '.$this->getAliasNumPage().' VON '.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'C', 'C');
    }
    
    public function addShadow($x,$y,$h,$w){

        for($i=2;$i>=1;$i-=0.1){
            $this->SetAlpha(0.1-($i*0.04));
            $this->SetFillColor(0, 0, 0);
            $this->SetDrawColor(0, 0, 0);
            $this->Rect($x+$i, $y+$i, $h, $w, 'DF');
        }

        $this->SetAlpha(1);
    }
}
