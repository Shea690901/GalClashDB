<?php
namespace {
/*
   Das CSS ist aufgeteilt auf mehrere Dateien:
   - default.css   : Standardeinstellungen für alle Geräte
   - {theme}.css   : Farb- und teilweise Positionseinstellungen
*/

    /*
    ** load configuration
    */
    require_once 'config.php';

    $_VERSION = "3.0.0α1";

    /*
    ** Some simple message outputs
    */
    if(DEBUG)
    {
        function debug_output()
        {
            global $session;

            print('<div class="alert alert-info">');
            if(isset($session) && $session->use_java())
                printf('<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>');
            printf("<pre>'_COOKIE = ");
            var_dump($_COOKIE);
            foreach($GLOBALS as $key => $value)
            {
                if(($key != 'GLOBALS') && (strpos($key, '_') !== 0))
                {
                    printf("%s = ", $key);
                    var_dump($value);
                }
            }
            print('</pre>');
            if(isset($session) && $session->use_java())
                printf('<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>');
            printf("</div>\n");
        }
    }
    else
    {
        function debug_output() {}
    }

    /*
    ** Commonfunctions for application and autoloader
    */
    require_once 'include/GalClash/GCBase.php';

    /*
    ** Output begins here
    */
    $page = new \GalClash\GCPage($request, $session, $themes);


/*
** from here on:
** old code (partiell mixed with new code e.g. db-access)
*/

function put_allianz_kombinieren()
{
    global $request;

    $oallianz = trim($request->oalliance);
    $nallianz = trim($request->nalliance);
    $force    = $request->force;

?>
    <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
        <input type="submit" value="zurück" />
        <input name="admin" type="hidden" value="1" />
    </form>
    <h3>Achtung! Allianzname bereits vorhanden! Allianzen kombinieren?</h3>
    <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
        <fieldset>
            <legend>Allianzname ändern</legend>
            Spieler die unter der alten Allianz eingegragen sind werden der neuen zugeordnet
            und der alte Allianzname aus der Datenbank gelöscht!
            <table border="0" cellpadding="0" cellspacing="4">
                <tr>
                    <td align="right"><label for="oname">alter Name:</label></td>
                    <td><input name="oallianz" id="oallianz" type="text" size="20" maxlength="20" value="<?php print($oallianz); ?>"/></td>
                </tr>
                <tr>
                    <td align="right"><label for="nname">neuer Name:</label></td>
                    <td><input name="nallianz" id="nallianz" type="text" size="20" maxlength="20" value="<?php print($nallianz); ?>"/></td>
                </tr>
                <tr>
                    <td align="right"><label for="force">Namen kombinieren:</label></td>
                    <td><input name="force" id="force" type="checkbox" value="1" /></td>
                </tr>
            </table>
            <input type="submit" value="Ändern" /><input type="reset" value="Abbrechen" />
            <input name="n_allianz" type="hidden" value="1" />
            <input name="admin" type="hidden" value="1" />
        </fieldset>
    </form>
<?php
}

function put_namen_kombinieren()
{
    global $request;

    $oname = trim($request->oname);
    $nname = trim($request->nname);
    $force = $request->force;

?>
    <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
        <input type="submit" value="zurück" />
        <input name="admin" type="hidden" value="1" />
    </form>
    <h3>Achtung! Name bereits vorhanden! Spieler kombinieren?</h3>
    <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
        <fieldset>
            <legend>Name ändern</legend>
            Kolonien unter dem alten Namen werden dem neuen Namen zugeordnet und der alte Name aus der Datenbank gelöscht!
            <table border="0" cellpadding="0" cellspacing="4">
                <tr>
                    <td align="right"><label for="oname">alter Name:</label></td>
                    <td><input name="oname" id="oname" type="text" size="20" maxlength="20" value="<?php print($oname); ?>"/></td>
                </tr>
                <tr>
                    <td align="right"><label for="nname">neuer Name:</label></td>
                    <td><input name="nname" id="nname" type="text" size="20" maxlength="20" value="<?php print($nname); ?>"/></td>
                </tr>
                <tr>
                    <td align="right"><label for="force">Namen kombinieren:</label></td>
                    <td><input name="force" id="force" type="checkbox" value="1" /></td>
                </tr>
            </table>
            <input type="submit" value="Ändern" /><input type="reset" value="Abbrechen" />
            <input name="n_name" type="hidden" value="1" />
            <input name="admin" type="hidden" value="1" />
        </fieldset>
    </form>
<?php
}

function put_add_form($spieler, $allianz)
{
    global $session;
?>
    <div id="col_add_form">
        <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
            <fieldset>
                <legend>Kolonie hinzufügen<?php print($session->is_admin() ? " / löschen" : ""); ?></legend>
                Bitte auf korrekte Schreibweise achten: <code>BattleSqua</code> und <code>Battle5qua</code>
                sind zwei unterschiedliche Namen!
                <?php print($session->is_admin() ? "<br />Zum Löschen bitte alle Felder ausfüllen und auf Groß-/Kleinschreibung achten!" : ""); ?>
                <table border="0" cellpadding="0" cellspacing="4">
                    <tr>
                        <td align="right">Spieler:</td>
                        <td><input name="spieler" type="text" size="20" maxlength="20" value="<?php print($spieler); ?>" /></td>
                    </tr>
                    <tr>
                        <td align="right">Allianz ('-' für keine):</td>
                        <td><input name="allianz" type="text" size="20" maxlength="20" value="<?php print($allianz); ?>" /></td>
                    </tr>
                    <tr>
                        <table border="0" cellpadding="0" cellspacing="4">
                            <tr>
                                <td></td>
                                <td>Galaxie</td>
                                <td>System</td>
                                <td>Planet</td>
                            </tr>
                            <tr>
                                <td align="right">Kolonie</td>
                                <td align="center"><input name="galaxy" type="text" size="2" maxlength="2" /></td>
                                <td align="center"><input name="system" type="text" size="3" maxlength="3" /></td>
                                <td align="center"><input name="planet" type="text" size="2" maxlength="2" /></td>
                            </tr>
                        </table>
                    </tr>
<?php
    if($session->is_admin())
    {
?>
                    <tr>
                        <td align="right"><label for="force">Kolonie löschen:</label></td>
                        <td><input name="loeschen" id="force" type="checkbox" value="1" /></td>
                        <td align="right"><label for="force">Sicher:</label></td>
                        <td><input name="force" id="force" type="checkbox" value="1" /></td>
                    </tr>
<?php
    }
?>
                </table>
                <input type="submit" value="Einfügen" /><input type="reset" value="Abbrechen" />
                <input name="state" type="hidden" value="einfügen" />
            </fieldset>
        </form>
    </div>
<?php
}

function put_search_form()
{
    global $request;

    $ex = $request->exact;

?>
    <div id="search_form">
        <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
            <fieldset>
                <legend>Spieler oder Allianz suchen / DB Übersicht</legend>
                <table border="0" cellpadding="0" cellspacing="4">
                    <tr>
                        <td align="right">Spieler:</td>
                        <td><input name="spieler" type="text" size="20" maxlength="20" /></td>
                        <td align="right">ähnliche Suche:</td>
                        <td><input name="exact" type="radio" value="0" <?php print($ex ? "" : "checked=\"checked\""); ?> /></td>
                    </tr>
                    <tr>
                        <td align="right">Allianz ('-' für keine):</td>
                        <td><input name="allianz" type="text" size="20" maxlength="20" /></td>
                        <td align="right">exakte Suche:</td>
                        <td><input name="exact" type="radio" value="1" <?php print($ex ? "checked=\"checked\"" : ""); ?> /></td>
                    </tr>
                    <tr><td>&nbsp;</td></tr>
                    <tr>
                        <table border="0" cellpadding="0" cellspacing="4">
                            <tr>
                                <td></td>
                                <td>Galaxie</td>
                                <td>System</td>
                            </tr>
                            <tr>
                                <td align="right">Übersicht</td>
                                <td align="center"><input name="galaxy" type="text" size="2" maxlength="2" /></td>
                                <td align="center"><input name="system" type="text" size="3" maxlength="3" /></td>
                            </tr>
                        </table>
                    </tr>
                </table>
                <input type="submit" value="Suchen" /><input type="reset" value="Abbrechen" />
                <input name="state" type="hidden" value="suchen" />
            </fieldset>
        </form>
    </div>
<?php
}

function overview($gal, $sys)
{
    global $db;

    $dbh = $db->get_handle();
    if($sys == 0)
    {
        $sth = $dbh->prepare(
                "SELECT name, allianz, gal, sys, pla FROM V_spieler WHERE " .
                "gal = ? ORDER BY gal, sys, pla"
                );
        $arg = array($gal);
    }
    else
    {
        $sth = $dbh->prepare(
                "SELECT name, allianz, gal, sys, pla FROM V_spieler WHERE " .
                "gal = ? AND sys = ? ORDER BY gal, sys, pla"
                );
        $arg = array($gal, $sys);
    }
    try {
        $sth->execute($arg);
    }
    catch(PDOException $e) {
        \GalClash\error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        return NULL;
    }
    return $sth;
}

function suche($spieler, $name, $exact)
{
    global $db;

    $dbh = $db->get_handle();
    if($exact)
    {
        $sth = $dbh->prepare(
                "SELECT name, allianz, gal, sys, pla FROM V_spieler WHERE " .
                ($spieler ? "name" : "allianz") .
                " = ? ORDER BY allianz, name, gal, sys, pla"
                );
    }
    else
    {
        $name = "%" . $name . "%";
        $sth = $dbh->prepare(
                "SELECT name, allianz, gal, sys, pla FROM V_spieler WHERE " .
                ($spieler ? "name" : "allianz") .
                " LIKE ? ORDER BY allianz, name, gal, sys, pla"
                );
    }
    try {
        $sth->execute(array($name));
    }
    catch(PDOException $e) {
        \GalClash\error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        return NULL;
    }
    return $sth;
}

function display_result($sth)
{
    $result = $sth->fetchAll(PDO::FETCH_OBJ);
    $spieler="";
    $allianz="";
    if(!isset($result))
    {
        return "";
    }
    else
    {
?>
    <table border="1" rules="all">
        <colgroup span="5"></colgroup>
        <thead>
            <tr>
                <th rowspan="2">Allianz</th>
                <th rowspan="2">Spieler</th>
                <th colspan="3">Kolonien</th>
            </tr>
            <tr>
                <th>Galaxie</th>
                <th>System</th>
                <th>Planet</th>
            </tr>
        </thead>
        <tbody>
<?php
        foreach($result as $row)
        {
?>
            <tr>
                <td><?php print($allianz != $row->allianz ? $allianz = $row->allianz : ""); $allianz = $row->allianz; ?></td>
                <td><?php print($spieler != $row->name ? $row->name : ""); $spieler = $row->name; ?></td>
                <td align="center"><?php print($row->gal); ?></td>
                <td align="center"><?php print($row->sys); ?></td>
                <td align="center"><?php print($row->pla); ?></td>
            </tr>
<?php
        }
?>
        </tbody>
    </table>
<?php
    }
    return $allianz;
}

function add_coords($dbh, $gal, $sys, $pla, $s_id)
{
    $sth = $dbh->prepare("INSERT INTO coords (gal, sys, pla, s_id) VALUES (:gal, :sys, :pla, :s_id )");
    try {
        $sth->bindValue(":s_id", $s_id, PDO::PARAM_INT);
        $sth->bindValue(":gal", $gal, PDO::PARAM_INT);
        $sth->bindValue(":sys", $sys, PDO::PARAM_INT);
        $sth->bindValue(":pla", $pla, PDO::PARAM_INT);
        $sth->execute();
    }
    catch(PDOException $e) {
        \GalClash\error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        throw new Exception("rollback", 1);
    }
}

function update_coords($dbh, $c_id, $s_id)
{
    $sth = $dbh->prepare("UPDATE coords SET s_id = :s_id WHERE c_id = c_id");
    try {
        $sth->bindValue(":c_id", $c_id, PDO::PARAM_INT);
        $sth->bindValue(":s_id", $s_id, PDO::PARAM_INT);
        $sth->execute();
    }
    catch(PDOException $e) {
        \GalClash\error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        throw new Exception("rollback", 1);
    }
}

function neue_kolonie($arg)
{
    global $db;

    $dbh        = $db->get_handle();
    $spieler    = $arg->spieler;
    $allianz    = $arg->allianz;
    $gal        = (int) $arg->galaxy;
    $sys        = (int) $arg->system;
    $pla        = (int) $arg->planet;
    if($spieler == "-")
    {
        \GalClash\error_message("'-' als Spielername ist unzulässig!");
        return 0;
    }
    if(($spieler == "") || ($allianz == ""))
    {
        \GalClash\error_message("Spielername und Allianz müssen angegeben werden!");
        return 0;
    }

    $sth = $dbh->prepare("SELECT * FROM V_spieler WHERE gal = :gal AND sys = :sys AND pla = :pla");   /* kolonie bereits vorhanden? */

    try {
        $sth->bindValue(":gal", $gal, PDO::PARAM_INT);
        $sth->bindValue(":sys", $sys, PDO::PARAM_INT);
        $sth->bindValue(":pla", $pla, PDO::PARAM_INT);
        $sth->execute();
    }
    catch(PDOException $e) {
        \GalClash\error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        return;
    }
    if($sth->rowCount() == 1)
    {
        $row = $sth->fetch(PDO::FETCH_OBJ);
        if(($row->name == $spieler) && ($row->allianz == $allianz))
        {
            \GalClash\error_message("Kolonie bereits eingetragen");
        }
        else
        {
            \GalClash\error_message("Kolonie bereits eingetragen für anderen Spieler / andere Allianz! Bitte Allianzleiter informieren!");
            $sth->execute();
            display_result($sth);
            printf("<hr />");
        }
        return;
    }
    $s_id = get_spieler_id($dbh, $spieler);
    $a_id = get_allianz_id($dbh, $allianz);
    try {
        $dbh->beginTransaction();
        if($s_id == -1)     /* Spieler noch nicht vorhanden */
        {
            if($a_id == -1) /* Allianz auch noch nicht vorhanden */
                $a_id = add_allianz($dbh, $allianz);
            $s_id = add_spieler($dbh, $spieler, $a_id);
        }
        add_coords($dbh, $gal, $sys, $pla, $s_id);
        $dbh->commit();
    }
    catch(Exception $e) {
        $dbh->rollBack();
        \GalClash\error_message("Konnte Daten nicht eintragen...");
    }
}

function remove_kolonie($arg)
{
    global $db;

    $dbh        = $db->get_handle();
    $spieler    = $arg->spieler;
    $allianz    = $arg->allianz;
    $gal        = (int) $arg->galaxy;
    $sys        = (int) $arg->system;
    $pla        = (int) $arg->planet;

    if($spieler == "-")
    {
        \GalClash\error_message("'-' als Spielername ist unzulässig!");
        return 0;
    }
    if(($spieler == "") || ($allianz == ""))
    {
        \GalClash\error_message("Spielername und Allianz müssen angegeben werden!");
        return 0;
    }

    $sth = $dbh->prepare("SELECT * FROM V_spieler WHERE gal = :gal AND sys = :sys AND pla = :pla");   /* kolonie vorhanden? */

    try {
        $sth->bindValue(":gal", $gal, PDO::PARAM_INT);
        $sth->bindValue(":sys", $sys, PDO::PARAM_INT);
        $sth->bindValue(":pla", $pla, PDO::PARAM_INT);
        $sth->execute();
    }
    catch(PDOException $e) {
        \GalClash\error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        return;
    }
    if($sth->rowCount() == 1)
    {
        $row = $sth->fetch(PDO::FETCH_OBJ);
        if(($row->name == $spieler) && ($row->allianz == $allianz))     /* gefunden */
        {
            $sth1 = $dbh->prepare("DELETE FROM coords WHERE gal = :gal AND sys = :sys AND pla = :pla");
            try {
                $sth1->bindValue(":gal", $gal, PDO::PARAM_INT);
                $sth1->bindValue(":sys", $sys, PDO::PARAM_INT);
                $sth1->bindValue(":pla", $pla, PDO::PARAM_INT);
                $sth1->execute();
            }
            catch(PDOException $e) {
                \GalClash\error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
                return;
            }
        }
        else                                                                /* aber anderer Besitzer */
        {
            \GalClash\error_message("Kolonie ist für anderen Spieler / andere Allianz eingetragen! Bitte vor dem Löschen überprüfen!");
            $sth->execute();
            display_result($sth);
            printf("<hr />");
        }
        return;
    }
    \GalClash\error_message("Kolonie nicht gefunden!");
}

function get_urlaub()
{
    global $db;
    global $session;

    $dbh = $db->get_handle();
    $sth = $dbh->prepare("SELECT urlaub FROM V_user WHERE name = ?");
    try {
        $sth->execute(array($session->user));
    }
    catch(PDOException $e) {
        return "Fehler";
    }
    $row = $sth->fetch(PDO::FETCH_OBJ);
    $datum = $row->urlaub;
    return ($datum == "0000-00-00" ? "-" : ($datum == "9999-12-31" ? "+" : date("d.m.Y", strtotime($row->urlaub))));
}

function update_urlaub($datum)
{
    global $db;
    global $session;

    $dbh = $db->get_handle();
    $sth = $dbh->prepare("UPDATE user_pwd SET urlaub = :datum WHERE s_id = ( SELECT s_id FROM spieler WHERE name = :name )");
    try {
        $sth->bindValue(":datum", $datum);
        $sth->bindValue(":name", $session->user);
        $sth->execute();
    }
    catch(PDOException $e) {
        \GalClash\error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
    }
}

function namens_aenderung()
{
    global $db;
    global $request;

    $dbh   = $db->get_handle();
    $oname = trim($request->oname);
    $nname = trim($request->nname);
    $force = $request->force;

    if(($oname == "-") || ($nname == "-"))
    {
        \GalClash\error_message("'-' als Name ist unzulässig!");
        return 0;
    }
    if(($oname == "") || ($nname == ""))
    {
        \GalClash\error_message("Alter oder neuer Name ist leer!");
        return;
    }
    if($nname == $oname)
    {
        \GalClash\error_message("Alter und neuer Name sind identisch!");
        return;
    }
    $o_s_id = get_spieler_id($dbh, $oname);
    if($o_s_id == -1)
    {
        \GalClash\error_message(sprintf("Spieler '%s' nicht gefunden...", $oname));
        return 0;
    }

    $n_s_id = get_spieler_id($dbh, $nname);
    if(($n_s_id == -1) ||     /* Einfach: neuer Name existiert (hoffentlich) noch nicht... */
       ($o_s_id == $n_s_id))  /* bzw. andere groß/klein Schreibng */
    {
        $sth = $dbh->prepare("UPDATE spieler SET name = :nname WHERE name = :oname");
        try {
            $sth->bindValue(":oname", $oname);
            $sth->bindValue(":nname", $nname);
            $sth->execute();
        }
        catch(PDOException $e) {
            \GalClash\error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        }
        return 0;
    }
    if($force == 0)         /* Auch einfach: erstmal nachfragen */
        return 1;
                            /* hier wird's etwas aufwändiger */
            /* Kolonien unter dem alten Namen dem neuen zuordnen */
    $sth1 = $dbh->prepare("UPDATE coords SET s_id = :n_s_id WHERE s_id = :o_s_id"); 
            /* anschließend den alten Namen löschen */
    $sth2 = $dbh->prepare("DELETE FROM spieler WHERE s_id = :o_s_id");

    try {
        $dbh->beginTransaction();

        $sth1->bindValue(":o_s_id", $o_s_id);
        $sth1->bindValue(":n_s_id", $n_s_id);
        $sth2->bindValue(":o_s_id", $o_s_id);

        $sth1->execute();
        $sth2->execute();

        $dbh->commit();
    }
    catch(PDOException $e) {
        $dbh->rollBackk();
        \GalClash\error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        return 1;
    }
    return 0;
}

function allianz_aenderung()
{
    global $db;
    global $request;

    $dbh      = $db->get_handle();
    $oallianz = trim($request->oallianz);
    $nallianz = trim($request->nallianz);
    $force    = $request->force;

    if(($oallianz == "-") || ($nallianz == "-"))
    {
        \GalClash\error_message("'-' als Name ist unzulässig!");
        return 0;
    }
    if(($oallianz == "") || ($nallianz == ""))
    {
        \GalClash\error_message("Alter oder neuer Name ist leer!");
        return 0;
    }
    if($nallianz == $oallianz)
    {
        \GalClash\error_message("Alter und neuer Name sind identisch!");
        return 0;
    }
    $o_a_id = get_allianz_id($dbh, $oallianz);
    if($o_a_id == -1)
    {
        \GalClash\error_message(sprintf("Allianz '%s' nicht gefunden...", $oallianz));
        return 0;
    }

    $n_a_id = get_allianz_id($dbh, $nallianz);
    if(($n_a_id == -1) ||     /* Einfach: neuer Name existiert (hoffentlich) noch nicht... */
       ($o_a_id == $n_a_id))  /* bzw. andere groß/klein Schreibng */
    {
        $sth = $dbh->prepare("UPDATE allianzen SET allianz = :nallianz WHERE allianz = :oallianz");
        try {
            $sth->bindValue(":oallianz", $oallianz);
            $sth->bindValue(":nallianz", $nallianz);
            $sth->execute();
        }
        catch(PDOException $e) {
            \GalClash\error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        }
        return 0;
    }
    if($force == 0)         /* Auch einfach: erstmal nachfragen */
        return 2;
                            /* hier wird's etwas aufwändiger */
            /* Spieler unter dem alten Namen dem neuen zuordnen */
    $sth1 = $dbh->prepare("UPDATE spieler SET a_id = :n_a_id WHERE a_id = :o_a_id"); 
            /* anschließend den alten Namen löschen */
    $sth2 = $dbh->prepare("DELETE FROM allianzen WHERE a_id = :o_a_id");

    try {
        $dbh->beginTransaction();

        $sth1->bindValue(":o_a_id", $o_a_id);
        $sth1->bindValue(":n_a_id", $n_a_id);
        $sth2->bindValue(":o_a_id", $o_a_id);

        $sth1->execute();
        $sth2->execute();

        $dbh->commit();
    }
    catch(PDOException $e) {
        $dbh->rollBack();
        \GalClash\error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        return 2;
    }
    return 0;
}



/*
** from here on:
** mixed code (old & new)
*/

    debug_output();     // <<<< delete for production, together with definition up top <<<<

    $page->header();
    $page->start_main();

    if(sizeof($early_errors))           // Ouch, we had some errrors
    {
        foreach($early_errors as $key => $value)
        {
            if($value !== NULL)
                \GalClash\error_message(sprintf('Fehler wärend Initialisierung:<br />%s', $value->getMessage()));
        }
    }
    if(!isset($session))                // Ups… without a session we have a problem…
    {
        \GalClash\error_message('Session konnte nicht initialisiert werden!');
    }
    else if(!$session->is_logged_in())  // We he a session, but it's not logged in yet
    {
        if(is_null($login_ret))         // this try went wrong…
            \GalClash\error_message("Falscher Benutzername oder falsches Passwort!");

        $session->login_form();
    }
    else                                // now we may work…
    {
        if(isset($request->profile) || $session->c_pwd)    /* Kontenverwaltung */
        {
            $profile = new \GalClash\GCProfile($session, $request, $db);
            if(isset($request->update))
                $profile->update();
            $profile->form();
        }
        else if(isset($request->admin))                     /* ADMIN MODE */
        {
            $admin_page = new \GalClash\GCAdminMode($request, $session, $db);
        }
        else                                                /* normal Modus */
        {
            put_search_form();
            
            switch($request->state)
            {
                case "start":
                    break;
                case "suchen":
                    if($request->spieler != "")
                        $ret = suche(TRUE, $request->spieler, $request->exact);
                    else if($request->allianz != "")
                        $ret = suche(FALSE, $request->allianz, $request->exact);
                    else if($request->galaxy != 0)
                        $ret = overview($request->galaxy, $request->system);
                    else
                        \GalClash\error_message("Sorry, leere Suchanfragen werden nichg unterstützt...");
                    if(isset($ret) && $ret->rowCount() > 0)
                    {
                        print("<div id=\"search_res\">");
                        $a = display_result($ret);
                        print("</div>");
                    }
                    else
                    {
                        \GalClash\error_message("Nichts gefunden.");
                        $a = "";
                    }
                    if(!$request->exact)
                        $a = $request->allianz;
                    put_add_form(isset($request->spieler) ? $request->spieler: "", $a);
                    break;
                case "einfügen":
                    break;
                    if(!isset($request->loeschen))
                        neue_kolonie($request);
                    else
                    {
                        if(!isset($request->force))
                            \GalClash\error_message("Sicherheitsfrage nicht gesetzt! Kolonie wird nicht gelöscht!");
                        else
                            remove_kolonie($request);
                    }
                    $ret = suche(TRUE, $request->spieler, TRUE);
                    if(isset($ret) && $ret->rowCount() > 0)
                        display_result($ret);
                    put_add_form($request->spieler, $request->allianz);
                    break;
                default:
                    \GalClash\error_message("Sorry, aber so einfach ist das System nicht zu knacken ;-)");
            }
        }
    }

    // that's it…
    $page->end_main();
    $page->footer();
}
?>
