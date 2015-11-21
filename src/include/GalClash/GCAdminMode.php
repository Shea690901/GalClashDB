<?php
namespace GalClash {
    use \Exception;

    class GCAdminMode extends GCMode {
        /*
        ** depending on result of process_request print:
        ** - alliance overview
        ** - admin forms
        */
        public function put_form()
        {
            $ret = $this->ret;

            if(isset($ret['forms']))
            {
                foreach($ret['forms'] as $form)
                {
                    switch($form)
                    {
                        case 'member':          // all changes pertaining users
                            $this->display_member_forms($ret);
                            unset($ret['overview']);
                            break;
                        case 'allies':          // all changes pertaining allies
                            $this->display_allies_forms($ret);
                            unset($ret['overview']);
                            break;
                        case 'leader':          // security check for leader change
                            $this->display_leader_form($ret['ally'], $ret['name']);
                            break;
                        default:
                            throw new \ErrorException('Unknown formular type!');
                            break;
                    }
                }
            }
        }

        /*
        ** displays overview for ally group owning the db
        **
        ** - view
        **      0: display group members and user count for each member
        **      1: display detail for one member
        ** - ally
        **      member to display detail for
        */
        private function display_overview($view, $ally)
        {
            $db      = $this->db;
            $dbh     = $db->get_handle();
            $session = $this->ses;

?>
            <div id="admin_ally_overview">
                <fieldset>
                    <legend><?php print(($ally == "-" ? "Gruppen" : "Allianz") . "übersicht" .
                            ($ally == "-" ? "" : " für '" . $ally . "'")); ?></legend>
<?php
            switch($view)
            {
                case 1:     $sth = $dbh->prepare("SELECT name, admin, urlaub, blocked " .
                                    "FROM V_user NATURAL JOIN allianzen WHERE allianz = :allianz " .
                                    "ORDER BY name");
                            $sth->bindValue(":allianz", $ally);
                            break;
                default:    $sth = $dbh->prepare("SELECT allianz, COUNT(1) AS anzahl FROM V_user NATURAL JOIN allianzen " .
                                    "GROUP BY allianz ORDER BY COUNT(1) DESC, allianz");
            }
            try {
                $sth->execute();
            }
            catch(\PDOException $e) {
                $this->store_error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
                return;
            }
            $result = $sth->fetchAll(\PDO::FETCH_OBJ);
            if(!isset($result))
                return;
            else
            {
?>
                    <table border="1" rules="all">
                        <thead>
                            <tr>
<?php
                switch($view)
                {
                    case 1:
?>
                                <th>Name</th>
                                <th>Admin</th>
                                <th>Urlaub</th>
                                <th>gesperrt</th>
                            </tr>
                        </thead>
                        <tbody>
<?php
                        foreach($result as $row)
                        {
?>
                            <tr>
                                <td>
<?php
                            if(($row->name != $session->user) && ($row->name != ($leader = $db->get_ally_leader($ally))))
                                printf("<input type=\"checkbox\" name=\"names[]\" value=\"%s\">%s\n", $row->name, $row->name);
                            else
                                printf("<input type=\"checkbox\" name=\"names[]\" disabled=\"disabled\">%s\n", $row->name, $row->name);
?>
                                </td>
                                <td align="center"><?php print($row->name == $leader ? "L" : 
                                        ($row->admin == 1 ? 'X' : "-")); ?></td>
                                <td align="center"><?php $d= $row->urlaub; print($d == "0000-00-00" ?
                                        "-" :
                                        ($d == "9999-12-31" ? "unbegrenzt" : date("d.m.Y", strtotime($d)))); ?></td>
                                <td align="center"><?php print($row->blocked); ?></td>
                            </tr>
<?php
                        }
                        break;
                    default:
?>
                                <th>Allianz</th>
                                <th>gemeldete<br />Mitglieder</th>
                            </tr>
                        </thead>
                        <tbody>
<?php
                        foreach($result as $row)
                        {
?>
                            <tr>
                                <td>
<?php
                            if($row->allianz != $session->ally)
                                printf("<input type=\"radio\" name=\"names[]\" value=\"%s\">%s\n", $row->allianz, $row->allianz);
                            else
                                printf("<input type=\"radio\" name=\"names[]\" disabled=\"disabled\">%s\n", $row->allianz, $row->allianz);
?>
                                    </td>
                                <td><?php print($row->anzahl); ?></td>
                            </tr>
<?php
                        }
                }
?>
                        </tbody>
                    </table>
                    Zeige Allianz:
                    <select name="ov_ally" id="ov_ally" size="1" />
<?php
                $ally_group = $db->get_ally_group();

                if($ally == "-")
                    print("<option>-</option>");
                else
                    print("<option selected=\"selected\">-</option>");
                foreach($ally_group as $member)
                {
                    if(($ally == "-") && ($member == $this->ses->ally))
                        $fmt = "<option selected=\"selected\">%s</option>";
                    else
                        $fmt = "<option>%s</option>";
                    printf($fmt, $member);
                }
?>
                    </select>
                    <input type="submit" name="overview" value="Auswahl" />
                    <input type="hidden" name="d_ally" value="<?php print($ally); ?>" />
                </fieldset>
            </div>
<?php
            }
        }

        private function display_allies_forms($args)
        {
            $ally       = $args['ally'];
            $ally_group = $this->db->get_ally_group();
?>
    <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8" class="rcontainer "> 
<?php
            if(isset($args['overview']))
                $this->display_overview(($ally == "-" ? 0 : 1), $ally);
            if($this->ses->is_leader())
            {
?>
        <div id="admin_allies_forms"<?php print($ally == '-' ? '' : ' style="width:100%"'); ?>>
            <fieldset>
                <legend>Neue Allianz in Gruppe</legend>
                <p>Fügt eine neue Allianz den zugriffsberechtigten Allianzen hinzu.<br />Die Angabe des Leiters ist zwingend!</p>
                <table border="0" cellpadding="0" cellspacing="4">
                    <tr>
                        <td align="right"><label for="n_ally">Allianz:</label></td>
                        <td><input type="text" name="n_ally" id="n_ally" size="20" maxlength="20" /></td>
                    </tr>
                    <tr>
                        <td align="right"><label for="n_leader">Leiter:</label></td>
                        <td><input type="text" name="n_leader" id="n_leader" size="20" maxlength="20" /></td>
                    </tr>
                    <tr>
                        <td align="right"><label for="pwd">Passwort:</label></td>
                        <td><input name="pwd" id="pwd" type="Text" size="20" maxlength="20" /></td>
                    </tr>
                </table>
                <input type="submit" name="add_ally" value="Eintragen" />
            </fieldset>
<?php
                if($ally == '-')
                {
?>
            <fieldset>
                <legend>Allianz aus Gruppe entfernen</legend>
                <p>Löscht die markierte Allianz aus der Gruppe der zugriffsberechtigten Allianzen.<br />
                Die Angabe des Leiters dient der Sicherheitsabfrage und ist zwingend (man achte auf die Schreibweise)!</p>
                <table border="0" cellpadding="0" cellspacing="4">
                    <tr>
                        <td align="right"><label for="name">Leiter:</label></td>
                        <td><input name="name" id="name" type="Text" size="20" maxlength="20" /></td>
                    </tr>
                </table>
                <input type="submit" name ="del_ally" value="Löschen" />
            </fieldset>
<?php
                }
?>
            <input type="hidden" name="ally" value="<?php print($ally); ?>" />
            <input type="hidden" name="admin" value="1" />
            <input type="hidden" name="state" value="work" />
        </div>
    </form>
<?php
            }
        }

        private function display_member_forms($args)
        {
            $session    = $this->ses;
            $db         = $this->db;

            $ally       = $args['ally'];

            $ally_group = $db->get_ally_group();
            $users = $db->get_ally_users($ally);
?>
    <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8" class="rcontainer "> 
<?php
            if(isset($args['overview']))
                $this->display_overview(($ally == "-" ? 0 : 1), $ally);
?>
        <div id="admin_member_forms"<?php print($ally != '-' ? '' : ' style="width:100%"'); ?>>
            <fieldset>
                <legend>Neues Allianzmitglied</legend>
<?php
            if($ally != '-')
            {
?>
                <p>
                Kann auch benutzt werden um nebenstehend ausgewählte User einer neuen Allianz zuzuweisen!<br />
                Oder, mit nur einem ausgewählten User unter Beibehaltung der Allianzmitgliedschaft, um ein neues Passwort zu vergeben.
                </p>
<?php
            }
?>
                <table border="0" cellpadding="0" cellspacing="4">
                    <tr>
                        <td align="right"><label for="add_member_ally">Allianz:</label></td>
                        <td>
                            <select name="am_ally" id="add_member_ally" size="1" />
<?php
            foreach($ally_group as $member)
            {
                if($member == ($ally != '-' ? $ally : $session->ally))
                    $fmt = "<option selected=\"selected\">%s</option>";
                else
                    $fmt = "<option>%s</option>";
                printf($fmt, $member);
            }
?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td align="right"><label for="add_member_name">Name:</label></td>
                        <td><input name="name" id="add_member_name" type="Text" size="20" maxlength="20" /></td>
                    </tr>
                    <tr>
                        <td align="right"><label for="pwd">Passwort:</label></td>
                        <td><input name="pwd" id="pwd" type="Text" size="20" maxlength="20" /></td>
                    </tr>
                </table>
                <input type="submit" name="add_user" value="Eintragen" />
            </fieldset>
<?php
            if($ally != '-')
            {
?>
            <fieldset>
                <legend>Allianzmitglied löschen</legend>
                <p>
                Löscht die selektierten User.<br />
                Zur Sicherheit ist die weiter unten befindliche Sicherheitsabfrage zu bejahen!
                </p>
                <input type="submit" name="del_user" value="Löschen" />
            </fieldset>
            <fieldset>
                <legend>Allianzmitglied sperren/entsperren</legend>
                <p>Sperrt bzw. entsperrt für die selektierten User den Zugang zur Datenbank.</p>
                <input type="submit" name="block_user" value="Eintragen" />
            </fieldset>
<?php
                if($session->is_leader())
                {
?>
            <fieldset>
                <legend>Adminrechte geben/löschen</legend>
                <p>Gestattet bzw. verbietet für die selektierten User den Adminzugang zur Datenbank.</p>
                <input type="submit" name="admin_user" value="Eintragen" />
            </fieldset>
            <fieldset>
                <legend>Neuer Allianzleiter</legend>
                <p>Trägt trägt den ausgewählten User als neuen Leiter für "<?php print($ally); ?>" ein.</p>
                <input type="submit" name="new_leader" value="Eintragen" />
            </fieldset>
<?php
                }
?>
            <input type="checkbox" name="del_security" value="1">Wollen sie die markierten Benutzer wirklich löschen?
<?php
            }
?>
            <input type="hidden" name="ally" value="<?php print($ally); ?>" />
            <input type="hidden" name="admin" value="1" />
            <input type="hidden" name="state" value="work" />
        </div>
    </form>
<?php
        }

        private function display_leader_form($ally, $name)
        {
?>
    <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8" class="rcontainer "> 
        <fieldset>
            <legend>Neuer Allianzleiter</legend>
            <p>Sicherheitsabfrage!</p>
            <p>Diese Änderung läßt sich ohne Mithilfe des neuen Leiters nicht rückgängig machen!</p>
            <p>Soll "<?php print($name); ?>" wirklich als neuer Leiter für die Allianz "<?php print($ally); ?>" eingetragen werden?</p>
            <input type="radio" name="force" value="1" />Ja<br />
            <input type="radio" name="force" value="0" checked="checked" />Nein<br />
            <input type="submit" name="new_leader" value="Eintragen" />
            <input type="hidden" name="ally" value="<?php print($ally); ?>" />
            <input type="hidden" name="names[]" value="<?php print($name); ?>" />
            <input type="hidden" name="admin" value="1" />
            <input type="hidden" name="state" value="work" />
        </fieldset>
    </form>
<?php
        }

        public function process_request($arg = NULL)
        {
            $this->ret = $this->do_it();
        }

        private function do_it()
        {
            $req   = $this->req;
            $ses   = $this->ses;
            $state = trim($req->state);

            // nothing really to process…
            if(isset($req->overview))
            {
                if(($ally = trim($req->ov_ally)) == '-')
                    $forms = array('allies', 'member');
                else
                    $forms = array('member', 'allies');
                return array('overview' => 1, 'ally' => $ally, 'forms' => $forms);
            }
            else if($state == 'start')
                return array('overview' => 1, 'ally' => '-', 'forms' => array('allies', 'member'));

            // now begins the work

            $ret = array('overview' => 1, 'ally' => $req->ally);
            $ret['forms'] = array('member', 'allies');
            if(isset($req->add_user))
            {
                $ally = trim($req->am_ally);
                $ret['ally'] = $ally;
                $ret['forms'] = array('member', 'allies');
                $pwd = trim($req->pwd);
                if(($req->ally != '-') && (isset($req->names)))
                {
                    if((sizeof($req->names) == 1) && ($req->d_ally == $req->ally))
                        $this->set_pwd($req->names[0], $pwd);
                    else
                        $this->move_users($ally, $req->names);
                }
                else
                {
                    $name = trim($req->name);
                    $this->add_user($ally, $name, $pwd);
                }
            }
            else if(isset($req->del_user))
            {
                if(!isset($req->names))
                    $this->store_error_message("Keine zu löschenden User selektiert");
                else if(!isset($req->del_security))
                    $this->store_error_message("Sicherheitsabfrage nicht bestätigt");
                else
                {
                    foreach($req->names as $name)
                        $this->del_user(trim($name));
                }
                $ret['ally'] = $req->ally;
                $ret['forms'] = array('member', 'allies');
            }
            else if(isset($req->block_user))
            {
                if(!isset($req->names))
                    $this->store_error_message("Keine zu ändernden User selektiert");
                else
                {
                    foreach($req->names as $name)
                        $this->block_user(trim($name));
                }
                $ret['ally'] = $req->ally;
                $ret['forms'] = array('member', 'allies');
            }
            else if($ses->is_leader())
            {
                if(isset($req->admin_user))
                {
                    if(!isset($req->names))
                        $this->store_error_message("Keine zu ändernden User selektiert");
                    else
                    {
                        foreach($req->names as $name)
                            $this->admin_user(trim($name));
                    }
                    $ret['ally'] = $req->ally;
                    $ret['forms'] = array('member', 'allies');
                }
                else if(isset($req->add_ally))
                {
                    $ally = trim($req->n_ally);
                    $name = trim($req->n_leader);
                    $pwd  = trim($req->pwd);
                    if($this->add_ally($ally, $name, $pwd) == 1)
                    {
                        $ret['ally'] = '-';
                        $ret['forms'] = array('allies', 'member');
                    }
                    else
                    {
                        $ret['ally'] = $ally;
                        $ret['forms'] = array('member', 'allies');
                    }
                }
                else if(isset($req->del_ally))
                {
                    if(!isset($req->names))
                        $this->store_error_message("Keine zu löschende Allianz selektiert");
                    else
                    {
                        $ally = trim($req->names[0]);
                        $name = trim($req->name);
                        if($name == $this->db->get_ally_leader($ally))
                            $this->del_ally($ally);
                        else
                            $this->store_error_message("Allianzleiter nicht angegeben oder falsch");
                    }
                    $ret['ally'] = '-';
                    $ret['forms'] = array('allies', 'member');
                }
                else if(isset($req->new_leader))
                {
                    if((!isset($req->names)) || (count($req->names) != 1))
                        $this->store_error_message("Es muß genau ein User (der neue Allianzleiter) ausgewählt sein");
                    else if(isset($req->force))
                    {
                        if($req->force == "1")
                            $this->new_leader($req->ally, $req->names[0], TRUE);
                        else
                            $this->store_info_message('Operation abgebrochen…');
                        $ret['ally'] = $req->ally;
                        $ret['forms'] = array('member', 'allies');
                    }
                    else
                    {
                        $ret['forms'] = array('leader');
                        $ret['ally']  = $req->ally;
                        $ret['name']  = $req->names[0];
                    }
                }
                else
                {
                    $this->store_error_message('Sie wünschen, MeLady?');
                }
            }
            else
            {
                $this->store_error_message('Sie wünschen, MeLady?');
            }
            return $ret;
        }

        private function add_ally($ally, $name, $pwd)
        {
            if(strlen($ally) == 0)
            {
                $this->store_error_message("Allianzname muss angegeben sein!");
                return 1;
            }
            if(strlen($name) == 0)
            {
                $this->store_error_message("Leitername muss angegeben sein!");
                return 1;
            }
            if(($ally == '-') || ($name == "-"))
            {
                $this->store_error_message("'-' als Allianz- oder Leitername ist unzulässig!");
                return 1;
            }
            if(strlen($pwd) == 0)
                $pwd = \Tiger\gen_password();
            $c_pwd = password_hash($pwd, PASSWORD_DEFAULT);
            try {
                $ret = $this->db->add_ally($ally, $name, $c_pwd);
                $this->store_success_message($name . " erfolgreich eingetragen…");
                if($ret == -1)
                    $this->store_message(sprintf("Initiales Passwort für %s lautet:<br />%s", $name,  $pwd), 'warning');
                else
                    $this->store_info_message('Altes Passwort wurde beibehalten…');
            }
            catch(Exception $e) {
                $this->store_error_message($e->getMessage());
                return 1;
            }
            return 0;
        }

        private function del_ally($ally)
        {
            try {
                $ret = $this->db->del_ally($ally);
                $this->store_success_message(sprintf("%s erfolgreich ausgetragen.\nzusätzlich wurde%s %d Mitglied%s gesperrt",
                            $ally,
                            $ret >1 ? "n" : "", $ret, $ret > 1 ? "er" : ""));
            }
            catch(Exception $e)
            {
                $this->store_error_message($e->getMessage());
            }
        }

        private function move_users($ally, $names)
        {
            $db   = $this->db;
            if(!$db->ally_has_access($ally))
            {
                $this->store_error_message('Ungültige Zielallianz');
                return;
            }
            foreach($names as $name)
            {
                if(!$db->player_has_access($name))
                    $this->store_error_message(sprintf('Ungültiger Username: %s', $name));
                else
                {
                    try {
                        $this->db->add_user($ally, $name, '---');
                        $this->store_success_message($name . " erfolgreich eingetragen…");
                    }
                    catch(Exception $e) {
                        $this->store_error_message($e->getMessage());
                    }
                }
            }
        }

        private function add_user($ally, $name, $pwd)
        {
            if(strlen($name) == 0)
            {
                $this->store_error_message("Spielername muss angegeben sein!");
                return;
            }
            if($name == "-")
            {
                $this->store_error_message("'-' als Spielername ist unzulässig!");
                return;
            }
            if(strlen($pwd) == 0)
                $pwd = \Tiger\gen_password();
            $c_pwd = password_hash($pwd, PASSWORD_DEFAULT);
            try {
                $ret = $this->db->add_user($ally, $name, $c_pwd);
                $this->store_success_message($name . " erfolgreich eingetragen…");
                if($ret == -1)
                    $this->store_message(sprintf('Initiales Passwort für %s lautet:<br />%s', $name, $pwd), 'info');
                else
                    $this->store_info_message('Altes Passwort wurde beibehalten…');
            }
            catch(Exception $e) {
                $this->store_error_message($e->getMessage());
            }
        }

        private function set_pwd($name, $pwd)
        {
            if(strlen($pwd) == 0)
                $pwd = \Tiger\gen_password();
            $c_pwd = password_hash($pwd, PASSWORD_DEFAULT);
            try {
                $uid = $this->db->get_user_id($name);
                if($uid != -1)
                {
                    $this->db->update_passwd($uid, $c_pwd, TRUE);
                    $this->store_message(sprintf('Neues Passwort für %s lautet:<br />%s', $name, $pwd), 'info');
                }
                else
                    $this->store_error_message(sprintf('User %s nicht gefunden!', $name));
            }
            catch(Exception $e) {
                $this->store_error_message($e->getMessage());
            }
        }

        private function del_user($name)
        {
            $db = $this->db;

            if(strlen($name) == 0)
            {
                $this->store_error_message("Spielername muss angegeben sein!");
                return;
            }
            if($name == "-")
            {
                $this->store_error_message("'-' als Spielername ist unzulässig!");
                return;
            }
            try {
                $db->del_user($name);
                $this->store_success_message($name . " erfolgreich gelöscht…");
            }
            catch(Exception $e) {
                $this->store_error_message(sprintf("%s:\n%s", $name, $e->getMessage()));
            }
        }

        private function block_user($name)
        {
            if(strlen($name) == 0)
            {
                $this->store_error_message("Spielername muss angegeben sein!");
                return;
            }
            if($name == "-")
            {
                $this->store_error_message("'-' als Spielername ist unzulässig!");
                return;
            }
            try {
                $this->db->block_user($name, $this->ses->pid);
                $this->store_success_message($name . " erfolgreich geändert…");
            }
            catch(Exception $e) {
                $this->store_error_message(sprintf("%s:\n%s", $name, $e->getMessage()));
            }
        }

        private function admin_user($name)
        {
            if(strlen($name) == 0)
            {
                $this->store_error_message("Spielername muss angegeben sein!");
                return;
            }
            if($name == "-")
            {
                $this->store_error_message("'-' als Spielername ist unzulässig!");
                return;
            }
            try {
                $this->db->admin_user($name);
                $this->store_success_message($name . " erfolgreich geändert…");
            }
            catch(Exception $e) {
                $this->store_error_message(sprintf("%s:\n%s", $name, $e->getMessage()));
            }
        }

        private function new_leader($ally, $name, $force = FALSE)
        {
            if($force)
            {
                try {
                    $this->db->change_leader($ally, $name);
                    $this->store_success_message($name . " erfolgreich als neuer Leiter eingetragen…");
                }
                catch(Exception $e) {
                    $this->store_error_message(sprintf("%s:\n%s", $name, $e->getMessage()));
                }
            }
        }
    } // class GCAdminMode
} // namespace GalClash
?>
