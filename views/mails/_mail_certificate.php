<?php

$message = [
   'header' => sprintf(
        _('In der Courseware der Veranstaltung %s haben Sie %s%% Fortschritt erreicht'),
        $course_member['course_name'],
        $user_progress
    ),
    'content' => _('Anbei erhalten Sie Ihr Zertifikat. Dieses Zertifikat können Sie auch unter dem Menüpunkt "Fortschrittsübersicht" in Courseware herunterladen.'),
    'url' => _('Courseware Fortschrittsübersicht ansehen:') .' '. UrlHelper::getUrl(
        $GLOBALS['ABSOLUTE_URI_STUDIP']
        . 'plugins.php/courseware/progress/'
        .'?cid='
        . $course_member['seminar_id']
    )
];

$htmlMessage = Studip\Markup::markAsHtml(
    implode('<br>', array_map('formatReady', $message))
);

echo $htmlMessage;
