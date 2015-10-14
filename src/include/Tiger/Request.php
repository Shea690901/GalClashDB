<?php
namespace Tiger {
    class Request extends Base {
        private $var_filter = NULL;

        public function __construct($init = FALSE)
        {
            parent::__construct();
            if($init)
                $this->init();
        }

        public function init()
        {
            if($this->var_filter)
            {
                $filter = $this->var_filter;
                foreach($_REQUEST as $key => $val)
                {
                    $key = htmlspecialchars($key);
                    if(is_string($val))
                        $v = htmlspecialchars($val);
                    else if(is_array($val))
                    {
                        $v = [];
                        foreach($val as $a)
                            $v[] = htmlspecialchars($a);
                    }
                    if($filter($key, $v))
                        $this->$key = $v;
                }
            }
            else
            {
                foreach($_REQUEST as $key => $val)
                {
                    $key = htmlspecialchars($key);
                    if(is_string($val))
                        $v = htmlspecialchars($val);
                    else if(is_array($val))
                    {
                        $v = [];
                        foreach($val as $a)
                            $v[] = htmlspecialchars($a);
                    }
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
