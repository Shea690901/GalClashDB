<?php
namespace Tiger {
    class Request extends Base {
        private $var_filter = NULL;

        function __construct()
        {
            parent::__construct();
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
    }
}

?>
