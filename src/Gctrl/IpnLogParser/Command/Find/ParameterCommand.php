<?php

namespace Gctrl\IpnLogParser\Command\Find;

use Gctrl\IpnLogParser\Command\AbstractLogCommand,
    Gctrl\IpnLogParser\Filter\RequestParameterFilter;
use Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;

/**
 * ParameterCommand
 *
 * @author Michael Crumm <mike.crumm@groundctrl.com>
 */
class ParameterCommand extends AbstractLogCommand
{
    public function configure()
    {
        $this->setName('find:parameter')
                ->setDefinition(array(
                    new InputArgument('path', InputArgument::REQUIRED, 'The path to the log file.'),
                    new InputArgument('parameter', InputArgument::REQUIRED, 'The parameter key to search.'),
                    new InputOption('contents', 'c', InputOption::VALUE_REQUIRED, 'The value of the parameter.', null),
                    new InputOption('days', 'd', InputOption::VALUE_REQUIRED, 'The number of days to search.', 0)
                ))
                ->setDescription('Find requests containing the parameter');
    }
    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $path      = $input->getArgument('path');
        $days      = $input->getOption('days');
        $reader    = $this->getReader($path, $days);

        $parameter = $input->getArgument('parameter');
        $contents  = $input->getOption('contents');

        $filter = new RequestParameterFilter($reader, $parameter, $contents);
        $count  = 0;

        foreach ($filter as $record) {
            $count++;
            $output->writeln(print_r($record, true));
        }

        $output->writeln(sprintf('Found %d records matching criteria.', $count));
    }
}
