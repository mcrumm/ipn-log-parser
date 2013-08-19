<?php

namespace Gctrl\IpnLogParser\Command\Log;

use Gctrl\IpnLogParser\Command\AbstractLogCommand;
use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;

class ParseCommand extends AbstractLogCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('log:parse')
            ->setDescription('Parses a ground(ctrl) IPN log.')
            ->setDefinition(array(
				new InputArgument('path', InputArgument::REQUIRED, 'The path to the log file.'),
                new InputOption('days', 'd', InputOption::VALUE_REQUIRED, 'The number of days to search. Default: 0', 0),
			))
			->setHelp(<<<EOT
The <info>%command.name%</info> command parses an IPN log file.
EOT
			);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $path   = $input->getArgument('path');
        $days   = $input->getOption('days');

        try {
            $reader = $this->getReader($path, $days);
        } catch (\RuntimeException $fileProblem) {
            $output->writeln(sprintf('<error>The file "%s" could not be opened.', $path));
            return;
        }

        foreach ($reader as $log) {

            if (isset($log['date'])) {
                $output->writeln($log['date']->format('Y-m-d h:i:s') . ' - ' . $log['level']);
            } else {
                $output->writeln($log['level']);
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

        $output->writeln(sprintf('Found %d records in the log.', count($reader)));
    }
}
