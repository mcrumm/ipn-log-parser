<?php

namespace Gctrl\IpnLogParser\Command;

use Dubture\Monolog\Reader\LogReader;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

class DumpErrorsCommand extends AbstractLogCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('dump:errors')
            ->setDescription('Dumps errors in the log.')
            ->setDefinition(array(
				new InputArgument('path', InputArgument::REQUIRED, 'The path to the log file.')
			))
			->setHelp(<<<EOT
The <info>%command.name%</info> command dumps request errors from the IPN log.
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
            $this->dumpErrors($reader, $output);
        } catch (\RuntimeException $fileProblem) {
            $output->writeln(sprintf('<error>The file "%s" could not be opened.', $path));
        }
    }

    /**
     * Dump Errors
     *
     * @param \Dubture\Monolog\Reader\LogReader $reader
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    public function dumpErrors(LogReader $reader, OutputInterface $output)
    {
        $errors = 0;

        foreach ($reader as $log) {
            if (LogLevel::ERROR !== strtolower($log['level'])) {
                continue;
            }

            $output->writeln(++$errors . '. ' . $log['message']);

            if (isset($log['date'])) {
                $output->writeln($log['date']->format('Y-m-d H:i:s'));
            }
            $output->writeln(print_r($log['context'][1], true));
            $output->writeln(str_pad('', 80, '-'));
        }

        $output->writeln(sprintf('Found %d errors in the log.', $errors));
    }
}
