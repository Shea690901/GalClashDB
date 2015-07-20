<?php
namespace GalClash {
    class GCSession extends \Tiger\Session {
        private $handler;
        private $logged_in = FALSE;

        public function __construct()
        {
            parent::__construct();
//          $this->handler = new \Tiger\SessionHandler_PDO(DB_ENGINE, DB_HOST, DB_PORT, 'GalClash', 'hgttcfvkl7gf');
//          session_set_save_handler($this->handler, true);
//          register_shutdown_function('session_write_close');
            session_name('GalClashSession');
        }

        public function __destruct()
        {
            unset($this->handler);
            parent::__destruct();
        }

        public function login($errors, $db)
        {
            foreach($errors as $error)
            {
                if(get_class($error) == '\Tiger\DB_Exception')
                {
                    $this->logged_in = FALSE;
                    $this->destroy();
                    return FALSE;
                }
                else if(get_class($error) == '\Tiger\Session_Exception')
                {
                    $this->logged_in = FALSE;
                    $this->destroy();
                    return FALSE;
                }
            }
            if(isset($_POST["logout"]))
            {
                $this->logged_in = FALSE;
                $this->destroy();
                return FALSE;
            }
                
            if(!isset($this->user))
            {
                if(!isset($_POST['user']))
                    return FALSE;
                $user      = $_POST['user'];
                $dbh       = $db->get_handle();
                try {
                    $user_info = $db->get_user_info($user);
                }
                catch(\Exception $e) {
                    printf('<pre>%s</pre>', $e->getMessage());
                    return FALSE;
                }
                if(($user_info['blocked'] == '-') && ($recrypt = $this->check_password($user_info['pwd'], $_POST["pwd"])))
                {
                    if($recrypt == 2)
                        $db->update_passwd($user, $t = password_hash($_POST['pwd'], PASSWORD_DEFAULT));
                    $this->user     = $user;
                    $this->allianz  = $user_info['allianz'];
                    $this->admin    = $user_info['admin'];
                    $this->leiter   = $user_info['leiter'];
                    $this->c_pwd    = $user_info['c_pwd'];
                }
                else
                {
                    return NULL;
                }
            }
            $this->logged_in = TRUE;
            $this->set_time(time());
            $this->export();
            return TRUE;
        }

        public function is_logged_in()
        {
            return $this->logged_in;
        }

        private function check_password($crypted, $pwd)
        {
            if($_SERVER['SERVER_ADDR'] == '127.0.0.1')
            {
                $info = password_get_info($crypted);
                if($info['algo'])
                {
                    if(password_verify($pwd, $crypted))
                        return (password_needs_rehash($crypted, PASSWORD_DEFAULT)) ? 2 : 1;
                    else
                        return 0;
                }
                else
                    return ($crypted == sha1($pwd)) ? 2 : ($crypted == '') ? 1 : 0;
            }
            else
                return ($crypted == sha1($pwd)) ? 1 : ($crypted == '') ? 1 : 0;
        }
    }
}

?>
