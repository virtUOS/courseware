<?php

use Mooc\DB\Block as DbBlock;
use Mooc\DB\Field;
use Mooc\DB\MailLog;
use Mooc\DB\UserProgress;

require_once 'lib/classes/CronJob.class.php';

/**
 * Courseware cronjob for Stud.IP
 *
 * @author    Ron Lucke <lucke@elan-ev.de>
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */
class CoursewareMailCronjob extends CronJob
{
    public static function getName()
    {
        return _('Courseware E-Mails senden');
    }

    public static function getDescription()
    {
        return _('Sendet E-Mails zur Erinnerung, beim Zurücksetzen des Fortschrittes und bei Erhalt von Zertifikaten');
    }

    public static function getParameters()
    {
        return [
            'verbose' => [
                'type'        => 'boolean',
                'default'     => false,
                'status'      => 'optional',
                'description' => _('Sollen Ausgaben erzeugt werden'),
            ],
        ];
    }

    public function setUp()
    {
        $this->courseware_plugin = PluginEngine::getPlugin('Courseware');
    }

    public function execute($last_result, $parameters = [])
    {
        $coursewares = DbBlock::findBySQL('type = ?', ['Courseware']);

        //find all courses with courseware cert active
        $cert_coursewares = $this->filterCoursewares($coursewares, 'certificate');
        $this->sendCertificates($cert_coursewares);

        //find all courses with courseware reminder active
        $reminder_coursewares = $this->filterCoursewares($coursewares, 'reminder');
        $this->sendReminder($reminder_coursewares);

        //find all courses with courseware resetter active
        $resetter_coursewares = $this->filterCoursewares($coursewares, 'resetter');
        $this->sendResetter($resetter_coursewares);
    }

    private function filterCoursewares(
        array $all_coursewares,
        string $activeElement
    ) {
        $courses = [];

        foreach ($all_coursewares as $courseware) {
            $field = Field::findOneBySQL('block_id = ? AND name = ?', [
                $courseware->id,
                $activeElement,
            ]);
            if ('true' == $field->json_data) {
                $course['courseware_db'] = $courseware;
                $course['courseware_ui'] = $this->courseware_plugin
                    ->getBlockFactory()
                    ->makeBlock($courseware);
                $course['fields'] = Field::findOneBySQL('block_id = ?', [
                    $courseware->id,
                ]);
                $courses[$courseware->seminar_id] = $course;
            }
        }

        return $courses;
    }

    private function courseProgress($cid, $uid)
    {
        $subchapters = DbBlock::findBySQL('seminar_id = ? AND type = ?', [
            $cid,
            'Subchapter',
        ]);
        $complete = 0;
        foreach ($subchapters as $subchapter) {
            $complete += self::subchapterComplete($subchapter, $uid);
        }

        return count($subchapters) ? (int) ($complete / count($subchapters)) : 0;
    }

    private function subchapterComplete($subchapterblock, $uid)
    {
        $blocks = 0;
        $blocks_progress = 0;
        foreach ($subchapterblock->children as $section) {
            $blocks += count($section->children);
            foreach ($section->children as $block) {
                $bid = $block->id;
                $progress = UserProgress::findOneBySQL('block_id = ? AND user_id = ?', [
                    $bid,
                    $uid,
                ]);
                if ($progress && 1 == $progress->grade / $progress->max_grade) {
                    ++$blocks_progress;
                }
            }
        }

        return $blocks ? (int) ($blocks_progress / $blocks) * 100 : 0;
    }

    private function sendCertificates($coursewares)
    {
        foreach ($coursewares as $cid => $courseware_data) {
            $course = \Course::find($cid);
            $students = $course->getMembersWithStatus('autor');
            $limit = $courseware_data['courseware_ui']->getCertificateLimit();
            foreach ($students as $student) {
                $student_progress = $this->courseProgress($cid, $student->user_id);
                if (
                    $student_progress >= $limit &&
                    !MailLog::hasCertificate($student->user_id, $cid)
                ) {
                    $this->sendCertificateMail(
                        $student,
                        $student_progress,
                        $courseware_data,
                        $course
                    );
                }
            }
        }
    }

    private function sendReminder($coursewares)
    {
        foreach ($coursewares as $cid => $courseware_data) {
            if (!$this->checkDate($courseware_data['courseware_ui'])) {
                continue;
            }
            $course = \Course::find($cid);
            $students = $course->getMembersWithStatus('autor');
            foreach ($students as $student) {
                $student_progress = $this->courseProgress($cid, $student->user_id);
                if ($student_progress < 100) {
                    $this->sendReminderMail(
                        $student,
                        $student_progress,
                        $courseware_data
                    );
                }
            }
        }
    }

    private function sendResetter($coursewares)
    {
        foreach ($coursewares as $cid => $courseware_data) {
            if (!$this->checkDate($courseware_data['courseware_ui'])) {
                continue;
            }
            $course = \Course::find($cid);
            $students = $course->getMembersWithStatus('autor');
            $blocks = DbBlock::findBySQL('seminar_id = ?', [$cid]);
            foreach ($students as $student) {
                $had_progress = false;
                foreach ($blocks as $block) {
                    $progress = UserProgress::findOneBySQL(
                        'block_id = ? AND user_id = ?',
                        [$block->id, $student->user_id]
                    );
                    if ($progress) {
                        $had_progress = true;
                        $progress->delete();
                        $maillog = MailLog::getCertificate($student->user_id, $cid);
                        if ($maillog) {
                            $maillog->delete();
                        }
                    }
                }
                if ($had_progress) {
                    $this->sendResetterMail($course_member, $courseware_data);
                }
            }
        }
    }

    private function sendCertificateMail(
        $course_member,
        $user_progress,
        $courseware_data,
        $course
    ) {
        $template_factory = new Flexi_TemplateFactory(
            dirname(__FILE__) . '/../views'
        );
        $template = $template_factory->open('mails/_mail_certificate');
        $htmlMessage = $template->render(
            compact('course_member', 'user_progress', 'courseware_data')
        );

        $mail = new StudipMail();
        $pdf_file_name =
            $course_member->nachname .
            '_' .
            $course->name .
            '_' .
            _('Zertifikat') .
            '.pdf';
        $pdf_file_path = $this->createCertificatePDF(
            $course_member,
            $course,
            $pdf_file_name,
            $courseware_data
        );

        $send_mail = $mail
            ->addRecipient(
                $course_member->email,
                $course_member->vorname . ' ' . $course_member->nachname
            )
            ->setSubject(
                $course_member->course_name .
                ' ' .
                _('[Courseware]') .
                ' - ' .
                _('Zertifikat')
            )
            ->setBodyHtml($htmlMessage)
            ->setBodyText(trim(kill_format($htmlMessage)))
            ->addFileAttachment($pdf_file_path, $pdf_file_name)
            ->send();

        if ($send_mail) {
            $this->createMailLog($course_member, 'certificate');
        }
    }

    private function sendReminderMail(
        $course_member,
        $user_progress,
        $courseware_data
    ) {
        $template_factory = new Flexi_TemplateFactory(
            dirname(__FILE__) . '/../views'
        );
        $template = $template_factory->open('mails/_mail_reminder');
        $htmlMessage = $template->render(
            compact('course_member', 'user_progress', 'courseware_data')
        );

        $mail = new StudipMail();
        $send_mail = $mail
            ->addRecipient(
                $course_member->email,
                $course_member->vorname . ' ' . $course_member->nachname
            )
            ->setSubject(
                $course_member->course_name .
                ' ' .
                _('[Courseware]') .
                ' - ' .
                _('Erinnerung')
            )
            ->setBodyHtml($htmlMessage)
            ->setBodyText(trim(kill_format($htmlMessage)))
            ->send();

        if ($send_mail) {
            $this->createMailLog($course_member, 'reminder');
        }
    }

    private function sendResetterMail($course_member, $courseware_data)
    {
        $template_factory = new Flexi_TemplateFactory(
            dirname(__FILE__) . '/../views'
        );
        $template = $template_factory->open('mails/_mail_resetter');
        $htmlMessage = $template->render(
            compact('course_member', 'courseware_data')
        );

        $mail = new StudipMail();
        $send_mail = $mail
            ->addRecipient(
                $course_member->email,
                $course_member->vorname . ' ' . $course_member->nachname
            )
            ->setSubject(
                $course_member->course_name .
                ' ' .
                _('[Courseware]') .
                ' - ' .
                _('Fortschritt zurückgesetzt')
            )
            ->setBodyHtml($htmlMessage)
            ->setBodyText(trim(kill_format($htmlMessage)))
            ->send();

        if ($send_mail) {
            $this->createMailLog($course_member, 'resetter');
        }
    }

    private function createMailLog($course_member, $mail_type)
    {
        $mail_log = new MailLog();
        $mail_log->user_id = $course_member->user_id;
        $mail_log->seminar_id = $course_member->seminar_id;
        $mail_log->mail_type = $mail_type;

        $mail_log->store();
    }

    private function checkDate($courseware)
    {
        $today = strtotime('today midnight');
        $start = $courseware->getResetterStartDate();
        $end = $courseware->getResetterEndDate();

        $start = '' === $start ? $today : strtotime($start);
        $end = '' === $end ? $today : strtotime($end);
        if ($today < $start || $today > $end) {
            return false;
        }

        $interval = $courseware->getResetterInterval();
        $is_in_interval = false;
        switch ($interval) {
            case '0': //wöchentlich
                $is_in_interval = date('N', $start) === date('N', $today);
                break;
            case '1': //14-tägig
                $is_in_interval = 0 === abs(date('W', $today) - date('W', $start)) % 2;
                break;
            case '2': //monatlich
                $is_in_interval = date('d', $start) === date('d', $today);
                if (
                    '31' === date('d', $start) &&
                    in_array(date('m', $today), ['04', '06', '09', '11'])
                ) {
                    $is_in_interval = '30' === date('d', $today);
                }
                if (intval(date('d', $start)) > 28 && '02' === date('m', $today)) {
                    $is_in_interval = '28' === date('d', $today);
                }
                break;
            case '3': // vierteljährlich
                $diff = abs(date('n', $start) - date('n', $today));
                $is_in_interval = 0 !== $diff && 0 === $diff % 3;
                break;
            case '4': // halbjährlich
                $diff = abs(date('n', $start) - date('n', $today));
                $is_in_interval = 0 !== $diff && 0 === $diff % 6;
                break;
            case '5': // jährlich
                $is_in_interval = date('d-m', $start) === date('d-m', $today);
                break;
        }

        return $is_in_interval;
    }

    private function createCertificatePDF(
        $course_member,
        $course,
        $pdf_file_name,
        $courseware_data
    ) {
        global $TMP_PATH;
        require_once dirname(__FILE__) . '/../pdf/coursewareCertificatePDF.php';

        $user = $course_member->user;
        $template_factory = new Flexi_TemplateFactory(
            dirname(__FILE__) . '/../views'
        );
        $template = $template_factory->open('mails/_pdf_certificate');
        $html = $template->render(compact('user', 'course'));

        $file_ref = new \FileRef(
            $courseware_data['courseware_ui']->getCertificateImageId()
        );
        if ($file_ref) {
            $file = new \File($file_ref->file_id);
            $background_image = $file['path'];
        } else {
            $background_image = false;
        }

        $pdf = new CoursewareCertificatePDF(($background = $background_image));
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output($TMP_PATH . '/' . $pdf_file_name, 'F');

        return $TMP_PATH . '/' . $pdf_file_name;
    }
}
