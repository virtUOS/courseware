<?php

class CoursewareCertificatePDF extends TCPDF
{
    public function __construct($background = false, $orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8')
    {
        $this->config = Config::GetInstance();
        if ($this->config->getValue('LOAD_EXTERNAL_MEDIA') == 'proxy') {
            $this->media_proxy = new MediaProxy();
        }
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, false);
        if ($background) {
            $this->background = $background;
        } else {
            $this->background = dirname(__FILE__).'/../assets/images/pdf_default_background.jpg';
        }

        $this->setDefaults();
    }

    public function Header()
    {
        $bMargin = $this->getBreakMargin();
        $auto_page_break = $this->AutoPageBreak;
        $this->SetAutoPageBreak(false, 0);
        $this->Image($this->background, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
        $this->setFont('helvetica', 'B', 50);
        $this->Cell(0, 160, 'Z E R T I F I K A T', 0, false, 'C', 0, '', 0, false, 'T', 'M');
        $this->SetAutoPageBreak($auto_page_break, $bMargin);
        $this->setPageMark();
    }

    private function setDefaults()
    {
        $this->SetTopMargin(110);
        $this->SetLeftMargin(20);
        $this->SetRightMargin(20);
    }
}