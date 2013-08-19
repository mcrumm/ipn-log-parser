<?php

namespace Gctrl\IpnLogParser\Filter;

class RequestParameterFilter extends \FilterIterator
{
    private $parameter;
    private $value;

    /**
     * Constructor.
     *
     * @param \Iterator   $iterator
     * @param string      $parameter
     * @param string|null $value
     */
    public function __construct(\Iterator $iterator, $parameter, $value = null)
    {
        $this->parameter = $parameter;
        $this->value     = $value;

        parent::__construct($iterator);
    }

    /**
     * {@inheritdoc}
     */
    public function accept()
    {
        $log = $this->current();

        if (!isset($log['context'], $log['context'][1])) {
            return false;
        }

        $request = $log['context'][1];
        if (null === $this->value) {
            return array_key_exists($this->parameter, $request);
        }

        return array_key_exists($this->parameter, $request)
            && 0 === strcasecmp($request[$this->parameter], $this->value);
    }
}
