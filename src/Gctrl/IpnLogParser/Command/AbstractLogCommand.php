<?php

namespace Gctrl\IpnLogParser\Command;

use Cilex\Command\Command;
use Dubture\Monolog\Reader\LogReader;

abstract class AbstractLogCommand extends Command
{
    /**
     * Get Log Reader
     *
     * @param string $file
     * @param int    $days
     * @param string $pattern
     *
     * @return \Dubture\Monolog\Reader\LogReader
     */
    public function getReader($file, $days = 30, $pattern = 'default')
    {
        return new LogReader($file, $days, $pattern);
    }
}
