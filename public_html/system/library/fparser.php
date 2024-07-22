<?php

class Fparser
{
    private Log $log;
    private \fparser\fparserinterface $fparser;

    public function __construct(Registry $registry)
    {
        $this->log = $registry->get('log');
    }

    public function load($file_path, $handler)
    {
        $class = 'fparser\\' . $handler;

        $this->fparser = $this->initFparser(new $class($file_path));
    }

    private function initFparser(\fparser\fparserinterface $fparser)
    {
        try {
            return $fparser;
        } catch (\Exception $e) {
            $this->log->write($e->getMessage() . ' Error Code : ' . $e->getCode());
            return null;
        }
    }

    public function get($option = '')
    {
        return $this->fparser->get($option);
    }
}