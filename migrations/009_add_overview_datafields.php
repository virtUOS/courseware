<?php

/**
 * @author Till Glöggler <tgloeggl@uos.de>
 */
class AddOverviewDatafields extends Migration
{

    public function description()
    {
        return 'add datafields for course-overview sidebar';
    }

    public function up()
    {
        DBManager::get()->exec(
            "INSERT INTO `datafields` (`datafield_id`, `name`, `object_type`,
                `object_class`, `edit_perms`, `view_perms`, `priority`,
                `mkdate`, `chdate`, `type`, `typeparam`, `is_required`, `description`)
            VALUES (md5('(M)OOC-Preview-Image'), '(M)OOC-Preview-Image', 1,
                NULL, 3, 1, '0', NULL, NULL, 2, '', '0', 'URL für ein Vorschaubild')"
        );

        DBManager::get()->exec(
            "INSERT INTO `datafields` (`datafield_id`, `name`, `object_type`,
                `object_class`, `edit_perms`, `view_perms`, `priority`,
                `mkdate`, `chdate`, `type`, `typeparam`, `is_required`, `description`)
            VALUES (md5('(M)OOC-Preview-Video (mp4)'), '(M)OOC-Preview-Video (mp4)', 1,
                NULL, 3, 1, '0', NULL, NULL, 2, '', '0', 'URL für ein Vorschauvideo')"
        );
    }

    public function down()
    {
        DBManager::get()->exec(
            "DELETE FROM datafields WHERE datafield_id "
                . "IN(md5('(M)OOC-Preview-Image'), md5('(M)OOC-Preview-Video (mp4)'))"
        );
    }
}
