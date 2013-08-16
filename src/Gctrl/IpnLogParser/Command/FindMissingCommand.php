<?php

namespace Gctrl\IpnLogParser\Command;

use Dubture\Monolog\Reader\LogReader;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Helper\TableHelper,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface;

class FindMissingCommand extends AbstractLogCommand
{
    protected $transactions;

    protected $corrections;

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->transactions = array();
        $this->corrections  = array();

        $this->setName('find:missing')
            ->setDescription('Finds error requests that have not yet been retried.')
            ->setDefinition(array(
				new InputArgument('path', InputArgument::REQUIRED, 'The path to the log file.')
			))
			->setHelp(<<<EOT
The <info>%command.name%</info> loops through requests, looking for errors and
returning any requests that have not self-corrected.
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
        $txnErrors      = $this->gatherErrorTransactions($reader);
        $txnCorrections = $this->gatherCorrectedTransactions($reader);
        $txnMissing     = $txnErrors - $txnCorrections;

        $helper = $this->getHelperSet()->get('table');
        $table  = $this->getMissingTable($helper);

        $table->setLayout(TableHelper::LAYOUT_BORDERLESS)->render($output);

        $output->writeln(sprintf('Found %d records missing corrections.', $txnMissing));
    }

    public function gatherErrorTransactions(LogReader $reader)
    {
        $reader->rewind();

        $transactions = 0;

        foreach ($reader as $log) {
            if (!$this->isErrorLine($log)) {
                continue;
            }

            $request = $this->getRequest($log);
            if (empty($request)) {
                continue;
            }

            $txn_id  = $this->getTransactionId($request);

            if (!array_key_exists($txn_id, $this->transactions)) {
                $transactions++;
                $this->transactions[$txn_id] = $request;
            }
        }

        return $transactions;
    }

    public function gatherCorrectedTransactions(LogReader $reader)
    {
        $reader->rewind();

        $corrected = 0;

        foreach ($reader as $log) {
            if ($this->isErrorLine($log)) {
                continue;
            }

            $request = $this->getRequest($log);

            if (empty($request)) {
                continue;
            }

            $txn_id  = $this->getTransactionId($request);

            if (array_key_exists($txn_id, $this->transactions)) {
                $corrected++;
                $this->corrections[$txn_id] = $request;
            }
        }

        return $corrected;
    }

    public function getRequest($log)
    {
        return $log['context'][1];
    }

    public function getTransactionId(array $request)
    {
        return $request['txn_id'];
    }

    public function isErrorLine($log)
    {
        return LogLevel::ERROR === strtolower($log['level']);
    }

    /**
     * Get Missing Transactions Table
     *
     * @param \Symfony\Component\Console\Helper\TableHelper $table
     *
     * @return \Symfony\Component\Console\Helper\TableHelper
     */
    public function getMissingTable(TableHelper $table)
    {
        $missing = array_diff_key($this->transactions, $this->corrections);

        $table->setHeaders(array(
            'ID',
            'Type',
            'Payment Status',
            'Reason',
            'Order #'
        ));

        foreach ($missing as $request) {
            $table->addRow($this->getTableRowForRequest($request));
        }

        return $table;
    }

    public function getTableRowForRequest(array $request)
    {
        return array(
            $request['txn_id'],
            isset($request['txn_type']) ? $request['txn_type'] : '-',
            isset($request['payment_status']) ? $request['payment_status'] : '-',
            isset($request['reason_code']) ? $request['reason_code'] : '-',
            $request['invoice']
        );
    }
}
