<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class CheckTranslations extends Command
{
    protected function configure()
    {
        $this->setName('check:translations')
            ->setDescription('Check game namespace translations');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('=== Checking game namespace translations ===');
        $output->writeln('');

        $translations = Db::table('ui_translations')
            ->where('namespace', 'game')
            ->order('locale,key')
            ->select()
            ->toArray();

        if (empty($translations)) {
            $output->writeln('<error>No translations found for game namespace!</error>');
            return 1;
        }

        foreach ($translations as $row) {
            $output->writeln(sprintf('[%s] %s = %s', $row['locale'], $row['key'], $row['value']));
        }

        $output->writeln('');
        $output->writeln(sprintf('=== Total: %d translations ===', count($translations)));

        return 0;
    }
}
