<?php

namespace Mooc\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

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
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('<question>Do you really want to reset all statuses? (y|n)</question> ', false);
        if(!$helper->ask($input, $output, $question)) {
            return true;
        }
        $output->write('<comment>Resetting courseware migration status... </comment>');
        \Mooc\DB\MigrationStatus::resetMigrationStatus();
        $output->writeln('<comment>done!</comment>');

        return 0;
    }
}
