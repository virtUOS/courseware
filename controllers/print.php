<?php

class PrintController extends CoursewareStudipController {

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        PageLayout::addStylesheet($GLOBALS['ABSOLUTE_URI_STUDIP'] . $this->plugin->getPluginPath() . '/assets/courseware.min.css');

    }

    public function index_action()
    {
    }
    
    function note_action()
    {
        global $vipsPlugin, $STUDIP_BASE_PATH;
        require_once $STUDIP_BASE_PATH.'/vendor/tcpdf/tcpdf.php';
        require_once $this->plugin->getPluginPath().'/models/courseware/Dfbpdf.php';
        
        $note_content = Request::get("note-data");
        $note_type = Request::get("note-type");
        $note_color = Request::get("note-color");
        $note_header1 = Request::get("note-header1");
        $note_header2 = Request::get("note-header2");
        $note_questions = Request::get("note-questions");
        $note_content = json_decode(htmlentities($note_content, ENT_HTML401, false));
        $note_questions = json_decode($note_questions);
        array_walk($note_content, function(&$val){$val = html_entity_decode(nl2br($val));});
        
        // create new PDF document
        $pdf = new DFBPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'ISO-8859', false);
        $pdf->dfbtitle1 = $this->htmlentitiesOutsideHTMLTags($note_header1, ENT_HTML401);
        $pdf->dfbtitle2 = $this->htmlentitiesOutsideHTMLTags($note_header2, ENT_HTML401);
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetTopMargin(40);
        $pdf->SetLeftMargin(15);
        $pdf->SetRightMargin(20);
        $pdf->AddPage();
        if ($note_type == "post-it") {
            $pdf->Cell(0, 0, $note_questions[0], 0, false, 'L', 0, '', 0, false, 'M', 'B');
            $pdf->Ln(12);
        }
        $x = 45;
        $y = $pdf->getY();
        $w = 120;
        $h = 60;
        if ($note_type == "post-it") {
            foreach ($note_content as $key => $text) {
                $pdf->StartTransform();
                $pdf->Rotate(1, $x, $y+$h);
                $pdf->addShadow($x,$y, $w,$h);
                $pdf->SetFont('', '', 25,'', false);    
                $pdf->Rect($x, $y, $w, $h, 'DF', array(0,0,0,0), $this->getColor($note_color));
                $pdf->writeHTMLCell($w-10, $h-10, $x+5, $y+5 ,$text);
                // Stop Transformation
                $pdf->StopTransform();
                //if ($key%2 == 0) {
                //     $x += $w+10;
                //} else {
                //  $x -= $w+10;
                    $y += $h+10; 
                //}
                if( ($y > 260) && ($key !== count($note_content)-1)) {
                    $pdf->AddPage();
                    $x = 35;
                    $y = 50;
                }
            }
        } else if ($note_type == "classic") {
            $x = 15;
            $y = $pdf->getY();
            foreach ($note_content as $key => $text) {
                $pdf->SetFillColor(255,255,255);
                $pdf->SetFont('', 'B', 20,'', false);
                $pdf->writeHTMLCell(180, "", $x, $y ,$note_questions[$key]);
                $y += 16;
                $pdf->SetFont('', '', 25,'', false);
                $pdf->writeHTMLCell(180, "", $x, $y ,$text, 0, 1, 1, true, 'J', true);
                $y +=  $pdf->getY()+24;
                if (($y > 260) && ($key !== count($note_content)-1)){
                    $pdf->AddPage();
                    $y = 50;
                }
            }
        } else {
            $html = $note_content[0];
            $pdf->writeHTML($html, true, 0, true, 0);
        }
        $filename = $note_header1."-".$note_header2.".pdf";
        $pdf->Output($filename);
        exit("delivering pdf file");
    }
    
    function selfevaluation_action()
    {
        global $vipsPlugin, $STUDIP_BASE_PATH;
        require_once $STUDIP_BASE_PATH.'/vendor/tcpdf/tcpdf.php';
        require_once $this->plugin->getPluginPath().'/models/courseware/Dfbpdf.php';
        
        $selfevaluation_content = Request::get("selfevaluation-data");
        $selfevaluation_title = Request::get("selfevaluation-title");
        $selfevaluation_subtitle = Request::get("selfevaluation-subtitle");
        $selfevaluation_description = Request::get("selfevaluation-description");
        $selfevaluation_headerleft = Request::get("selfevaluation-headerleft");
        $selfevaluation_headerright = Request::get("selfevaluation-headerright");
        $selfevaluation_content  = json_decode(htmlentities($selfevaluation_content, ENT_HTML401, false));
        // create new PDF document
        $pdf = new DFBPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'ISO-8859', false);
        $pdf->dfbtitle1 = $this->htmlentitiesOutsideHTMLTags($selfevaluation_title, ENT_HTML401);
        $pdf->dfbtitle2 = $this->htmlentitiesOutsideHTMLTags($selfevaluation_subtitle, ENT_HTML401);
        $pdf->SetTopMargin(40);
        $pdf->SetLeftMargin(20);
        $pdf->SetRightMargin(20);
        $pdf->AddPage();
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(0, 0, 0);
        //$pdf->SetFontSize(16);
        //$pdf->writeHTMLCell(180, '', 15, 40, $this->htmlentitiesOutsideHTMLTags($selfevaluation_title, ENT_HTML401), 0, 1, 1, true, 'J', true);
        
        $y = $pdf->getY()+10;
        $pdf->writeHTMLCell(90, '', 15, $y, $this->htmlentitiesOutsideHTMLTags($selfevaluation_headerleft, ENT_HTML401), 0, 1, 1, true, 'J', true);
        $pdf->writeHTMLCell(90, '', 98, $y, $this->htmlentitiesOutsideHTMLTags($selfevaluation_headerright, ENT_HTML401), 0, 1, 1, true, 'J', true);
        
        $y = $pdf->getY()+10;
        $w = 24;
        $w_textcell = 84;
        $h = 12;
        
        $style1 = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(255, 255, 255));
        $style2 = array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0));
        
        $pdf->SetLineStyle($style1);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->MultiCell($w_textcell, $h, '', 1, 'J', 1, 0, 15, $y, true, 0, false, true, $h, 'T');
        $pdf->SetFillColor(0, 127, 75);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetLineStyle($style1);
        //$pdf->ImageSVG($file='plugins_packages/virtUOS/Courseware/assets/images/SmileyI.svg', $x=30, $y=100, $w='', $h=100, $link='', $align='', $palign='', $border=0, $fitonpage=false);
        $pdf->Image('plugins_packages/virtUOS/Courseware/assets/images/SmileyI.png', $w_textcell+23, $y+2, 8, '', '', '', '', false, 100);
        $pdf->MultiCell($w, $h, "", 1, 'C', 1, 0, $w_textcell+15, $y, true, 0, false, true, $h, 'M');
        $pdf->Image('plugins_packages/virtUOS/Courseware/assets/images/SmileyII.png', $w_textcell+$w+23, $y+2, 8, '', '', '', '', false, 100);
        $pdf->MultiCell($w, $h, "", 1, 'C', 1, 1, $w_textcell+$w+15, $y, true, 0, false, true, $h, 'M');
        $pdf->Image('plugins_packages/virtUOS/Courseware/assets/images/SmileyIII.png', $w_textcell+$w*2+23, $y+2, 8, '', '', '', '', false, 100);
        $pdf->MultiCell($w, $h, "", 1, 'C', 1, 1, $w_textcell+$w*2+15, $y, true, 0, false, true, $h, 'M');
        $pdf->Image('plugins_packages/virtUOS/Courseware/assets/images/SmileyIV.png', $w_textcell+$w*3+23, $y+2, 8, '', '', '', '', false, 100);
        $pdf->MultiCell($w, $h, "", 1, 'C', 1, 1, $w_textcell+$w*3+15, $y, true, 0, false, true, $h, 'M');
        $pdf->Ln(4);
        
        $y = $y+$h;
        $h = 12;
        $cx = 0;
        $cy = 0;
        foreach($selfevaluation_content as $key => $content) {
            $txt = $content->element;
            
            $pdf->SetLineStyle($style1);
            $pdf->SetFillColor(0, 127, 75);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFontSize(10);
            $pdf->writeHTMLCell($w_textcell, $h,  15, $y, $txt, 0, 1, 1, true, 'L', true);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetLineStyle(array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(200, 200, 200)));
            $pdf->MultiCell($w, $h, '', 1, 'J', 1, 0, $w_textcell+15, $y, true, 0, false, true, $h, 'T');
            $pdf->MultiCell($w, $h, '', 1, 'J', 1, 1, $w_textcell+$w+15, $y, true, 0, false, true, $h, 'T');
            $pdf->MultiCell($w, $h, '', 1, 'J', 1, 1, $w_textcell+$w*2+15, $y, true, 0, false, true, $h, 'T');
            $pdf->MultiCell($w, $h, '', 1, 'J', 1, 1, $w_textcell+$w*3+15, $y, true, 0, false, true, $h, 'T');
            if (($cx != 0) && ($cy != 0)) {$lastcy = $cy; $lastcx = $cx;}
            $cy = $y+$h/2;
            switch($content->value) {
                case "++":
                    $cx = $x+$w_textcell+$w/2+15;
                    break;
                case "+":
                    $cx = $x+$w_textcell+$w+$w/2+15;
                    break;
                case "-":
                    $cx = $x+$w_textcell+$w*2+$w/2+15;
                    break;
                case "--":
                    $cx = $x+$w_textcell+$w*3+$w/2+15;
                    break;
                
            }
            
            $pdf->SetLineStyle($style2);
            if ($lastcx != 0) { 
                $pdf->Line($lastcx, $lastcy, $cx, $cy);
                $pdf->SetFillColor(255,255,255);
                $pdf->Circle($lastcx, $lastcy, 2, 0, 360, 'DF');
            }
            $pdf->Ln(4);
            $y = $y+$h;
            if ($y > 260) {
                    $pdf->Circle($cx, $cy, 2, 0, 360, 'DF');
                    $pdf->AddPage();
                    $y = 50;
                    $h = 12;
                    $pdf->SetLineStyle($style1);
                    $pdf->SetFillColor(255, 255, 255);
                    $pdf->MultiCell($w_textcell, $h, '', 1, 'J', 1, 0, 15, $y, true, 0, false, true, $h, 'T');
                    $pdf->SetFillColor(0, 127, 75);
                    $pdf->SetTextColor(255, 255, 255);
                    $pdf->SetLineStyle($style1);
                    $pdf->MultiCell($w, $h, "++", 1, 'C', 1, 0, $w_textcell+15, $y, true, 0, false, true, $h, 'M');
                    $pdf->MultiCell($w, $h, "+", 1, 'C', 1, 1, $w_textcell+$w+15, $y, true, 0, false, true, $h, 'M');
                    $pdf->MultiCell($w, $h, "-", 1, 'C', 1, 1, $w_textcell+$w*2+15, $y, true, 0, false, true, $h, 'M');
                    $pdf->MultiCell($w, $h, "--", 1, 'C', 1, 1, $w_textcell+$w*3+15, $y, true, 0, false, true, $h, 'M');
                    $pdf->Ln(4);
                    $y = $y+$h;
                    $x = 0;
                    $h = 24;
                    $cx = 0;
                    $cy = 0;
                    $lastcx = 0;
                    $lastcy = 0;
            }
            
        }
        $pdf->Circle($cx, $cy, 2, 0, 360, 'DF');
        $filename = $selfevaluation_title."-".$selfevaluation_subtitle.".pdf";
        $y = $pdf->getY()+4;
        $pdf->SetFontSize(12);
        $pdf->SetTextColor(0, 0, 0);
        $border_width = 2;
        $border_settings = array('LTRB' => array('width' => $border_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 127, 75)));
        $pdf->setCellPaddings( $left = '2', $top = '2', $right = '2', $bottom = '2');
        $pdf->writeHTMLCell(180, '', 15, $y, $this->htmlentitiesOutsideHTMLTags($selfevaluation_description, ENT_HTML401), $border_settings, 1, 1, true, 'J', true);
        
        $pdf->Output($filename);
        exit("delivering pdf file");
    }
    
    private function getColor($colorname) 
    {
        $colors = array(
            "white"    => array(255,255,255),
            "yellow"   => array(254,250,188),
            "blue"     => array(188,254,250),
            "green"    => array(188,250,188),
            "red"      => array(254,188,188),
            "orange"   => array(254,188,108)
        );
        
        return $colors[$colorname];
    }
    
    /*
 *    Wandelt Sonderzeichen in HTML-Entities um, 
 *    lässt aber die HTML-Tags bestehen.
 *    @param string $htmlText Zeichenkette die HTML-Tags und Sonderzeichen enthält
 *    @param obj $ent flag für htmlentities
 * 
 *    @return string gibt Zeichenkette mit darstellbarem HTML wieder 
 */

    private function htmlentitiesOutsideHTMLTags($htmlText, $ent)
    {
        $matches = Array();
        $sep = '###HTMLTAG###';

        preg_match_all(":</{0,1}[a-z]+[^>]*>:i", $htmlText, $matches);

        $tmp = preg_replace(":</{0,1}[a-z]+[^>]*>:i", $sep, $htmlText);
        $tmp = preg_replace('/<!-- [^>]+\-->/i', "", $tmp); 
        
        $tmp = explode($sep, $tmp);

        for ($i=0; $i<count($tmp); $i++)
            $tmp[$i] = htmlentities($tmp[$i], $ent,  false);

        $tmp = join($sep, $tmp);

        for ($i=0; $i<count($matches[0]); $i++)
            $tmp = preg_replace(":$sep:", $matches[0][$i], $tmp, 1);

        return $tmp;
    }
}
