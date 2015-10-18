<?php
namespace Tiger {
    class Cookie extends Base {
        private $_cookie_name;

        public function __construct($name)
        {
            parent::__construct();
            $this->_cookie_name = $name;
            if(isset($_COOKIE[$name]))
            {
                foreach($_COOKIE[$name] as $key => $val)
                    $this->$key = $val;
            }
        }

        public function __destruct()
        {
            parent::__destruct();
        }

        public function save()
        {
            if(count($this) == 0)
                setcookie($this->_cookie_name, '', time() - 1000);
            else
            {
                foreach($this as $key => $value)
                    setcookie(sprintf('%s[%s]', $this->_cookie_name, $key), $value, time() + 60 * 60 * 24 * 30);
            }
        }
    }
}
?>
