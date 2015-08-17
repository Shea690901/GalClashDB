<?php
namespace GalClash {
    class GCProfile {
        private $db  = NULL;
        private $req = NULL;
        private $ses = NULL;

        public function __construct(GCSession $ses, GCRequest $req, GCDB $db)
        {
            $this->db  = $db;
            $this->req = $req;
            $this->ses = $ses;
        }

        public function __destruct() {}

        public function update()
        {
            if(isset($this->req->pwd))
            {
                $opwd  = $request->opwd;
                $npwd1 = $request->npwd1;
                $npwd2 = $request->npwd2;
                if(strlen($npwd1) < 8)
                {
                    \error_message("Passwort zu kurz!");
                    return;
                }
                if($npwd1 != $npwd2)
                {
                    \error_message("Passwörter stimmen nicht überein!");
                    return;
                }
                if($opwd == $npwd1)
                {
                    \error_message("Altes und neues Passwort sind identisch!");
                    return;
                }

                try {
                    $user_info = $this->db->get_user_info($this->ses->user);
                }
                catch(\Exception $e) {
                    \error_message($e->getMessage());
                    return;
                }
                if($user_info && $this->check_password($user_info['pwd'], $opwd))
                {
                    $this->db->update_passwd($user, password_hash($this->request_ob->pwd, PASSWORD_DEFAULT));
                }
                else
                {
                    error_message("Falsches Passwort!");
                    cancel_session();
                }
                return;
            }
            if(isset($request->urlaub))
            {
                $datum = $request->datum;
                switch($datum)
                {
                    case "+":
                        $datum = "9999-12-31";
                        break;
                    case "-":
                        $datum = "0000-00-00";
                        break;
                    default:
                        if($r = sscanf($datum, "%d.%d.%d", $d, $m, $y) != 3)
                        {
                            error_message("Fehlerhaftes Datum!");
                            return;
                        }
                        if($y < 100)
                            $y += 2000;
                        $t = strtotime(sprintf("%4d-%02d-%02d", $y, $m, $d));
                        if($t === FALSE)
                        {
                            error_message("ungültiges Datum: '" . $datum . "'");
                            return;
                        }
                        $datum = date("Y-m-d", $t);
                        break;
                }
                update_urlaub($datum);
            }
        }
            //put_konto_forms($session->c_pwd);

        public function form()
        {
            if($this->ses->c_pwd)
                \warning_message("Bitte Passwort ändern");
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
                    <input type="submit" value="Ändern" /><input type="reset" value="Abbrechen" />
                    <input name="update" type="hidden" value="1" />
                    <input name="profile" type="hidden" value="1" />
                    <input name="pwd" type="hidden" value="1" />
                </fieldset>
            </form>
            <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
                <fieldset>
                    <legend>Urlaub</legend>
                    <table border="0" cellpadding="0" cellspacing="4">
                        <tr>
                            <td align="right"><label for="urlaub">in Urlaub bis:</label></td>
                            <td><input name="datum" id="urlaub" type="text" size="10" maxlength="10" value="<?php print(get_urlaub()); ?>"/></td>
                            <td>Format: dd.mm.yyyy<br />Eintrag löschen: '-'<br />unbestimmte Zeit: '+'</td>
                        </tr>
                    </table>
                    <input type="submit" value="Eintragen" /><input type="reset" value="Abbrechen" />
                    <input name="update" type="hidden" value="1" />
                    <input name="profile" type="hidden" value="1" />
                    <input name="urlaub" type="hidden" value="1" />
                </fieldset>
            </form>
            <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
                <fieldset>
                    <legend>Javascript</legend>
                    <input type="radio" name="js" value="1" />einschalten<br />
                    <input type="radio" name="js" value="0" />ausschalten<br />
                    <input type="submit" value="Eintragen" /><input type="reset" value="Abbrechen" />
                    <input name="update" type="hidden" value="1" />
                    <input name="profile" type="hidden" value="1" />
                    <input name="java" type="hidden" value="1" />
                </fieldset>
            </form>
        <?php
        }
    }
}
?>
