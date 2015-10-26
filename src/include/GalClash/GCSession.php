<?php
namespace GalClash {
    class GCSession extends \Tiger\Session
    {
        private $handler;
        private $request_ob = null;
        private $error_msg = false;     // do we have to display an error?

        public function __construct(GCRequest $req = null)
        {
            parent::__construct();
//          $this->handler = new \Tiger\SessionHandler_PDO(DB_ENGINE, DB_HOST, DB_PORT, 'GalClash', 'hgttcfvkl7gf');
//          session_set_save_handler($this->handler, true);
//          register_shutdown_function('session_write_close');
            session_name('GalClashSession');
            $this->request_ob = $req;
            $logged_in = false;
            $this->add_keep(['logged_in', 'user', 'theme', 'java']);
        }

        public function __destruct()
        {
            unset($this->handler);
            parent::__destruct();
        }

        public function login($errors, $db)
        {
            if (is_null($db)) {
                $this->logged_in = false;
                unset($this->user);

                return false;
            } else {
                $this->db = $db;
            }
            if (is_null($this->request_ob)) {
                unset($this->user);

                return false;
            }
            foreach ($errors as $error) {
                if (get_class($error) == '\Tiger\DB_Exception') {
                    $this->logged_in = false;
                    unset($this->user);

                    return false;
                } elseif (get_class($error) == '\Tiger\Session_Exception') {
                    $this->logged_in = false;
                    unset($this->user);

                    return false;
                }
            }
            if (isset($this->request_ob->logout)) {
                $this->logged_in = false;
                unset($this->user);

                return false;
            }

            if (!isset($this->user)) {
                if (!isset($this->request_ob->user)) {
                    return false;
                }
                $this->user = $this->request_ob->user;
            }

            try {
                $user_info = $this->get_user_info();
            } catch (\Exception $e) {
                error_message($e->getMessage());

                return false;
            }
            if ($user_info) {
                if (!$this->is_logged_in()) {
                    if (isset($this->request_ob->pwd)) {
                        $recrypt = check_password($db, $user_info['uid'], $this->request_ob->pwd);
                    } else {
                        return null;
                    }
                    if ($recrypt == 0) {
                        return null;
                    } elseif ($recrypt == 2) {
                        update_passwd($uid, $this->request_ob->pwd);
                    }
                    if ($user_info['blocked'] != '-') {
                        return null;
                    }
                }
                $this->uid = $user_info['uid'];
                $this->pid = $user_info['pid'];
                $this->aid = $user_info['aid'];
                $this->ally = $user_info['ally'];
                $this->c_pwd = $user_info['c_pwd'];
                $this->logged_in = true;
                $this->set_time(time());
                $this->export();

                return true;
            } else {
                return null;
            }
        }

        public function logout($error = false)
        {
            $this->logged_in = false;
            $this->error_msg = $error; // if TRUE we have to display an error_message
            unset($this->user);
            $this->export();
        }

        public function is_logged_in()
        {
            return $this->is_valid() ? (isset($this->logged_in) ? $this->logged_in : false) : false;
        }

        public function is_admin()
        {
            if ($this->is_valid()) {
                $admin = $this->get_user_info()['admin'];
            } else {
                return false;
            }

            return $admin || $this->is_leader();
        }

        public function is_leader()
        {
            if ($this->is_valid()) {
                return $this->get_user_info()['leader'];
            } else {
                return false;
            }
        }

        public function login_form()
        {
            if ($this->error_msg) {
                error_message('Falsches Passwort!');
            }
            ?>
            <div id="touch-screen" class="alert alert-danger">
                <h1>Touchscreen gefunden :-)</h1>
                <p>Falls diese Meldung länger als 1 Tag sichtbar ist:<br />Bitte Tiger unter Angabe des verwendeten Browsers Bescheid geben!</p>
            </div>
            <form action="<?php print($_SERVER['PHP_SELF']);
            ?>" method="post" accept-charset="utf-8"> 
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

        public function logout_button()
        {
            ?>
            <div id="logout_b">
                <form action="<?php print($_SERVER['PHP_SELF']);
            ?>" method="post" accept-charset="utf-8"> 
                    Eingeloggt als: '<?php print($this->user);
            ?>'
                    <input name="logout" type="hidden" value="1" />
                    <input type="submit" value="Logout" />
                </form>
            </div>
<?php

        }

        private function get_user_info()
        {
            return $this->db->get_user_info($this->user);
        }
    }
}
?>
