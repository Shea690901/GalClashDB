<?php
namespace GalClash {
    use \Exception;

    class GCProfile extends GCMode {
        public function put_form()
        {
            if($this->ret['form'] == FALSE)
                return;
            $session = $this->ses;

            if($session->c_pwd)
                $this->store_warning_message("Bitte Passwort ändern");
?>
            <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
                <fieldset>
                    <legend>Passwort ändern</legend>
                    <table border="0" cellpadding="0" cellspacing="4">
                        <tr>
                            <td align="right"><label for="opwd">altes Passwort:</label></td>
                            <td><input name="opwd" id="opwd" type="password" size="20" maxlength="20" /></td>
                        </tr>
                        <tr>
                            <td align="right"><label for="npwd1">neues Passwort:</label></td>
                            <td><input name="npwd1" id="npwd1" type="password" size="20" maxlength="20" /></td>
                            <td>8 - 20 Zeichen</td>
                        </tr>
                        <tr>
                            <td align="right"><label for="npwd2">neues Passwort wiederholen:</label></td>
                            <td><input name="npwd2" id="npwd2" type="password" size="20" maxlength="20" /></td>
                        </tr>
                    </table>
                    <input type="submit" name="ch_pwd" value="Ändern" /><input type="reset" value="Eingabe löschen" />
                    <input name="profile" type="hidden" value="1" />
                    <input type="hidden" name="state" value="work" />
                </fieldset>
            </form>
            <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
                <fieldset>
                    <legend>Urlaub</legend>
                    <table border="0" cellpadding="0" cellspacing="4">
                        <tr>
                            <td align="right"><label for="urlaub">in Urlaub bis:</label></td>
                            <td><input name="date" id="urlaub" type="text" size="10" maxlength="10" value="<?php print($this->db->get_vacation($this->ses->user)); ?>"/></td>
                            <td>Format: dd.mm.yyyy<br />Eintrag löschen: '-'<br />unbestimmte Zeit: '+'</td>
                        </tr>
                    </table>
                    <input type="submit" name="vacation" value="Eintragen" />
                    <input type="hidden" name="profile" value="1" />
                    <input type="hidden" name="state" value="work" />
                </fieldset>
            </form>
            <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
                <fieldset>
                    <legend>Javascript</legend>
                    <p>Javascript wird z.B. für die schließbaren Meldungsboxen verwendet.<br />
                    Man kann diese Seite aber auch, ohne relevanten Verlust der
                    Funktionalität, komplett ohne Javascript verwenden.</p>
                    <table border="0" cellpadding="0" cellspacing="4">
                        <tr>
                            <td align="right"><label for="js">Javascript</label></td>
                            <td>
                                <input type="checkbox" id="js" name="js" value="1"
                                    <?php print(\use_javascript() ? ' checked="checked"' : ''); ?>
                                    />
                            </td>
                        </tr>
                    </table>
                    <input type="submit" name="java" value="Eintragen" />
                    <input name="profile" type="hidden" value="1" />
                    <input type="hidden" name="state" value="work" />
                </fieldset>
            </form>
        <?php
        }

        public function process_request($cookie = NULL)
        {
            if(!((gettype($cookie) == 'object') && (get_class($cookie) == 'Tiger\Cookie')))
                throw new Exception(__CLASS__ . '::' . __FJNCTION__ . ': Fehlerhafter Parameter');
            $this->ret = $this->do_it($cookie);
        }

        private function do_it($cookie)
        {
            $request = $this->req;
            $session = $this->ses;
            $db      = $this->db;
            $state   = trim($request->state);

            // normaly we later want to display the formulars
            $ret['form'] = TRUE;

            // nothing to do (yet)
            if($state == 'start')
                return $ret;

            // now begins the work

            if(isset($request->java))
            {
                if(isset($request->js))
                {
                    \enable_javascript();
                    $this->store_info_message('Die Verwendung von Javascript ist erlaubt…');
                }
                else
                {
                    \disable_javascript();
                    $this->store_info_message('Die Verwendung von Javascript ist verboten…');
                }
            }
            else if(isset($request->ch_pwd))
            {
                $opwd  = $request->opwd;
                $npwd1 = $request->npwd1;
                $npwd2 = $request->npwd2;

                if(strlen($npwd1) < 8)
                {
                    $this->store_error_message("Passwort zu kurz!");
                }
                else if($npwd1 != $npwd2)
                {
                    $this->store_error_message("Passwörter stimmen nicht überein!");
                }
                else if($opwd == $npwd1)
                {
                    $this->store_error_message("Altes und neues Passwort sind identisch!");
                }
                else
                {
                    try {
                        $user_info = $db->get_user_info($session->user);
                    }
                    catch(Exception $e) {
                        $this->store_error_message($e->getMessage());
                        return $ret;
                    }
                    if($user_info && check_password($db, ($u_id = $user_info['uid']), $opwd))
                    {
                        $db->update_passwd($u_id, password_hash($this->req->npwd1, PASSWORD_DEFAULT));
                        $this->store_success_message('Passwort erfolgreich gewechselt…');
                    }
                    else
                    {
                        $session->logout(TRUE);
                        return $ret;
                    }
                }
            }
            else if(isset($request->vacation))
            {
                $date = $request->date;
                switch($date)
                {
                    case "+":
                        $date = "9999-12-31";
                        break;
                    case "-":
                        $date = "0000-00-00";
                        break;
                    default:
                        if(sscanf($date, "%d.%d.%d", $d, $m, $y) != 3)
                        {
                            $this->store_error_message("Fehlerhaftes Datum!");
                            return $ret;
                        }
                        if($y < 100)
                            $y += 2000;
                        $t = strtotime(sprintf("%4d-%02d-%02d", $y, $m, $d));
                        if($t === FALSE)
                        {
                            $this->store_error_message("ungültiges Datum: '" . $date . "' (Falsches Format)");
                            return $ret;
                        }
                        if($t <= time())
                        {
                            $this->store_error_message("ungültiges Datum: '" . $date . "' (Datum bereits vergangen)");
                            return $ret;
                        }
                        $date = date("Y-m-d", $t);
                        break;
                }
                $db->update_vacation($session->user, $date);
                $this->store_success_message('Urlaub erfolgreich eingetragen…');
            }
            else
            {
                $this->store_error_message('Sie wünschen, MeLady?');
            }
            return $ret;
        }

        public function update()
        {
        }
            //put_konto_forms($session->c_pwd);
    }
}
?>
