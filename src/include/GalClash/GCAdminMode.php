<?php
namespace GalClash {
    class GCAdminMode extends \Tiger\Base {

        /*
        ** __constructor
        **
        ** process request and prints accordingly:
        ** - alliance overview
        ** - admin forms
        */
        public function __construct(GCRequest $request, GCSession $session, GCDB $db)
        {
            parent::__construct();

            // save for subsequent functions…
            $this->db      = $db;
            $this->request = $request;
            $this->session = $session;

            // first we need to know what changed, so we know what to display…
            // ret contains a mapping with all parameters needed later on
            $ret = $this->process_request();
            print('<pre>');
            var_dump($ret);
            print('</pre>');

            foreach($ret['forms'] as $form)
            {
                switch($form)
                {
                    case 'member':
                        $this->display_member_forms($ret);
                        break;
                    case 'allies':
                        $this->display_allies_forms($ret);
                        break;
                    default:
                        throw new \ErrorException('Unknown formular type!');
                        break;
                }
                unset($ret['overview']);
            }
        }

    //        $ret = 0;
    //        if(isset($_POST["n_name"]))
    //            $ret = namens_aenderung();
    //        if(isset($_POST["n_allianz"]))
    //            $ret = allianz_aenderung();
    //        switch($ret)
    //        {
    //            case 1:
    //                put_namen_kombinieren();
    //                break;
    //            case 2:
    //                put_allianz_kombinieren();
    //                break;
    //            default:
    //              put_admin_forms();
    //        }

        /*
        ** __destructor
        ** prints:
        */
        public function __destruct()
        {
            parent::__destruct();
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
            $session = $this->session;

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
                \GalClash\error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
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
                            if(($row->name != $session->user) && ($row->name != $db->get_ally_leader($ally)))
                                printf("<input type=\"checkbox\" name=\"names[]\" value=\"%s\">%s\n", $row->name, $row->name);
                            else
                                printf("<input type=\"checkbox\" name=\"names[]\" disabled=\"disabled\">%s\n", $row->name, $row->name);
?>
                                </td>
                                <td align="center"><?php print($row->admin == 1 ? "X" : "-"); ?></td>
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
                                <td><?php print($row->allianz); ?></td>
                                <td><?php print($row->anzahl); ?></td>
                            </tr>
<?php
                        }
                }
?>
                        </tbody>
                    </table>
                    <select name="ov_ally" id="ov_ally" size="1" />
<?php
                $ally_group = $db->get_ally_group();

                if($ally == "-")
                    print("<option>-</option>");
                else
                    print("<option selected=\"selected\">-</option>");
                foreach($ally_group as $member)
                {
                    if(($ally == "-") && ($member == $this->session->ally))
                        $fmt = "<option selected=\"selected\">%s</option>";
                    else
                        $fmt = "<option>%s</option>";
                    printf($fmt, $member);
                }
?>
                    </select>
                    <input type="submit" name="overview" value="Auswahl" />
                </fieldset>
            </div>
<?php
            }
        }

        private function display_allies_forms($args)
        {
$alliance   = trim($this->request->alliance);
$oalliance   = trim($this->request->alliance);
$nalliance   = trim($this->request->alliance);
$name   = trim($this->request->name);
$oname   = trim($this->request->name);
$nname   = trim($this->request->name);
$dbh = $this->db->get_handle();
$session = $this->session;

            $ally       = $args['ally'];
?>
    <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8" class="rcontainer "> 
<?php
            if(isset($args['overview']))
                $this->display_overview(($ally == "-" ? 0 : 1), $ally);
?>
        <fieldset>
            <legend>Neue Allianz in Gruppe</legend>
            <table border="0" cellpadding="0" cellspacing="4">
                <tr>
                    <td align="right"><label for="allianz">Allianz:</label></td>
                    <td><input name="allianz" id="allianz" type="Text" size="20" maxlength="20" value="<?php print($alliance); ?>"/></td>
                    <td align="right"><label for="name">Leiter:</label></td>
                    <td><input name="name" id="name" type="Text" size="20" maxlength="20" value="<?php print($name); ?>"/></td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                    <td align="right"><label for="pwd">Passwort:</label></td>
                    <td><input name="pwd" id="pwd" type="Text" size="20" maxlength="20" /></td>
                </tr>
            </table>
            <input type="submit" value="Eintragen" /><input type="reset" value="Abbrechen" />
            <input name="n_gruppe" type="hidden" value="1" />
            <input name="admin" type="hidden" value="1" />
        </fieldset>
    </form>
    <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
        <fieldset>
            <legend>Allianz aus Gruppe entfernen</legend>
            <table border="0" cellpadding="0" cellspacing="4">
                <tr>
                    <td><label for="allianz_ent">Allianz:</label></td>
                    <td><select name="allianz" id="allianz_ent" size="1" />
<?php
    $sth = $dbh->prepare("SELECT allianz FROM V_blacklisted");

    try {
        $sth->execute();
    }
    catch(\PDOException $e) {
        \GalClash\error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
    }
    if($sth->rowCount() > 0)
    {
        $rows = $sth->fetchAll(\PDO::FETCH_OBJ);
        foreach($rows as $row)
        {
            if($row->allianz != $session->ally)
                printf("<option>%s</option>", $row->allianz);
        }
    }
?>
                    </select></td>
                </tr>
                <tr>
                <td>&nbsp;</td>
                </tr>
            </table>
            <input type="submit" value="Löschen" /><input type="reset" value="Abbrechen" />
            <input name="l_gruppe" type="hidden" value="1" />
            <input name="admin" type="hidden" value="1" />
        </fieldset>
    </form>
    <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
        <fieldset>
            <legend>Spielername ändern</legend>
            <table border="0" cellpadding="0" cellspacing="4">
                <tr>
                    <td align="right"><label for="oname">alter Name:</label></td>
                    <td><input name="oname" id="oname" type="text" size="20" maxlength="20" value="<?php print($oname); ?>"/></td>
                </tr>
                <tr>
                    <td align="right"><label for="nname">neuer Name:</label></td>
                    <td><input name="nname" id="nname" type="text" size="20" maxlength="20" value="<?php print($nname); ?>"/></td>
                </tr>
            </table>
            <input type="submit" value="Ändern" /><input type="reset" value="Abbrechen" />
            <input name="n_name" type="hidden" value="1" />
            <input name="admin" type="hidden" value="1" />
            <input name="force" type="hidden" value="0" />
        </fieldset>
    </form>
    <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
        <fieldset>
            <legend>Allianzname ändern</legend>
            <table border="0" cellpadding="0" cellspacing="4">
                <tr>
                    <td align="right"><label for="oallianz">alter Name:</label></td>
                    <td><input name="oallianz" id="oallianz" type="text" size="20" maxlength="20" value="<?php print($oalliance); ?>"/></td>
                </tr>
                <tr>
                    <td align="right"><label for="nallianz">neuer Name:</label></td>
                    <td><input name="nallianz" id="nallianz" type="text" size="20" maxlength="20" value="<?php print($nalliance); ?>"/></td>
                </tr>
            </table>
            <input type="submit" value="Ändern" /><input type="reset" value="Abbrechen" />
            <input name="n_allianz" type="hidden" value="1" />
            <input name="admin" type="hidden" value="1" />
            <input name="force" type="hidden" value="0" />
        </fieldset>
    </form>
<?php
        }

        private function display_member_forms($args)
        {
            $session    = $this->session;
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
        <div id="admin_member_forms">
            <fieldset>
                <legend>Neues Allianzmitglied</legend>
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
                Löscht die selektierten User<br />
                Zur Sicherheit ist die weiter untenen befindliche Checkbox zu markieren!
                </p>
                <input type="submit" name="del_user" value="Löschen" />
            </fieldset>
            <fieldset>
                <legend>Allianzmitglied sperren/entsperren</legend>
                <p>Sperrt bzw. entsperrt für die selektierten User den Zugang zur Datenbank</p>
                <input type="submit" name="block_user" value="Eintragen" />
            </fieldset>
<?php
                if($session->is_leader())
                {
?>
            <fieldset>
                <legend>Adminrechte geben/löschen</legend>
                <table border="0" cellpadding="0" cellspacing="4">
                    <tr>
                        <td align="right"><label for="name">Name:</label></td>
                        <td>
                            <select name="name" id="name" size="1" />
<?php
                    printf("<option value=\"----\">Bitte auswählen!</option>\n");
                    foreach($users as $user => $val)
                    {
                        if($val['admin'] == 1)
                            $fmt = "<option value=\"-%s\">%s (Admin)</option>";
                        else
                            $fmt = "<option value=\"+%s\">%s</option>";
                        printf($fmt, $user, $user);
                    }
?>
                            </select>
                        </td>
                    </tr>
                </table>
                <input type="submit" value="Eintragen" />
                <input name="a_user" type="hidden" value="1" />
            </fieldset>
<?php
                }
?>
            <input type="checkbox" name="del_security" value="1">Wollen sie die markierten Benutzer wirklich löschen?
<?php
            }
?>
            <input name="ally" type="hidden" value="<?php print($ally); ?>" />
            <input name="admin" type="hidden" value="1" />
            <input name="state" type="hidden" value="work" />
        </form>
    </div>
<?php
        }

        private function process_request()
        {
            $req = $this->request;
            $state = trim($req->state);
            print('<pre>');
            var_dump($req);
            print('</pre>');

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

            if(isset($req->add_user))
            {
                $ally = trim($req->am_ally);
                // $this->add_member();
                $ret = array('overview' => 1, 'ally' => $ally, 'forms' => array('member', 'allies'));
            }
            else if(0)
            {
                /*
                    $this->block_member();
                    $ret['overview'] = 1;
                    $ret['ally'] = $ally;
                    break;
                case 'l_user':
                    $this->delete_member();
                    $ret['overview'] = 1;
                    $ret['ally'] = $ally;
                    break;
                default:
                    if($this->session->is_leader() || TRUE)
                    {
                        switch($state)
                        {
                            case 'a_user':
                                $this->toggle_priv_level();
                                $ret['overview'] = 1;
                                $ret['ally'] = $ally;
                                break;
                            case 'n_gruppe':
                                $this->add_alliance();
                                $ret['overview'] = 1;
                                $ret['ally'] = '-';
                                break;
                            case 'l_gruppe':
                                $this->delete_alliance();
                                $ret['overview'] = 1;
                                $ret['ally'] = '-';
                                break;
                            default:
                                // unknown request
                        }
                        break;
                    }
                    else
                    {
                        // unknown request
                    }
                    */
            }
            return $ret;
        }

        private function add_member()
        {
            $db       = $this->db;
            $dbh      = $db->get_handle();

            $name     = trim($this->request->name);
            $alliance = trim($this->request->alliance);
            $pwd      = trim($this->request->pwd);

            if(strlen($alliance) == 0)
            {
                \GalClash\error_message("Allianzzugehörigkeit unbekannt…");
                return 0;
            }
            if(strlen($name) == 0)
            {
                \GalClash\error_message("Spielername muss angegeben sein!");
                return 0;
            }
            if(($name == "-") || ($alliance == "-"))
            {
                \GalClash\error_message("'-' als Spielername/Allianz ist unzulässig!");
                return 0;
            }
            if(strlen($pwd) == 0)
                $pwd = \Tiger\gen_password();
            $c_pwd = sha1($pwd);

            $s_id = $db->get_player_id($dbh, $name);
            $a_id = $db->get_ally_id($dbh, $allianz);

            if($s_id == -1)
                $sth1 = $dbh->prepare("INSERT INTO spieler (name, a_id) VALUES ( :name, :a_id )");
            else
                $sth1 = $dbh->prepare("UPDATE spieler SET a_id = :a_id WHERE s_id = :s_id");
            $sth2 = $dbh->prepare("INSERT INTO user_pwd ( s_id, pwd ) VALUES ( :s_id, :pwd )");
            try {
                $dbh->beginTransaction();

                $sth1->bindValue(":a_id", $a_id, PDO::PARAM_INT);
                if($s_id == -1)
                {
                    $sth1->bindValue(":name", $name);
                    $sth1->execute();
                    $s_id = get_spieler_id($dbh, $name);
                }
                else
                {
                    $sth1->bindValue(":s_id", $s_id, PDO::PARAM_INT);
                    $sth1->execute();
                }
                $sth2->bindValue(":pwd", $pwd);
                $sth2->bindValue(":s_id", $s_id, PDO::PARAM_INT);
                $sth2->execute();

                $dbh->commit();
            }
            catch(PDOException $e) {
                $dbh->rollBack();
                \GalClash\error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
            }
        }

    }
}

?>
