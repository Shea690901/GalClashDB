<?php
namespace Tiger {
    class Session_Exception extends \Exception {
        const NO_ERROR            = 0;
        const SESSION_INVALID     = 1;
        const SESSION_TIMEOUT     = 10;

        public function __construct($code, $msg = NULL)
        {
            if($msg === NULL)
            {
                switch($code)
                {
                    case Session_Exception::NO_ERROR:
                        $msg = 'No error…';
                        break;
                    case Session_Exception::SESSION_INVALID:
                        $msg = 'Session is invalid!';
                        break;
                    case Session_Exception::SESSION_TIMEOUT:
                        $msg = 'Timeout!';
                        break;
                    default:
                        $msg = 'Unknown exception!';
                }
            }
            parent::__construct($msg, $code);
        }
    }

    class Session {
        private $fingerprint = NULL;
        private $timeout     = 300;         /* default timeout 5min */
        private $time;

        private $java        = FALSE;       /* default: we don't use java */

        public function __construct()
        {
            if(session_status() === PHP_SESSION_DISABLED)
                throw new Session_Exception('Sessions disabled', Session_Exception::SESSION_INVALID);
        }

        public function __destruct()
        {
        }

        public function set_timeout($timeout)
        {
            if(!is_int($timeout))
                return;
            $this->timeout = $timeout;
        }

        public function set_time($time)
        {
            if(!is_int($time))
                return;
            $this->time= $time;
        }

        public function get_time()
        {
            return $this->time;
        }

        private function gen_fingerprint()
        {
            $ctx = hash_init('sha512');
            hash_update($ctx, $_SERVER['REMOTE_ADDR']);
            hash_update($ctx, $_SERVER['HTTP_USER_AGENT']);
            return hash_final($ctx);
        }

        public function open()
        {
            session_start();
            $this->import();
            $fp = $this->gen_fingerprint();
            if($this->fingerprint === NULL)
                $this->fingerprint = $fp;
            else if($this->fingerprint != $fp)
                throw new Session_Exception(Session_Exception::SESSION_INVALID);
            else if(isset($this->time))
            {
                if((time() - $this->time) > $this->timeout)
                    throw new Session_Exception(Session_Exception::SESSION_TIMEOUT);
            }
            $this->time = time();
        }

        private function keep_vars($key)
        {
            switch($key)
            {
                case 'fingerprint':
                case 'java':
                case 'timeout':
                case 'time':
                    return TRUE;
                default:
                    return FALSE;
            }
        }

        public function destroy()
        {
            $_SESSION = array();
            if(ini_get("session.use_cookies"))
            {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            foreach($this as $key => $value)
            {
                if(!$this->keep_vars($key))
                    unset($this->$key);
            }
            $this->fingerprint = NULL;
            session_destroy();
        }

        public function is_valid()
        {
            return (session_status() === PHP_SESSION_ACTIVE);
        }

        private function import()
        {
            foreach($_SESSION as $key => $value)
            {
                $this->$key = $value;
                unset($_SESSION[$key]);
            }
            $_SESSION = array();
        }

        public function export()
        {
            foreach($_SESSION as $key => $value)
            {
                unset($_SESSION[$key]);
            }
            foreach($this as $key => $value)
            {
                $_SESSION[$key] = $value;
            }
        }

        public function use_java()
        {
            return isset($this->java) ? $this->java : FALSE;
        }

        public function enable_java()
        {
            $this->java = TRUE;
        }

        public function disable_java()
        {
            $this->java = TRUE;
        }
    }
}

?>
