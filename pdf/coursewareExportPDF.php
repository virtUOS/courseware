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
            font-size: 32px;
            line-height: 32px;
        }

        h2 {
            font-weight: bold;
            background-color: #ffffff;
            font-size: 20px;
            line-height: 36px;
        }

        h3 {
            font-weight: bold;
            background-color: #ffffff;
            font-size: 18px;
            line-height: 20px;
        }

        h4 {
            font-weight: bold;
            background-color: #ffffff;
            font-size: 16px;
            line-height: 18px;
        }

        h5 {
            font-weight: bold;
            background-color: #ffffff;
            font-size: 12px;
            line-height: 14px;
        }

        h6 {
            font-weight: normal;
            font-style: italic;
            color: #888;
            background-color: #ffffff;
            font-size: 10px;
            line-height: 8px;
            text-align: right;
        }

        hr {
            height: 1px;
        }

        div.block {
            background-color: #fff;
        }

        dt {
            font-weight: bold;
        }
    </style>
        <?php return ob_get_clean();
    }
}
