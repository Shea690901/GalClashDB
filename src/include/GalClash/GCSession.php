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
                    $this->user   = $user;
                    $this->uid    = $user_info['uid'];
                    $this->ally   = $user_info['ally'];
                    $this->admin  = $user_info['admin'];
                    $this->leader = $user_info['leader'];
                    $this->c_pwd  = $user_info['c_pwd'];
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
            return $this->is_valid() ? $this->logged_in : FALSE;
        }

        public function is_admin()
        {
            return $this->is_valid() ? $this->admin : FALSE;
        }

        public function is_leader()
        {
            return $this->is_valid() ? $this->leader : FALSE;
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
