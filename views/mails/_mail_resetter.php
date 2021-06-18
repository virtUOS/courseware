<?php

$message = [
   'header' => sprintf(
        _('Ihr Courseware-Fortschritt der Veranstaltung %s wurde zurückgesetzt'),
        $course_member['course_name']
    ),
    'content' => $courseware_data['courseware_ui']->getResetterMessage(),
    'url' => _('Courseware ansehen:') .' '. UrlHelper::getUrl(
        $GLOBALS['ABSOLUTE_URI_STUDIP']
        . 'plugins.php/courseware/courseware/'
        .'?cid='
        . $course_member['seminar_id']
    )
];

$htmlMessage = Studip\Markup::markAsHtml(
    implode('<br>', array_map('formatReady', $message))
);

echo $htmlMessage;
