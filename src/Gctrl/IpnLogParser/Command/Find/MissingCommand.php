<?php

namespace Gctrl\IpnLogParser\Command\Find;

use Dubture\Monolog\Reader\LogReader;
use Gctrl\IpnLogParser\Command\AbstractLogCommand;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Helper\TableHelper,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;

class MissingCommand extends AbstractLogCommand
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
				new InputArgument('path', InputArgument::REQUIRED, 'The path to the log file.'),
                new InputOption('days', 'd', InputOption::VALUE_REQUIRED, 'The number of days to search.', 0),
                new InputOption('show-query', 'o', InputOption::VALUE_NONE, 'Show select query'),
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
        $days = $input->getOptions('days');

        try {
            $reader = $this->getReader($path, $days);
            $this->showMissing($reader, $output);
            if ($input->getOption('show-query')) {
                $this->showQuery($output);
            }
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
    public function showMissing(LogReader $reader, OutputInterface $output)
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
                if (!array_key_exists($txn_id, $this->corrections)) {
                    $corrected++;
                    $this->corrections[$txn_id] = $request;
                }
            }
        }

        return $corrected;
    }

    public function getRequest($log)
    {
        $request = $log['context'][1];

        $request['log_message'] = $log['message'];

        if ($log['date'] instanceof \DateTime) {
            $request['log_date'] = $log['date']->format('Y-m-d H:i:s');
        }

        return $request;
    }

    public function getTransactionId(array $request)
    {
        return $request['ipn_track_id'];
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
            'Date',
            'ID',
            'Type',
            'Payment Status',
            'Reason',
            'Order #',
            'Log Message',
        ));

        foreach ($missing as $request) {
            $table->addRow($this->getTableRowForRequest($request));
        }

        return $table;
    }

    public function getTableRowForRequest(array $request)
    {
        return array(
            isset($request['log_date']) ? $request['log_date'] : 'UNKNOWN',
            $request['txn_id'],
            isset($request['txn_type']) ? $request['txn_type'] : '-',
            isset($request['payment_status']) ? $request['payment_status'] : '-',
            isset($request['reason_code']) ? $request['reason_code'] : '-',
            $request['invoice'],
            $request['log_message']
        );
    }

    public function showQuery(OutputInterface $output)
    {
        $missing = array_diff_key($this->transactions, $this->corrections);
        $query   = "SELECT * FROM orders WHERE order_num IN ('%s')";
        $nums    = array();

        foreach ($missing as $request) {
            if (!isset($request['invoice']) || empty($request['invoice'])) {
                continue;
            }

            $nums[] = $request['invoice'];
        }

        $output->writeln(sprintf($query, implode("','", $nums)));
    }
}
