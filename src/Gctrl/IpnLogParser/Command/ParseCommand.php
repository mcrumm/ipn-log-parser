<?php

namespace Gctrl\IpnLogParser\Command;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;
use Dubture\Monolog\Reader\LogReader;

class ParseCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('parse')
            ->setDescription('Parses a ground(ctrl) IPN log.')
            ->setDefinition(array(
				new InputArgument('path', InputArgument::REQUIRED, 'The path to the log file.')
			))
			->setHelp(<<<EOT
The <info>%command.name%</info> command remove the branch subdomain from the staging environment.
EOT
			);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $path   = $input->getArgument('path');

        try {
            $reader = new LogReader($path);
        }
        catch (\RuntimeException $fileProblem) {
            $output->writeln(sprintf('<error>The file "%s" could not be opened.', $path));
            return;
        }

        $output->writeln(sprintf('Found %d records in the log.', count($reader)));

        foreach ($reader as $log) {

            if (isset($log['date'])) {
                $output->writeln($log['date']->format('Y-m-d h:i:s') . ' - ' . $log['message']);
            } else {
                $output->writeln($log['message']);
            }

            if (isset($log['context'])) {
                $context = $log['context'];
                if (count($context) == 1) {
                    $output->writeln(sprintf('Request: %s', print_r($context[0], true)));
                } elseif (count($context) == 2) {
                    $output->writeln(sprintf('Request: %s', print_r($context[0], true)));
                    $output->writeln(sprintf('Parameters: %s', print_r($context[1], true)));
                }
            }
        }
    }
}
