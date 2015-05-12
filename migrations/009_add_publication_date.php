<?php

/**
 * @author Till Glöggler <tgloeggl@uos.de>
 */
class AddPublicationDate extends Migration
{

    public function description()
    {
        return 'add datafields for publication_data for chapters and subchapters';
    }

    public function up()
    {
        DBManager::get()->exec("ALTER TABLE `mooc_blocks`
            ADD `publication_date` int(11) NULL AFTER `position`"
        );
    }

    public function down()
    {
        DBManager::get()->exec("ALTER TABLE `mooc_blocks`
            DROP `publication_date`"
        );
    }
}
