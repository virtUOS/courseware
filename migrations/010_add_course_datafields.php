<?php

/**
 * @author Till Glöggler <tgloeggl@uos.de>
 */
class AddCourseDatafields extends Migration
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
            VALUES (md5('(M)OOC Startdatum'), '(M)OOC Startdatum', 1,
                NULL, 3, 1, '0', NULL, NULL, 5, '', '0', 'Startdatum des (M)OOC Kurses')"
        );

        DBManager::get()->exec(
            "INSERT INTO `datafields` (`datafield_id`, `name`, `object_type`,
                `object_class`, `edit_perms`, `view_perms`, `priority`,
                `mkdate`, `chdate`, `type`, `typeparam`, `is_required`, `description`)
            VALUES (md5('(M)OOC Dauer'), '(M)OOC Dauer', 1,
                NULL, 3, 1, '0', NULL, NULL, 2, '', '0', 'Dauer des (M)OOC Kurses')"
        );
        
        DBManager::get()->exec(
            "INSERT INTO `datafields` (`datafield_id`, `name`, `object_type`,
                `object_class`, `edit_perms`, `view_perms`, `priority`,
                `mkdate`, `chdate`, `type`, `typeparam`, `is_required`, `description`)
            VALUES (md5('(M)OOC Hinweise'), '(M)OOC Hinweise', 1,
                NULL, 3, 1, '0', NULL, NULL, 3, '', '0', 'Hinweise für (M)OOC Kurs')"
        );        
    }

    public function down()
    {
        DBManager::get()->exec(
            "DELETE FROM datafields WHERE datafield_id "
                . "IN(md5('(M)OOC Startdatum'), md5('(M)OOC Dauer'), md5('(M)OOC Hinweise'))"
        );
    }
}
