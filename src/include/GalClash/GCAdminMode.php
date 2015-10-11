<?php
namespace GalClash {
    class GCAdminMode extends \Tiger\Base {

        /*
        ** __constructor
        ** prints:
        ** - alliance overview
        ** - admin forms
        */
        public function __construct(GCRequest $request, GCSession $session, GCDB $db)
        {
            parent::__construct();
            $this->db      = $db;
            $this->request = $request;
            $this->session = $session;

            $dbh = $db->get_handle();

            $oalliance  = trim($request->oalliance);
            $nalliance  = trim($request->nalliance);
            $alliance   = trim($request->alliance);
            $all        = isset($request->all) ? trim($request->all) : '-';
            $oname      = trim($request->oname);
            $nname      = trim($request->nname);
            $name       = trim($request->name);

            $this->display_overview(($all == "-" ? 0 : 1), $all);

            $ally_group = $db->get_ally_group();
?>
    <table>
        <tr>
            <td>
                <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
                    <fieldset>
                        <legend>Neues Allianzmitglied</legend>
                        <table border="0" cellpadding="0" cellspacing="4">
                            <tr>
                                <td align="right"><label for="name">Name:</label></td>
                                <td><input name="name" id="name" type="Text" size="20" maxlength="20" /></td>
                                <td align="right"><label for="allianz">Allianz:</label></td>
                                <td>
                                    <select name="allianz" id="allianz" size="1" />
<?php
            foreach($ally_group as $member)
            {
                if($member == $session->allianz)
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
                                <td align="right"><label for="pwd">Passwort:</label></td>
                                <td><input name="pwd" id="pwd" type="Text" size="20" maxlength="20" /></td>
                            </tr>
                        </table>
                        <input type="submit" value="Eintragen" /><input type="reset" value="Abbrechen" />
                        <input name="n_user" type="hidden" value="1" />
                        <input name="admin" type="hidden" value="1" />
                    </fieldset>
                </form>
            </td>
            <td>
                <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
                    <fieldset>
                        <legend>Allianzmitglied löschen</legend>
                        <table border="0" cellpadding="0" cellspacing="4">
                            <tr>
                                <td align="right"><label for="name">Name:</label></td>
                                <td>
                                    <select name="name" id="name" size="1" />
<?php
        $sth = $dbh->prepare("SELECT name, blocked FROM V_user WHERE a_id = " .
                "( SELECT spieler.a_id FROM spieler WHERE name = :name ) " .
                "AND name != :name " .
                "AND name != ( SELECT spieler.name FROM ( spieler JOIN allianzen on leiter_id = spieler.s_id ) WHERE spieler.a_id = ( SELECT spieler.a_id FROM spieler WHERE name = :name ) )" .
                "ORDER BY name");

        try {
            $sth->bindValue(":name", $_SESSION["user"]);
            $sth->execute();
        }
        catch(\PDOException $e) {
            \GalClash\error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        }
        if($sth->rowCount() > 0)
        {
            $rows = $sth->fetchAll(\PDO::FETCH_OBJ);
            printf("<option value=\"----\">Bitte auswählen!</option>");
            foreach($rows as $row)
            {
                if($row->blocked != "-")
                    $fmt = "<option value=\"%s\">%s (gesperrt)</option>";
                else
                    $fmt = "<option value=\"%s\">%s</option>";
                printf($fmt, $row->name, $row->name);
            }
        }
?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <input type="submit" value="Löschen" /><input type="reset" value="Abbrechen" />
                        <input name="l_user" type="hidden" value="1" />
                        <input name="admin" type="hidden" value="1" />
                        <input name="all" type="hidden" value="<?php print($_SESSION["allianz"]); ?>" />
                    </fieldset>
                </form>
            </td>
        </tr>
        <tr>
            <td>
                <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
                    <fieldset>
                        <legend>Allianzmitglied sperren/entsperren</legend>
                        <table border="0" cellpadding="0" cellspacing="4">
                            <tr>
                                <td align="right"><label for="name">Name:</label></td>
                                <td>
                                    <select name="name" id="name" size="1" />
<?php
        $sth = $dbh->prepare("SELECT name, blocked FROM V_user WHERE blocked = :name OR a_id = " .
                "( SELECT spieler.a_id FROM spieler WHERE name = :name ) " .
                "AND name != :name " .
                "AND name != ( SELECT spieler.name FROM ( spieler JOIN allianzen on leiter_id = spieler.s_id ) WHERE spieler.a_id = ( SELECT spieler.a_id FROM spieler WHERE name = :name ) )" .
                "ORDER BY name");

        try {
            $sth->bindValue(":name", $session->user);
            $sth->execute();
        }
        catch(\PDOException $e) {
            \GalClash\error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        }
        if($sth->rowCount() > 0)
        {
            $rows = $sth->fetchAll(\PDO::FETCH_OBJ);
            printf("<option value=\"----\">Bitte auswählen!</option>");
            foreach($rows as $row)
            {
                if($row->blocked != "-")
                    $fmt = "<option value=\"-%s\">%s (gesperrt)</option>";
                else
                    $fmt = "<option value=\"+%s\">%s</option>";
                printf($fmt, $row->name, $row->name);
            }
        }
?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <input type="submit" value="Eintragen" /><input type="reset" value="Abbrechen" />
                        <input name="b_user" type="hidden" value="1" />
                        <input name="admin" type="hidden" value="1" />
                        <input name="all" type="hidden" value="<?php print($session->allianz); ?>" />
                    </fieldset>
                </form>
            </td>
<?php
    if($session->is_leiter() || TRUE)
    {
?>
            <td>
                <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
                    <fieldset>
                        <legend>Adminrechte geben/löschen</legend>
                        <table border="0" cellpadding="0" cellspacing="4">
                            <tr>
                                <td align="right"><label for="name">Name:</label></td>
                                <td>
                                    <select name="name" id="name" size="1" />
<?php
        $sth = $dbh->prepare("SELECT name, admin FROM V_user WHERE a_id = " .
                "( SELECT spieler.a_id FROM ( spieler JOIN allianzen ON leiter_id = spieler.s_id ) WHERE name = :name ) " .
                "AND name != :name");

        try {
            $sth->bindValue(":name", $session->user);
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
                if($row->admin == 1)
                    $fmt = "<option value=\"-%s\">%s (Admin)</option>";
                else
                    $fmt = "<option value=\"+%s\">%s</option>";
                printf($fmt, $row->name, $row->name);
            }
        }
?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <input type="submit" value="Eintragen" /><input type="reset" value="Abbrechen" />
                        <input name="a_user" type="hidden" value="1" />
                        <input name="admin" type="hidden" value="1" />
                        <input name="all" type="hidden" value="<?php print($session->allianz); ?>" />
                    </fieldset>
                </form>
            </td>

    <table border="0" cellpadding="0" cellspacing="4">
        <tr>
            <td>
                <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
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
            </td>
            <td>
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
            if($row->allianz != $session->allianz)
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
            </td>
        </tr>
    </table>
    <table border="0" cellpadding="0" cellspacing="4">
        <tr>
            <td>
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
            </td>
            <td>
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
            </td>
        </tr>
    </table>
<?php
            }
        }

    //        $ret = 0;
    //        if(isset($_POST["n_user"]))
    //            neues_mitglied();
    //        if(isset($_POST["b_user"]))
    //            sperre_mitglied();
    //        if(isset($_POST["l_user"]))
    //            loesche_mitglied();
    //        if(isset($_POST["a_user"]))
    //            admin_mitglied();
    //        if(isset($_POST["n_gruppe"]))
    //            neue_allianz();
    //        if(isset($_POST["l_gruppe"]))
    //            entferne_allianz();
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

        private function display_overview($ansicht, $alliance)
        {
            $db  = $this->db;
            $dbh = $db->get_handle();

?>
            <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
                <fieldset>
                    <legend><?php print(($alliance == "-" ? "Gruppen" : "Allianz") . "übersicht" .
                            ($alliance == "-" ? "" : " für '" . $alliance . "'")); ?></legend>
<?php
            switch($ansicht)
            {
                case 1:     $sth = $dbh->prepare("SELECT name, admin, urlaub, blocked " .
                                    "FROM V_user NATURAL JOIN allianzen WHERE allianz = :allianz " .
                                    "ORDER BY name");
                            $sth->bindValue(":allianz", $alliance);
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
                switch($ansicht)
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
                                <td><?php print($row->name); ?></td>
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
                    <select name="all" id="all" size="1" />
<?php
                $ally_group = $db->get_ally_group();

                if($alliance == "-")
                    print("<option>-</option>");
                else
                    print("<option selected=\"selected\">-</option>");
                foreach($ally_group as $member)
                {
                    if(($alliance == "-") && ($member == $this->session->allianz))
                        $fmt = "<option selected=\"selected\">%s</option>";
                    else
                        $fmt = "<option>%s</option>";
                    printf($fmt, $member);
                }
?>
                    </select>
                    <input type="submit" value="Auswahl" />
                    <input name="admin" type="hidden" value="1" />
                </fieldset>
            </form>
<?php
            }
        }

    }
}

?>
