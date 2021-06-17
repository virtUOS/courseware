<?php
$p = '<p style="font-size: 20px; text-align: center;">';
$span_bold = '<br /><br /><span style="font-size: 20px; text-align: center; font-weight: bold">';
$span_close = '</span><br /><br />';
switch($user->geschlecht) {
    case 1:
        $anrede = _('Herr');
        break;
    case 2:
        $anrede = _('Frau');
        break;
    default:
        $anrede= '';
}
echo $p;
printf(
    _("Hiermit wird bescheinigt, dass %s am %s erfolgreich am Seminar %s teilgenommen hat."), 
    $span_bold.$anrede.' '.$user->getFullname().$span_close,
    $span_bold.date('d.m.Y', time()).$span_close,
    $span_bold.$course->name.$span_close
);
echo '</p>';
