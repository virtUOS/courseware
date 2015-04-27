<?php

/**
 * 020_cronjob_preliminary_participants.php
 *
 * @author Till Glöggler <tgloeggl@uos.de>
 */
class CronjobPreliminaryParticipants extends Migration
{

    const FILENAME = 'public/plugins_packages/virtUOS/Mooc/cronjobs/preliminary_participants.php';

    public function description()
    {
        return 'add cronjob for moving preliminary participants after course start';
    }

    public function up()
    {
        $task_id = CronjobScheduler::registerTask(self::FILENAME, true);

        // Schedule job to run every day at 23:59
        CronjobScheduler::schedulePeriodic($task_id, -5);  // negative value means "every x minutes"
    }

    function down()
    {
        if ($task_id = CronjobTask::findByFilename(self::FILENAME)->task_id) {
            CronjobScheduler::unregisterTask($task_id);
        }
    }
}