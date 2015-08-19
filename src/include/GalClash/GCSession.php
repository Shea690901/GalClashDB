<?php
namespace GalClash {
    class GCSession extends \Tiger\Session {
        private $handler;
        private $logged_in  = FALSE;
        private $request_ob = NULL;

        public function __construct(GCRequest $req = NULL)
        {
            parent::__construct();
//          $this->handler = new \Tiger\SessionHandler_PDO(DB_ENGINE, DB_HOST, DB_PORT, 'GalClash', 'hgttcfvkl7gf');
//          session_set_save_handler($this->handler, true);
//          register_shutdown_function('session_write_close');
            session_name('GalClashSession');
            $this->request_ob = $req;
        }

        public function __destruct()
        {
            unset($this->handler);
            parent::__destruct();
        }

        public function login($errors, $db)
        {
            if(is_null($this->request_ob))
            {
                $this->destroy();
                return FALSE;
            }
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
            if(isset($this->request_ob->logout))
            {
                $this->logged_in = FALSE;
                $this->destroy();
                return FALSE;
            }
                
            if(!isset($this->user))
            {
                if(!isset($this->request_ob->user))
                    return FALSE;
                $user      = $this->request_ob->user;
                $dbh       = $db->get_handle();
                try {
                    $user_info = $db->get_user_info($user);
                }
                catch(\Exception $e) {
                    printf('<pre>%s</pre>', $e->getMessage());
                    return FALSE;
                }
                if($user_info && ($user_info['blocked'] == '-') && ($recrypt = check_password($db, $user_info['uid'], $this->request_ob->pwd)))
                {
                    if($recrypt == 2)
                        update_passwd($uid, $this->request_ob->pwd);
                    $this->user     = $user;
                    $this->uid      = $user_info['uid'];
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

        public function is_admin()
        {
            return $this->admin;
        }

        public function is_leiter()
        {
            return $this->leiter;
        }

        public function check_password($crypted, $pwd)
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

        public function login_form()
        {
?>
            <div id="touch-screen" class="alert alert-danger">
                <h1>Touchscreen gefunden :-)</h1>
                <p>Falls diese Meldung länger als 1 Tag sichtbar ist:<br />Bitte Tiger unter Angabe des verwendeten Browsers Bescheid geben!</p>
            </div>
            <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
                <fieldset>
                    <legend>Login</legend>
                    <table>
                        <tr>
                            <td><label for="login_user">Benutzer Name:</label></td>
                            <td><input name="user" id="login_user" type="text" size="20" maxlength="20" /></td>
                        </tr>
                        <tr>
                            <td><label for="login_pwd">Passwort</label></td>
                            <td><input name="pwd" id="login_pwd" type="password" size="20" maxlength="20" /></td>
                        </tr>
                    </table>
                    <input type="submit" value="Login" />
                </fieldset>
            </form>
<?php
        }

        function logout_button()
        {
?>
            <div id="logout_b">
                <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
                    Eingeloggt als: '<?php print($this->user); ?>'
                    <input name="logout" type="hidden" value="1" />
                    <input type="submit" value="Logout" />
                </form>
            </div>
<?php
        }
    }
}

?>
