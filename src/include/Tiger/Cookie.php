<?php

namespace Tiger {
    class Cookie extends Base
    {
        private $_cookie_name;

        public function __construct($name)
        {
            parent::__construct();

            if (!is_string($name) || (strlen($name) == 0)) {
                throw new Exception(__CLASS__.'::'.__FUNCTION__.': Parameterfehler');
            }
            $this->_cookie_name = $name;
            $this->_cookies_allowed = false;

            if (isset($_COOKIE[$name])) {
                foreach ($_COOKIE[$name] as $key => $val) {
                    $this->$key = $val;
                }
            }
        }

        public function __destruct()
        {
            $this->save();
            parent::__destruct();
        }

        public function allow()
        {
            $this->_cookies_allowed = true;
        }

        public function disallow()
        {
            $this->_cookies_allowed = false;
        }

        public function is_allowed()
        {
            return $this->_cookies_allowed;
        }

        public function set_key($key, $val)
        {
            $this->$key = $val;
        }

        public function unset_key($key)
        {
            unset($this->$key);
            setcookie(sprintf('%s[%s]', $this->_cookie_name, $key), '', time() - 1000);
        }

        public function get_key($key)
        {
            return isset($this->$key) ? $this->$key : null;
        }

        public function save()
        {
            if (!$this->is_allowed() || (count($this) == 0)) {
                setcookie($this->_cookie_name, '', time() - 1000);
            } else {
                foreach ($this as $key => $value) {
                    if (!$this->_cookies_allowed || !isset($value) || is_null($value)) {
                        setcookie(sprintf('%s[%s]', $this->_cookie_name, $key), '', time() - 1000);
                    } else {
                        setcookie(sprintf('%s[%s]', $this->_cookie_name, $key), $value, time() + 60 * 60 * 24 * 30);
                    }
                }
            }
        }
    }
}
