<?php

namespace Mooc\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Ron Lucke <lucke@elan-ev.de>
 */

class MigrateResetStatus extends Command
{
    protected function configure()
    {
        $this->setName('courseware:resetmigrationstatus');
        $this->setDescription('reset the migration status');
    }
    public function execute(InputInterface $input, OutputInterface $output)
    {
        \Mooc\DB\MigrationStatus::resetMigrationStatus();
    }
}