<?php

namespace Gctrl\IpnLogParser\Command\Dump;

use Dubture\Monolog\Reader\LogReader;
use Gctrl\IpnLogParser\Command\AbstractLogCommand;
use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

class RequestsCommand extends AbstractLogCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('dump:requests')
            ->setDescription('Dumps request object data.')
            ->setDefinition(array(
				new InputArgument('path', InputArgument::REQUIRED, 'The path to the log file.')
			))
			->setHelp(<<<EOT
The <info>%command.name%</info> command dumps requests objects from the IPN log.
EOT
			);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');

        try {
            $reader = $this->getReader($path);
            $this->dumpRequests($reader, $output);
        } catch (\RuntimeException $fileProblem) {
            $output->writeln(sprintf('<error>The file "%s" could not be opened.', $path));
        }
    }

    /**
     * Dump Requests
     *
     * @param \Dubture\Monolog\Reader\LogReader $reader
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    public function dumpRequests(LogReader $reader, OutputInterface $output)
    {
        foreach ($reader as $log) {
            if (isset($log['context']) && count($log['context']) > 1 ) {
                $output->writeln(json_encode($log['context'][1]));
            }
        }
    }
}