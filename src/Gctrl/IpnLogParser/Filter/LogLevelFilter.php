<?php

namespace Gctrl\IpnLogParser\Filter;

class LogLevelFilter extends \FilterIterator
{
    private $logLevel;

    /**
     * Constructor.
     *
     * @param \Iterator $iterator
     * @param string    $logLevel
     */
    public function __construct(\Iterator $iterator , $logLevel)
    {
        parent::__construct($iterator);

        $this->logLevel = $logLevel;
    }

    /**
     * {@inheritdoc}
     */
    public function accept()
    {
        $log = parent::current();

        $result = isset($log['level'])
                && 0 === strcasecmp($log['level'], $this->logLevel);

        return $result;
    }
}
