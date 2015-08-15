<?php
namespace Tiger {
    class Request extends Base {
        private $var_filter = NULL;

        public function __construct($init = FALSE)
        {
            parent::__construct();
            if($init)
                $thos->init();
        }

        public function init()
        {
            if($this->var_filter)
            {
                $filter = $this->var_filter;
                foreach($_REQUEST as $key => $val)
                {
                    $key = htmlspecialchars($key);
                    $val = htmlspecialchars($val);
                    if($filter($key, $val))
                        $this->$key = $val;
                }
            }
            else
            {
                foreach($_REQUEST as $key => $val)
                {
                    $key = htmlspecialchars($key);
                    $val = htmlspecialchars($val);
                    $this->$key = $val;
                }
            }
        }

        public function __destruct()
        {
            parent::__destruct();
        }

        public function set_var_filter($filter)
        {
            if(is_callable($filter))
                $this->var_filter = $filter;
            else
            {
                if(\DEBUG)
                    throw new \InvalidArgumentException(sprintf('Invalid Argument to set_var_filter expected callable, got %s', var_export($filter, TRUE)));
            }

        }
    }
}

?>
