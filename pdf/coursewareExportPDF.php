<?php

    class CoursewareExportPDF extends TCPDF
    {
        public function __construct(
            $background = false,
            $orientation = "P",
            $unit = "mm",
            $format = "A4",
            $unicode = true,
            $encoding = "UTF-8"
        ) {
            parent::__construct(
                $orientation,
                $unit,
                $format,
                $unicode,
                $encoding,
                false
            );
            $this->setDefaults();
        }

        private function setDefaults()
        {
            $this->SetFont('dejavusans', '', 10);
            $this->SetTopMargin(20);
            $this->SetLeftMargin(20);
            $this->SetRightMargin(20);
        }

        public function writeHTML(
            $html,
            $ln = true,
            $fill = false,
            $reseth = false,
            $cell = false,
            $align = ""
        ) {
            return parent::writeHTML(
                $this->getDefaultStyle() . $html,
                $ln,
                $filll,
                $reseth,
                $cell,
                $align
            );
        }

        private $itemLinks = [];

        public function addLinkToItem($itemId)
        {
            $link = $this->AddLink();
            $this->itemLinks[$itemId] = $link;

            return $link;
        }

        public function getLinkToItem($id)
        {
            return isset($this->itemLinks[$id]) ? $this->itemLinks[$id]  : null;
        }

        private function getDefaultStyle()
        {
            ob_start(); ?>
    <style>
        h1 {
            font-weight: bold;
            background-color: #ffffff;
            font-size: 40px;
            line-height: 48px;
        }

        h2 {
            font-weight: bold;
            background-color: #ffffff;
            font-size: 32px;
            line-height: 38px;
        }

        h3 {
            font-weight: bold;
            background-color: #ffffff;
            font-size: 28px;
            line-height: 34px;
        }

        h4 {
            font-weight: bold;
            background-color: #ffffff;
            font-size: 24px;
            line-height: 29px;
        }

        h5 {
            font-weight: bold;
            background-color: #ffffff;
            font-size: 20px;
            line-height: 24px;
        }

        h6 {
            font-weight: bold;
            background-color: #ffffff;
            font-size: 16px;
            line-height: 24px;
        }

        div.block {
            background-color: #eeeeee;
        }

        dt {
            font-weight: bold;
        }
    </style>
        <?php return ob_get_clean();
    }
}
