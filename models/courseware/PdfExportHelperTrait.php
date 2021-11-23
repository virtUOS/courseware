<?php

namespace Courseware;

use SVG\SVG;

trait PdfExportHelperTrait
{
    private function getRasteredIcon($color, $icon, $size = 20)
    {
        $iconFilename = sprintf(
            "%s/public/assets/images/icons/%s/%s.svg",
            $GLOBALS["STUDIP_BASE_PATH"],
            $color,
            $icon
        );

        $iconSvg = @file_get_contents($iconFilename);
        if (!$iconSvg) {
            return false;
        }
        $image = SVG::fromString($iconSvg);
        $rasterImage = $image->toRasterImage($size, $size, "white");

        ob_start();
        imagegif($rasterImage);
        $rastered = ob_get_clean();

        return "@" . base64_encode($rastered);
    }
}
