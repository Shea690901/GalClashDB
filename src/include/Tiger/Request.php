<?php

namespace Tiger {
    class Request extends Base
    {
        private $var_filter;

        public function __construct($filter = null, $init = false)
        {
            parent::__construct();
            if (is_null($filter)) {
                $filter = function ($k, $v) { return true; };
            }
            $this->set_var_filter($filter);
            if ($init) {
                $this->init();
            }
        }

        public function init()
        {
            $filter = $this->var_filter;
            foreach ($_REQUEST as $key => $val) {
                $key = htmlspecialchars($key);
                if (is_string($val)) {
                    $v = htmlspecialchars($val);
                } elseif (is_array($val)) {
                    $v = [];
                    foreach ($val as $a) {
                        $v[] = htmlspecialchars($a);
                    }
                }
                if ($filter($key, $v)) {
                    $this->$key = $v;
                }
            }
        }

        public function __destruct()
        {
            parent::__destruct();
        }

        public function set_var_filter($filter)
        {
            if (is_callable($filter)) {
                $this->var_filter = $filter;
            } else {
                if (\DEBUG) {
                    throw new \InvalidArgumentException(sprintf('Invalid Argument to set_var_filter expected callable, got %s', var_export($filter, true)));
                }
            }
        }
    }
}
