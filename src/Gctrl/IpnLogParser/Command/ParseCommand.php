<?php

namespace Gctrl\IpnLogParser\Command;

use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

class ParseCommand extends AbstractLogCommand
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
The <info>%command.name%</info> command parses an IPN log file.
EOT
			);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $path   = $input->getArgument('path');
            $reader = $this->getReader($path);
        } catch (\RuntimeException $fileProblem) {
            $output->writeln(sprintf('<error>The file "%s" could not be opened.', $path));
            return;
        }

        $output->writeln(sprintf('Found %d records in the log.', count($reader)));

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
    }
}
