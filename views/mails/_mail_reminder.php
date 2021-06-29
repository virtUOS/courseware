<?php

$message = [
   'header' => sprintf(
        _('In der Courseware der Veranstaltung %s haben Sie %s%% Fortschritt erreicht'),
        $course_member['course_name'],
        $user_progress
    ),
    'content' => $courseware_data['courseware_ui']->getReminderMessage(),
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
