<?php
$p = '<p style="font-size: 20px; text-align: center;">';
$pb = '<p style="font-size: 20px; text-align: center; font-weight: bold">';
$html = $p;
$html .=  _('Hiermit wird bescheinigt, dass');
$html .= '</p>';
$html .= $pb;
if ($user->geschlecht == 1) {
    $html .= 'Herr' . ' ';
}
if ($user->geschlecht == 2) {
    $html .= 'Frau' . ' ';
}
$html .= $user->getFullname();
$html .= '</p>';
$html .= $p;
$html .=  _('am') . ' ' . date('d.m.Y', time());
$html .= '</p>';
$html .= $p;
$html .=  _('erfolgreiche am Seminar');
$html .= '</p>';
$html .=  $pb . $course->name . '</p>';
$html .= $p;
$html .=  _('teilgenommen hat.');
$html .= '</p>';

echo studip_utf8encode($html);
