<?php

namespace Gctrl\IpnLogParser\Command\Find;

use Dubture\Monolog\Reader\LogReader;
use Gctrl\IpnLogParser\Command\AbstractLogCommand,
    Gctrl\IpnLogParser\Filter\LogLevelFilter;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;

class ErrorsCommand extends AbstractLogCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('find:errors')
            ->setDescription('Finds errors in the log.')
            ->setDefinition(array(
                new InputArgument('path', InputArgument::REQUIRED, 'The path to the log file.'),
                new InputOption('days', 'd', InputOption::VALUE_REQUIRED, 'The number of days to search.', 0),
                new InputOption('dump', 'u', InputOption::VALUE_NONE, 'Dump error lines', null),
			))
			->setHelp(<<<EOT
The <info>%command.name%</info> command finds request errors from the IPN log.
EOT
			);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $path  = $input->getArgument('path');
        $days  = $input->getOption('days');
        $dump  = $input->getOption('dump');

        try {
            $reader = $this->getReader($path, $days);
        } catch (\RuntimeException $fileProblem) {
            $output->writeln(sprintf('<error>The file "%s" could not be opened.', $path));
        }

        if (!$dump) {
            $count = $this->printTable($reader, $output);
        } else {
            $count = $this->dumpErrors($reader, $output);
        }

        $output->writeln(sprintf('Found %d errors in the log.', $count));
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
        $errors = new LogLevelFilter($reader, LogLevel::ERROR);
        $rows   = 0;

        foreach ($errors as $error) {
            $request = isset($error['context'][1]) ? $error['context'][1] : false;

            if (!$request) { continue; }

            $rows++;

            $output->writeln(sprintf('%d) %s', $rows, $error['message']));

            if (isset($error['date'])) {
                $output->writeln($error['date']->format('Y-m-d H:i:s'));
            }

            $output->writeln(print_r($request, true));
            $output->writeln(str_pad('', 80, '-'));
        }

        return $rows;
    }

    public function printTable(LogReader $reader, OutputInterface $output)
    {
        $table  = $this->getHelperSet()->get('table');        
        $errors = new LogLevelFilter($reader, LogLevel::ERROR);
        $rows   = 0;

        $table->setHeaders(array('Date', 'Transaction Type', 'Payment Status', 'Order #'));

        foreach ($errors as $error) {
            $request = isset($error['context'][1]) ? $error['context'][1] : false;

            if (!$request) { continue; }

            $rows++;

            $table->addRow(array(
                is_object($error['date']) ? $error['date']->format('Y-m-d H:i:s') : '',
                isset($request['txn_type']) ? $request['txn_type'] : '',
                isset($request['payment_status']) ? $request['payment_status'] : '',
                isset($request['invoice']) ? $request['invoice'] : '',
            ));
        }

        $table->render($output);

        return $rows;
    }
}
