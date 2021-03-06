<?php
error_reporting(E_ALL|E_STRICT);
/*
   Das CSS ist aufgeteilt auf mehrere Dateien:
   - default.css   : Standardeinstellungen für alle Geräte
   - {theme}.css   : Farb- und teilweise Positionseinstellungen
*/

require_once "config.php";

$_VERSION = "2.1.0";

function error_message($msg)
{
    printf("<p>%s</p>\n", $msg);
}

function is_cyrillish($txt)
{
    return (preg_match('/[АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдеёжзийклмнопрстуфхцчшщъыьэюя]/', $txt) === 1);
}

function print_($txt)
{
    if(is_cyrillish($txt))
        $fmt = '<div lang="ru">%s</div>';
    else
        $fmt = '%s';
    printf($fmt, $txt);
}

function is_admin()
{
    return $_SESSION["admin"];
}

function is_leiter()
{
    return $_SESSION["leiter"];
}

function is_timeout()
{
    global $timeout;
    return FALSE;

    if(isset($_SESSION["time"]))
        if((time() - $_SESSION["time"]) > $timeout)
        {
            error_message("Zu lange untätig... Bitte neu einloggen!");
            return TRUE;
        }
    return FALSE;
}

function cancel_session()
{
    $_SESSION = array();
    if(ini_get("session.use_cookies"))
    {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

function put_login_form()
{
?>
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

function start()
{
    session_start();
    if(isset($_POST["logout"]) || is_timeout())
        cancel_session();
        
    if(!isset($_SESSION["user"]))
    {
        if(!isset($_POST["user"]))
            return 1;
        if($dbh = connect())
        {
            if(check_password($dbh, $user = htmlspecialchars($_POST["user"]), htmlspecialchars($_POST["pwd"])))
            {
                $_SESSION["user"] = $user;
                $_SESSION["allianz"] = get_allianz($dbh, $user);
                $_SESSION["time"] = time();
                $_SESSION["admin"] = get_admin_status($dbh, $user);
                $_SESSION["leiter"] = get_leiter_status($dbh, $user);
                $_SESSION["c_pwd"] = get_change_password($dbh, $user);
                return 0;
            }
            else
            {
                return 2;
            }
        }
        return 3;
    }
    $_SESSION["time"] = time();
    return 0;
}

function connect()
{
    global $db_host, $db_port, $db_port, $db_name, $db_charset, $db_user, $db_pwd;

    $dsn = 'mysql:host=' . $db_host . ';' . ($db_port == 0 ? '' : 'port=' . $db_port . ';') . 'dbname=' . $db_name .';charset=' . $db_charset;
    $options = array(
    //    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
        PDO::ATTR_PERSISTENT => TRUE,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ); 
    try {
        $dbh = new PDO($dsn, $db_user, $db_pwd, $options);
        return $dbh;
    }
    catch(PDOException $e) {
        return NULL;
    }
}

function get_post_vars()
{
    global $post_vars;

    $post_vars["state"] = array_key_exists('state', $_POST) ? htmlspecialchars($_POST['state']) : "start";
    $post_vars["spieler"] = array_key_exists('spieler', $_POST) ? trim(htmlspecialchars($_POST['spieler'])) : "";
    $post_vars["allianz"] = array_key_exists('allianz', $_POST) ? trim(htmlspecialchars($_POST['allianz'])) : "";
    $post_vars["galaxy"] = array_key_exists('galaxy', $_POST) ? htmlspecialchars($_POST['galaxy']) : 0;
    $post_vars["system"] = array_key_exists('system', $_POST) ? htmlspecialchars($_POST['system']) : 0;
    $post_vars["planet"] = array_key_exists('planet', $_POST) ? htmlspecialchars($_POST['planet']) : 0;
    $post_vars["exact"] = array_key_exists('exact', $_POST) ? htmlspecialchars($_POST['exact']) == "1" : FALSE;
}

function put_logout_button()
{
?>
    <div id="logout_b">
        <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
            Eingeloggt als: '<?php print($_SESSION["user"]); ?>'
            <input name="logout" type="hidden" value="1" />
            <input type="submit" value="Logout" />
        </form>
    </div>
<?php
}

function put_theme_select()
{
?>
    <div id="theme_select">
        <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
            Theme
            <select name="theme" id="name" size="1" />
<?php
        global $theme;

        foreach(get_themes() as $t)
        {
            if($t == $theme)
                $fmt = "<option selected=\"selected\">%s</option>";
            else
                $fmt = "<option>%s</option>";
            printf($fmt, $t);
        }
?>
            </select>
            <input type="submit" value="Auswählen" />
<?php
        if(isset($_POST["admin"]))
            print("<input type=\"hidden\" name=\"admin\" value=\"1\" />");
        if(isset($_POST["konto"]))
            print("<input type=\"hidden\" name=\"konto\" value=\"1\" />");
?>
        </form>
    </div>
<?php
}

function put_konto_button($arg)
{
?>
    <div id="konto_b">
        <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
<?php
    if(!$arg)
    {
?>
            <input name="konto" type="hidden" value="1" />
            <input type="submit" value="Benutzerkonto" />
<?php
    }
    else
    {
?>
            <input type="submit" value="Zurück" />
<?php
    }
?>
        </form>
    </div>
<?php
}

function put_admin_button($arg)
{
?>
    <div id="admin_b">
        <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
<?php
    if(!$arg)
    {
?>
            <input name="admin" type="hidden" value="1" />
            <input type="submit" value="ADMIN MODE" />
<?php
    }
    else
    {
?>
            <input type="submit" value="Zurück" />
<?php
    }
?>
        </form>
    </div>
<?php
}

function put_allianz_kombinieren()
{
    $oallianz = array_key_exists('oallianz', $_POST) ? trim(htmlspecialchars($_POST['oallianz'])) : "";
    $nallianz = array_key_exists('nallianz', $_POST) ? trim(htmlspecialchars($_POST['nallianz'])) : "";
    $force = array_key_exists('force', $_POST) ? htmlspecialchars($_POST['force']) : 0;

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
    $oname = array_key_exists('oname', $_POST) ? trim(htmlspecialchars($_POST['oname'])) : "";
    $nname = array_key_exists('nname', $_POST) ? trim(htmlspecialchars($_POST['nname'])) : "";
    $force = array_key_exists('force', $_POST) ? htmlspecialchars($_POST['force']) : 0;

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

function put_admin_forms()
{
    $oallianz = array_key_exists('oallianz', $_POST) ? trim(htmlspecialchars($_POST['oallianz'])) : "";
    $nallianz = array_key_exists('nallianz', $_POST) ? trim(htmlspecialchars($_POST['nallianz'])) : "";
    $allianz = array_key_exists('allianz', $_POST) ? trim(htmlspecialchars($_POST['allianz'])) : "";
    $all = array_key_exists('all', $_POST) ? trim(htmlspecialchars($_POST['all'])) : ($allianz != "" ? $allianz : "-");
    $oname = array_key_exists('oname', $_POST) ? trim(htmlspecialchars($_POST['oname'])) : "";
    $nname = array_key_exists('nname', $_POST) ? trim(htmlspecialchars($_POST['nname'])) : "";
    $name = array_key_exists('name', $_POST) ? trim(htmlspecialchars($_POST['name'])) : "";
    $uebersicht = array_key_exists('uebersicht', $_POST) ? htmlspecialchars($_POST['uebersicht']) : 0;
?>
    <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
        <fieldset>
            <legend><?php print(($all == "-" ? "Gruppen" : "Allianz") . "übersicht" .
                    ($all == "-" ? "" : " für '" . $all. "'")); ?></legend>
<?php
    display_uebersicht(($all == "-" ? 0 : 1), $all);
?>
            <select name="all" id="all" size="1" />
<?php
    if($dbh = connect())
    {
        $sth = $dbh->prepare("SELECT allianz FROM V_blacklisted");

        try {
            $sth->execute();
        }
        catch(PDOException $e) {
            error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        }
        if($sth->rowCount() > 0)
        {
            $rows = $sth->fetchAll(PDO::FETCH_OBJ);
            if($all == "-")
                print("<option>-</option>");
            else
                print("<option selected=\"selected\">-</option>");
            foreach($rows as $row)
            {
                if(($all == "-") && ($row->allianz == $_SESSION["allianz"]))
                    $fmt = "<option selected=\"selected\">%s</option>";
                else
                    $fmt = "<option>%s</option>";
                printf($fmt, $row->allianz);
            }
        }
    }
?>
            </select>
            <input type="submit" value="Auswahl" />
            <input name="admin" type="hidden" value="1" />
        </fieldset>
    </form>
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
    if($dbh = connect())
    {
        $sth = $dbh->prepare("SELECT allianz FROM V_blacklisted");

        try {
            $sth->execute();
        }
        catch(PDOException $e) {
            error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        }
        if($sth->rowCount() > 0)
        {
            $rows = $sth->fetchAll(PDO::FETCH_OBJ);
            foreach($rows as $row)
            {
                if($row->allianz == $_SESSION["allianz"])
                    $fmt = "<option selected=\"selected\">%s</option>";
                else
                    $fmt = "<option>%s</option>";
                printf($fmt, $row->allianz);
            }
        }
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
        if($dbh = connect())
        {
            $sth = $dbh->prepare("SELECT name, blocked FROM V_user WHERE a_id = " .
                    "( SELECT spieler.a_id FROM spieler WHERE name = :name ) " .
                    "AND name != :name " .
                    "AND name != ( SELECT spieler.name FROM ( spieler JOIN allianzen on leiter_id = spieler.s_id ) WHERE spieler.a_id = ( SELECT spieler.a_id FROM spieler WHERE name = :name ) )" .
                    "ORDER BY name");

            try {
                $sth->bindValue(":name", $_SESSION["user"]);
                $sth->execute();
            }
            catch(PDOException $e) {
                error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
            }
            if($sth->rowCount() > 0)
            {
                $rows = $sth->fetchAll(PDO::FETCH_OBJ);
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
        if($dbh = connect())
        {
            $sth = $dbh->prepare("SELECT name, blocked FROM V_user WHERE blocked = :name OR a_id = " .
                    "( SELECT spieler.a_id FROM spieler WHERE name = :name ) " .
                    "AND name != :name " .
                    "AND name != ( SELECT spieler.name FROM ( spieler JOIN allianzen on leiter_id = spieler.s_id ) WHERE spieler.a_id = ( SELECT spieler.a_id FROM spieler WHERE name = :name ) )" .
                    "ORDER BY name");

            try {
                $sth->bindValue(":name", $_SESSION["user"]);
                $sth->execute();
            }
            catch(PDOException $e) {
                error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
            }
            if($sth->rowCount() > 0)
            {
                $rows = $sth->fetchAll(PDO::FETCH_OBJ);
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
        }
?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <input type="submit" value="Eintragen" /><input type="reset" value="Abbrechen" />
                        <input name="b_user" type="hidden" value="1" />
                        <input name="admin" type="hidden" value="1" />
                        <input name="all" type="hidden" value="<?php print($_SESSION["allianz"]); ?>" />
                    </fieldset>
                </form>
            </td>
<?php
    if(is_leiter())
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
        if($dbh = connect())
        {
            $sth = $dbh->prepare("SELECT name, admin FROM V_user WHERE a_id = " .
                    "( SELECT spieler.a_id FROM ( spieler JOIN allianzen ON leiter_id = spieler.s_id ) WHERE name = :name ) " .
                    "AND name != :name");

            try {
                $sth->bindValue(":name", $_SESSION["user"]);
                $sth->execute();
            }
            catch(PDOException $e) {
                error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
            }
            if($sth->rowCount() > 0)
            {
                $rows = $sth->fetchAll(PDO::FETCH_OBJ);
                foreach($rows as $row)
                {
                    if($row->admin == 1)
                        $fmt = "<option value=\"-%s\">%s (Admin)</option>";
                    else
                        $fmt = "<option value=\"+%s\">%s</option>";
                    printf($fmt, $row->name, $row->name);
                }
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
                        <input name="all" type="hidden" value="<?php print($_SESSION["allianz"]); ?>" />
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
                                <td><input name="allianz" id="allianz" type="Text" size="20" maxlength="20" value="<?php print($allianz); ?>"/></td>
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
    if($dbh = connect())
    {
        $sth = $dbh->prepare("SELECT allianz FROM V_blacklisted");

        try {
            $sth->execute();
        }
        catch(PDOException $e) {
            error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        }
        if($sth->rowCount() > 0)
        {
            $rows = $sth->fetchAll(PDO::FETCH_OBJ);
            foreach($rows as $row)
            {
                if($row->allianz != $_SESSION["allianz"])
                    printf("<option>%s</option>", $row->allianz);
            }
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
<?php
    }
?>
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
                                <td><input name="oallianz" id="oallianz" type="text" size="20" maxlength="20" value="<?php print($oallianz); ?>"/></td>
                            </tr>
                            <tr>
                                <td align="right"><label for="nallianz">neuer Name:</label></td>
                                <td><input name="nallianz" id="nallianz" type="text" size="20" maxlength="20" value="<?php print($nallianz); ?>"/></td>
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

function put_konto_forms($disable)
{
?>
    <?php if($disable) error_message("Bitte Passwort ändern"); ?>
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
            <input name="konto" type="hidden" value="1" />
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
            <input name="konto" type="hidden" value="1" />
            <input name="urlaub" type="hidden" value="1" />
        </fieldset>
    </form>
<?php
}

function put_add_form($spieler, $allianz)
{
?>
    <div id="col_add_form">
        <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
            <fieldset>
                <legend>Kolonie hinzufügen<?php print(is_admin() ? " / löschen" : ""); ?></legend>
                Bitte auf korrekte Schreibweise achten: <code>BattleSqua</code> und <code>Battle5qua</code>
                sind zwei unterschiedliche Namen!<br />
                Das gleiche gilt für <code>BattleSqu3</code> und <code>BattleSqua3</code><br />
                oder auch <code lang="ru">СССР</code> und <code>CCCP</code>…
                <?php print(is_admin() ? "<ul><li>Zum Löschen bitte alle Felder ausfüllen und auf Groß-/Kleinschreibung achten!</li>" .
                        "<li>Zum Ändern der Allianzzugehörigkeit die Koordinatenfelder frei lassen!</li></ul>" : ""); ?>
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
    if(is_admin())
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
    global $post_vars;

    $ex = $post_vars["exact"];

?>
    <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
        <fieldset>
            <legend>Gruppenübersicht</legend>
<?php
    display_uebersicht(0, "-");
?>
        </fieldset>
    </form>
    <div id="search_form">
        Kyrillische Zeichen für 'copy&amp;paste':
        <div text-size="110%" lang="ru">
            А Б В Г Д Е Ё Ж З И Й К Л М Н О П Р С Т У Ф Х Ц Ч Ш Щ Ъ Ы Ь Э Ю Я<br />
            а б в г д е ё ж з и й к л м н о п р с т у ф х ц ч ш щ ъ ы ь э ю я
        </div>
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
    if($dbh = connect())
    {
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
            error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
            return NULL;
        }
        return $sth;
    }
    error_message("Bitte später nochmal versuchen...");
    return NULL;
}

function suche($spieler, $name, $exact)
{
    if($dbh = connect())
    {
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
            error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
            return NULL;
        }
        return $sth;
    }
    error_message("Bitte später nochmal versuchen...");
    return NULL;
}

function display_uebersicht($ansicht, $allianz)
{
    if($dbh = connect())
    {
        switch($ansicht)
        {
            case 1:     $sth = $dbh->prepare("SELECT name, admin, urlaub, blocked " .
                                "FROM V_user NATURAL JOIN allianzen WHERE allianz = :allianz " .
                                "ORDER BY name");
                        $sth->bindValue(":allianz", $allianz);
                        break;
            default:    $sth = $dbh->prepare("SELECT allianz, COUNT(1) AS anzahl FROM V_user NATURAL JOIN allianzen " .
                                "GROUP BY allianz ORDER BY COUNT(1) DESC, allianz");
        }
        try {
            $sth->execute();
        }
        catch(PDOException $e) {
            error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
            return;
        }
        $result = $sth->fetchAll(PDO::FETCH_OBJ);
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
                <td align="center"><?php $d= $row->urlaub; print($d == "0000-00-00" ? "-" : ($d == "9999-12-31" ? "unbegrenzt" : date("d.m.Y", strtotime($d)))); ?></td>
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
<?php
        }
        return;
    }
    error_message("Bitte später nochmal versuchen...");
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
                <td><?php print_($allianz != $row->allianz ? $allianz = $row->allianz : ""); $allianz = $row->allianz; ?></td>
                <td><?php print_($spieler != $row->name ? $row->name : ""); $spieler = $row->name; ?></td>
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

function get_allianz($dbh, $user)
{
    $sth = $dbh->prepare("SELECT allianz FROM allianzen natural join spieler WHERE name = :name");
    try {
        $sth->bindValue(":name", $user);
        $sth->execute();
    }
    catch(PDOException $e) {
        error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        return NULL;
    }
    if($sth->rowCount() == 1)
    {
        $row = $sth->fetch(PDO::FETCH_OBJ);
        return $row->allianz;
    }
    return NULL;
}

function get_allianz_id($dbh, $allianz)
{
    $sth = $dbh->prepare("SELECT a_id FROM allianzen WHERE allianz = :allianz");
    try {
        $sth->bindValue(":allianz", $allianz);
        $sth->execute();
    }
    catch(PDOException $e) {
        error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        throw new Exception("rollback", 1);
    }
    if($sth->rowCount() == 1)
    {
        $row = $sth->fetch(PDO::FETCH_OBJ);
        return (int) $row->a_id;
    }
    return -1;
}

function add_allianz($dbh, $allianz)
{
    $sth = $dbh->prepare("INSERT INTO allianzen (allianz) VALUES ( :allianz )");
    try {
        $sth->bindValue(":allianz", $allianz);
        $sth->execute();
    }
    catch(PDOException $e) {
        error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        throw new Exception("rollback", 1);
    }
    try {
        return get_allianz_id($dbh, $allianz);
    }
    catch(Exception $e) {
        throw $e;
    }
}

function get_spieler_id($dbh, $name)
{
    $sth = $dbh->prepare("SELECT s_id FROM spieler WHERE name = :name");
    try {
        $sth->bindValue(":name", $name);
        $sth->execute();
    }
    catch(PDOException $e) {
        error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        throw new Exception("rollback", 1);
    }
    if($sth->rowCount() == 1)
    {
        $row = $sth->fetch(PDO::FETCH_OBJ);
        return (int) $row->s_id;
    }
    return -1;
}

function add_spieler($dbh, $name, $a_id)
{
    $sth = $dbh->prepare("INSERT INTO spieler (name, a_id) VALUES ( :name, :a_id )");
    try {
        $sth->bindValue(":a_id", $a_id, PDO::PARAM_INT);
        $sth->bindValue(":name", $name);
        $sth->execute();
    }
    catch(PDOException $e) {
        error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        throw new Exception("rollback", 1);
    }
    try {
        return get_spieler_id($dbh, $name);
    }
    catch(Exception $e) {
        throw $e;
    }
}

function get_member_id($dbh, $name)
{
    $sth = $dbh->prepare("SELECT m_id FROM V_user WHERE name = :name");
    try {
        $sth->bindValue(":name", $name);
        $sth->execute();
    }
    catch(PDOException $e) {
        error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        throw new Exception("rollback", 1);
    }
    if($sth->rowCount() == 1)
    {
        $row = $sth->fetch(PDO::FETCH_OBJ);
        return (int) $row->m_id;
    }
    return -1;
}

function update_spieler($dbh, $s_id, $a_id)
{
    $sth = $dbh->prepare("UPDATE spieler SET a_id = :a_id WHERE s_id = :s_id");
    try {
        $sth->bindValue(":a_id", $a_id, PDO::PARAM_INT);
        $sth->bindValue(":s_id", $s_id, PDO::PARAM_INT);
        $sth->execute();
    }
    catch(PDOException $e) {
        error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        throw new Exception("rollback", 1);
    }
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
        error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
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
        error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        throw new Exception("rollback", 1);
    }
}

function neue_kolonie($arg)
{
    global $max_gal;
    global $max_sys;
    global $max_pla;

    $spieler    = $arg["spieler"];
    $allianz    = $arg["allianz"];
    $gal        = (int) $arg["galaxy"];
    $sys        = (int) $arg["system"];
    $pla        = (int) $arg["planet"];
    if($spieler == "-")
    {
        error_message("'-' als Spielername ist unzulässig!");
        return 0;
    }
    if($spieler == "")
    {
        error_message("Spielername muss angegeben werden!");
        return 0;
    }
    if(is_admin() && ($gal == 0) && ($sys == 0) && ($pla == 0))
    {
        if($dbh = connect())
        {
            $s_id = get_spieler_id($dbh, $spieler);
            if($s_id == -1)
            {
                error_message("Spieler nicht gefunden");
                return 0;
            }
            $a_id = get_allianz_id($dbh, $allianz);

            try {
                $dbh->beginTransaction();
                
                if($a_id == -1)
                    $a_id = add_allianz($dbh, $allianz);
                update_spieler($dbh, $s_id, $a_id);
                $dbh->commit();
            }
            catch(PDOException $e) {
                error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
            }
            return;
        }
    }
    if(($gal < 1) || ($gal > $max_gal))
    {
        error_message("Galaxy ausserhalb erlaubtem Bereich");
        return 0;
    }
    if(($sys < 1) || ($sys > $max_sys))
    {
        error_message("System ausserhalb erlaubtem Bereich");
        return 0;
    }
    if(($pla < 1) || ($pla > $max_pla))
    {
        error_message("Planet ausserhalb erlaubtem Bereich");
        return 0;
    }
    $allianz = $allianz == "" ? "-" : $allianz;
    if($dbh = connect())
    {
        $sth = $dbh->prepare("SELECT * FROM V_spieler WHERE gal = :gal AND sys = :sys AND pla = :pla");   /* kolonie bereits vorhanden? */

        try {
            $sth->bindValue(":gal", $gal, PDO::PARAM_INT);
            $sth->bindValue(":sys", $sys, PDO::PARAM_INT);
            $sth->bindValue(":pla", $pla, PDO::PARAM_INT);
            $sth->execute();
        }
        catch(PDOException $e) {
            error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
            return;
        }
        if($sth->rowCount() == 1)
        {
            $row = $sth->fetch(PDO::FETCH_OBJ);
            if(($row->name == $spieler) && ($row->allianz == $allianz))
            {
                error_message("Kolonie bereits eingetragen");
            }
            else
            {
                error_message("Kolonie bereits eingetragen für anderen Spieler / andere Allianz! Bitte Allianzleiter informieren!");
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
            error_message("Konnte Daten nicht eintragen...");
        }
    }
}

function remove_kolonie($arg)
{
    $spieler    = $arg["spieler"];
    $allianz    = $arg["allianz"];
    $gal        = (int) $arg["galaxy"];
    $sys        = (int) $arg["system"];
    $pla        = (int) $arg["planet"];
    if($spieler == "-")
    {
        error_message("'-' als Spielername ist unzulässig!");
        return 0;
    }
    if(($spieler == "") || ($allianz == ""))
    {
        error_message("Spielername und Allianz müssen angegeben werden!");
        return 0;
    }
    if($dbh = connect())
    {
        $sth = $dbh->prepare("SELECT * FROM V_spieler WHERE gal = :gal AND sys = :sys AND pla = :pla");   /* kolonie vorhanden? */

        try {
            $sth->bindValue(":gal", $gal, PDO::PARAM_INT);
            $sth->bindValue(":sys", $sys, PDO::PARAM_INT);
            $sth->bindValue(":pla", $pla, PDO::PARAM_INT);
            $sth->execute();
        }
        catch(PDOException $e) {
            error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
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
                    error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
                    return;
                }
            }
            else                                                                /* aber anderer Besitzer */
            {
                error_message("Kolonie ist für anderen Spieler / andere Allianz eingetragen! Bitte vor dem Löschen überprüfen!");
                $sth->execute();
                display_result($sth);
                printf("<hr />");
            }
            return;
        }
        error_message("Kolonie nicht gefunden!");
    }
}

function get_admin_status($dbh, $user)
{
    $stmt = $dbh->prepare("SELECT admin FROM V_user WHERE name = ?");
    try {
        if($stmt->execute(array($user)))
            $row = $stmt->fetch(PDO::FETCH_OBJ);
    }
    catch(PDOException $e) {
        error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        return FALSE;
    }
    return ($row->admin == "1");
}

function get_leiter_status($dbh, $user)
{
    $stmt = $dbh->prepare("SELECT leiter FROM V_user WHERE name = ?");
    try {
        if($stmt->execute(array($user)))
            $row = $stmt->fetch(PDO::FETCH_OBJ);
    }
    catch(PDOException $e) {
        error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        return FALSE;
    }
    return ($row->leiter == "1");
}

function get_change_password($dbh, $user)
{
    $stmt = $dbh->prepare("SELECT c_pwd FROM V_user WHERE name = ?");
    try {
        if($stmt->execute(array($user)))
            $row = $stmt->fetch(PDO::FETCH_OBJ);
    }
    catch(PDOException $e) {
        error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        return FALSE;
    }
    return ($row->c_pwd == "1");
}

function check_password($dbh, $user, $pwd)
{
    $stmt = $dbh->prepare("SELECT pwd, blocked FROM V_user WHERE name = ?");
    try {
        if($stmt->execute(array($user)))
            $row = $stmt->fetch(PDO::FETCH_OBJ);
    }
    catch(PDOException $e) {
        error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        return FALSE;
    }
    if(isset($row->blocked) && ($row->blocked != "-"))
        return FALSE;
    if(isset($row->pwd))
        return (($row->pwd == sha1($pwd)) || ($row->pwd == ""));
    return FALSE;
}

function update_password($dbh, $pwd)
{
    $sth = $dbh->prepare("UPDATE user_pwd SET pwd = :pwd, c_pwd = 0 WHERE s_id = ( SELECT s_id FROM spieler WHERE name = :name )");
    try {
        $sth->bindValue(":pwd", $pwd);
        $sth->bindValue(":name", $_SESSION["user"]);
        $sth->execute();
    }
    catch(PDOException $e) {
        error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
    }
    $_SESSION["c_pwd"] = FALSE;
}

function get_urlaub()
{
    if($dbh = connect())
    {
        $sth = $dbh->prepare("SELECT urlaub FROM V_user WHERE name = ?");
        try {
            $sth->execute(array($_SESSION["user"]));
        }
        catch(PDOException $e) {
            return "Fehler";
        }
        $row = $sth->fetch(PDO::FETCH_OBJ);
        $datum = $row->urlaub;
        return ($datum == "0000-00-00" ? "-" : ($datum == "9999-12-31" ? "+" : date("d.m.Y", strtotime($row->urlaub))));
    }
    return "Fehler";
}

function update_urlaub($datum)
{
    if($dbh = connect())
    {
        $sth = $dbh->prepare("UPDATE user_pwd SET urlaub = :datum WHERE s_id = ( SELECT s_id FROM spieler WHERE name = :name )");
        try {
            $sth->bindValue(":datum", $datum);
            $sth->bindValue(":name", $_SESSION["user"]);
            $sth->execute();
        }
        catch(PDOException $e) {
            error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        }
    }
}

function update_konto()
{
    if(isset($_POST["pwd"]))
    {
        $opwd  = htmlspecialchars($_POST["opwd"]);
        $npwd1 = htmlspecialchars($_POST["npwd1"]);
        $npwd2 = htmlspecialchars($_POST["npwd2"]);
        if(strlen($npwd1) < 8)
        {
            error_message("Passwort zu kurz!");
            return;
        }
        if($npwd1 != $npwd2)
        {
            error_message("Passwörter stimmen nicht überein!");
            return;
        }
        if($opwd == $npwd1)
        {
            error_message("Altes und neues Passwort sind identisch!");
            return;
        }
        if($dbh = connect())
        {
            if(check_password($dbh, $_SESSION["user"], $opwd))
                update_password($dbh, sha1($npwd1));
            else
            {
                error_message("Falsches Passwort!");
                cancel_session();
            }
        }
        return;
    }
    if(isset($_POST["urlaub"]))
    {
        $datum = htmlspecialchars($_POST['datum']);
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

function neues_mitglied()
{
    $name    = array_key_exists('name', $_POST) ? trim(htmlspecialchars($_POST['name'])) : "";
    $allianz = array_key_exists('allianz', $_POST) ? trim(htmlspecialchars($_POST['allianz'])) : "";
    $pwd     = array_key_exists('pwd', $_POST) ? trim(htmlspecialchars($_POST['pwd'])) : "";

    if(strlen($allianz) == 0)
    {
        error_message("Nicht schummeln....");
        return 0;
    }
    if(strlen($name) == 0)
    {
        error_message("Name muss angegeben sein!");
        return 0;
    }
    if($name == "-")
    {
        error_message("'-' als Name ist unzulässig!");
        return 0;
    }
    if(strlen($pwd) > 0)
        $pwd = sha1($pwd);

    if($dbh = connect())
    {
        $s_id = get_spieler_id($dbh, $name);
        $a_id = get_allianz_id($dbh, $allianz);

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
            error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        }
    }
}

function admin_mitglied()
{
    $name = array_key_exists('name', $_POST) ? trim(htmlspecialchars($_POST['name'])) : "";
    $func = $name[0] == "+" ? 1 : 0;
    $name = substr($name, 1);

    if($dbh = connect())
    {
        $m_id = get_member_id($dbh, $name);
        if($m_id == -1)
        {
            error_message("Mitglied nicht gefunden");
            return 0;
        }

        $sth = $dbh->prepare("UPDATE user_pwd SET admin = :admin WHERE m_id = :m_id");
        try {
            $sth->bindValue(":m_id", $m_id, PDO::PARAM_INT);
            $sth->bindValue(":admin", $func, PDO::PARAM_INT);
            $sth->execute();
        }
        catch(PDOException $e) {
            error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        }
    }
    return 0;
}

function loesche_mitglied()
{
    $name = array_key_exists('name', $_POST) ? trim(htmlspecialchars($_POST['name'])) : "";

    if($dbh = connect())
    {
        $m_id = get_member_id($dbh, $name);
        if($m_id == -1)
        {
            error_message("Mitglied nicht gefunden");
            return 0;
        }

        $s_id = get_spieler_id($dbh, $name);
        if($s_id == -1)
        {
            error_message("Spieler nicht gefunden");
            return 0;
        }

        $sth1 = $dbh->prepare("UPDATE spieler SET a_id = 1 WHERE s_id = :s_id");
        $sth2 = $dbh->prepare("DELETE FROM user_pwd WHERE m_id = :m_id");
        try {
            $dbh->beginTransaction();

            $sth1->bindValue(":s_id", $s_id, PDO::PARAM_INT);
            $sth2->bindValue(":m_id", $m_id, PDO::PARAM_INT);
            $sth1->execute();
            $sth2->execute();

            $dbh->commit();
        }
        catch(PDOException $e) {
            error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        }
    }
    return 0;
}

function sperre_mitglied()
{
    $name = array_key_exists('name', $_POST) ? trim(htmlspecialchars($_POST['name'])) : "";
    $func = $name[0];
    $name = substr($name, 1);

    if($dbh = connect())
    {
        $m_id = get_member_id($dbh, $name);
        if($m_id == -1)
        {
            error_message("Mitglied nicht gefunden");
            return 0;
        }

        switch($func)
        {
            case "+":
                $b_id = get_spieler_id($dbh, $_SESSION["user"]);
                if($b_id == -1)
                {
                    error_message("Leiter nicht gefunden");
                    return 0;
                }

                $sth = $dbh->prepare("UPDATE user_pwd SET b_id = :b_id WHERE m_id = :m_id");
                try {
                    $sth->bindValue(":m_id", $m_id, PDO::PARAM_INT);
                    $sth->bindValue(":b_id", $b_id, PDO::PARAM_INT);
                    $sth->execute();
                }
                catch(PDOException $e) {
                    error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
                }
                break;
            case "-":
                $sth = $dbh->prepare("UPDATE user_pwd SET b_id = 1 WHERE m_id = :m_id");
                if($m_id == -1)
                {
                    error_message("Mitglied nicht gefunden");
                    return 0;
                }
                try {
                    $sth->bindValue(":m_id", $m_id, PDO::PARAM_INT);
                    $sth->execute();
                }
                catch(PDOException $e) {
                    error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
                }
                break;
        }
    }
    return 0;
}

function neue_allianz()
{
    $allianz = array_key_exists('allianz', $_POST) ? trim(htmlspecialchars($_POST['allianz'])) : "";
    $name    = array_key_exists('name', $_POST) ? trim(htmlspecialchars($_POST['name'])) : "";
    $pwd     = array_key_exists('pwd', $_POST) ? trim(htmlspecialchars($_POST['pwd'])) : "";

    if(strlen($allianz) == 0)
    {
        error_message("Allianzname muss angegeben sein!");
        return 0;
    }
    if(strlen($name) == 0)
    {
        error_message("Kein Allianzleiter angegeben!");
        return 0;
    }
    if(($name == "-") || ($allianz == "-"))
    {
        error_message("'-' als Name ist unzulässig!");
        return 0;
    }
    if(strlen($pwd) > 0)
        $pwd = sha1($pwd);

    if($dbh = connect())
    {
        $s_id = get_spieler_id($dbh, $name);
        $a_id = get_allianz_id($dbh, $allianz);
        $m_id = get_member_id($dbh, $name);

        if($a_id == -1)
            $sth1 = $dbh->prepare("INSERT INTO allianzen (allianz) VALUES ( :allianz )");
        if($s_id == -1)
            $sth2 = $dbh->prepare("INSERT INTO spieler (name, a_id) VALUES ( :name, :a_id )");
        else
            $sth2 = $dbh->prepare("UPDATE spieler SET a_id = :a_id WHERE s_id = :s_id");
        if($m_id == -1)
            $sth3 = $dbh->prepare("INSERT INTO user_pwd ( s_id, pwd, admin ) VALUES ( :s_id, :pwd, 1 )");
        $sth4 = $dbh->prepare("UPDATE allianzen SET leiter_id = :s_id WHERE a_id = :a_id");
        $sth5 = $dbh->prepare("INSERT INTO blacklisted ( a_id ) VALUES ( :a_id )");
        $sth6 = $dbh->prepare("INSERT INTO user_pwd ( s_id ) SELECT s_id FROM spieler " .
                "WHERE a_id = :a_id AND NOT EXISTS ( SELECT 1 FROM user_pwd WHERE user_pwd.s_id  = spieler.s_id )");

        try {
            $dbh->beginTransaction();

            if($a_id == -1)
            {
                $sth1->bindValue(":allianz", $allianz);
                $sth1->execute();
                $a_id = get_allianz_id($dbh, $allianz);
            }
            print('1');
            $sth2->bindValue(":a_id", $a_id, PDO::PARAM_INT);
            if($s_id == -1)
            {
                $sth2->bindValue(":name", $name);
                $sth2->execute();
                $s_id = get_spieler_id($dbh, $name);
            }
            else
            {
                $sth2->bindValue(":s_id", $s_id, PDO::PARAM_INT);
                $sth2->execute();
            }
            print('2');
            if($m_id == -1)
            {
                $sth3->bindValue(":pwd", $pwd);
                $sth3->bindValue(":s_id", $s_id, PDO::PARAM_INT);
                $sth3->execute();
            }
            print('3');
            $sth4->bindValue(":a_id", $a_id, PDO::PARAM_INT);
            $sth4->bindValue(":s_id", $s_id, PDO::PARAM_INT);
            $sth4->execute();
            print('4');
            $sth5->bindValue(":a_id", $a_id, PDO::PARAM_INT);
            $sth5->execute();
            $sth6->bindValue(":a_id", $a_id, PDO::PARAM_INT);
            $sth6->execute();

            $dbh->commit();
        }
        catch(PDOException $e) {
            $dbh->rollBack();
            error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        }
    }
}

function entferne_allianz()
{
    $allianz = array_key_exists('allianz', $_POST) ? trim(htmlspecialchars($_POST['allianz'])) : "";

    if(strlen($allianz) == 0)
    {
        error_message("Allianzname muss angegeben sein!");
        return 0;
    }
    if($allianz == "-")
    {
        error_message("'-' als Name ist unzulässig!");
        return 0;
    }
    if($dbh = connect())
    {
        $a_id = get_allianz_id($dbh, $allianz);

        if($a_id == -1)
        {
            error_message(sprintf("'%s' ist kein Mitglied der Gruppe!", $allianz));
            return 0;
        }

        $sth1 = $dbh->prepare("DELETE user_pwd FROM user_pwd NATURAL JOIN spieler WHERE a_id = :a_id");
        $sth2 = $dbh->prepare("DELETE FROM blacklisted WHERE a_id = :a_id");

        try {
            $dbh->beginTransaction();

            $sth1->bindValue(":a_id", $a_id, PDO::PARAM_INT);
            $sth1->execute();
            $sth2->bindValue(":a_id", $a_id, PDO::PARAM_INT);
            $sth2->execute();

            $dbh->commit();
        }
        catch(PDOException $e) {
            $dbh->rollBack();
            error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
        }
    }
}

function namens_aenderung()
{
    $oname = array_key_exists('oname', $_POST) ? trim(htmlspecialchars($_POST['oname'])) : "";
    $nname = array_key_exists('nname', $_POST) ? trim(htmlspecialchars($_POST['nname'])) : "";
    $force = array_key_exists('force', $_POST) ? htmlspecialchars($_POST['force']) : 0;

    if(($oname == "-") || ($nname == "-"))
    {
        error_message("'-' als Name ist unzulässig!");
        return 0;
    }
    if(($oname == "") || ($nname == ""))
    {
        error_message("Alter oder neuer Name ist leer!");
        return;
    }
    if($nname == $oname)
    {
        error_message("Alter und neuer Name sind identisch!");
        return;
    }
    if($dbh = connect())
    {
        $o_s_id = get_spieler_id($dbh, $oname);
        if($o_s_id == -1)
        {
            error_message(sprintf("Spieler '%s' nicht gefunden...", $oname));
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
                error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
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
            error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
            return 1;
        }
    }
    return 0;
}

function allianz_aenderung()
{
    $oallianz = array_key_exists('oallianz', $_POST) ? trim(htmlspecialchars($_POST['oallianz'])) : "";
    $nallianz = array_key_exists('nallianz', $_POST) ? trim(htmlspecialchars($_POST['nallianz'])) : "";
    $force = array_key_exists('force', $_POST) ? htmlspecialchars($_POST['force']) : 0;

    if(($oallianz == "-") || ($nallianz == "-"))
    {
        error_message("'-' als Name ist unzulässig!");
        return 0;
    }
    if(($oallianz == "") || ($nallianz == ""))
    {
        error_message("Alter oder neuer Name ist leer!");
        return 0;
    }
    if($nallianz == $oallianz)
    {
        error_message("Alter und neuer Name sind identisch!");
        return 0;
    }
    if($dbh = connect())
    {
        $o_a_id = get_allianz_id($dbh, $oallianz);
        if($o_a_id == -1)
        {
            error_message(sprintf("Allianz '%s' nicht gefunden...", $oallianz));
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
                error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
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
            error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
            return 2;
        }
    }
    return 0;
}

function check_theme($arg)
{
    global $css_path;

    if($arg == "default")
        return FALSE;
    $path = $_SERVER["DOCUMENT_ROOT"] . $css_path;
    if(!is_readable($path . $arg . ".css") || is_dir($path . $arg . ".css"))
        return FALSE;

    return TRUE;
}

function get_themes()
{
    global $css_path;

    $path = $_SERVER["DOCUMENT_ROOT"] . $css_path;
    $flist = glob($path . "*.css");
    $themes = array();

    foreach($flist as $f)
    {
        $f = substr(strrchr($f, "/"), 1);
        $p = strpos($f, ".");
        $themes[] = substr($f, 0, $p);
    }

    return array_filter($themes, "check_theme");
}

function set_theme()
{
    global $theme;
    global $default_theme;

    $theme = isset($_COOKIE["GalClashDB"]["theme"]) ? $_COOKIE["GalClashDB"]["theme"] : $default_theme;
    if((strpos($theme, "/") !== FALSE) || (check_theme($theme) == FALSE))
        $theme = $default_theme;

    if(isset($_POST["theme"]) && check_theme($_POST["theme"]))
        setcookie("GalClashDB[theme]", $theme = $_POST["theme"], time() + 60*60*24*30);
}

set_theme();
$start = start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="de" xml:lang="de">
    <head>
        <title>KoordinatenDB für Galactic Clash</title>

        <meta name="author" content="Tiger" />
<?php
$stat   = stat($_SERVER["SCRIPT_FILENAME"]);
$mtime  = $stat['mtime'];
printf("<meta name=\"date\" content=\"%s\" />\n", date(DATE_RFC822, $mtime));
?>
        <meta name="robots" content="noindex, nofollow" />
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
        <meta http-equiv="expires" content="0" />
        <meta http-equiv="cache-control" content="no-cache" />
        <meta http-equiv="pragma" content="no-cache" />

        <link rel="stylesheet" type="text/css" href="/css/default.css" />
        <link rel="stylesheet" type="text/css" href="<?php print($css_path . $theme); ?>.css" />
    </head>

    <body>
        <div id="kopf">
            <h1>KoordinatenDB für Galactic Clash</h1>
            <hr />
<?php
    put_theme_select();
    if($start == 0)
    {
        put_logout_button();
        if(isset($_POST["konto"]) || $_SESSION["c_pwd"])
        {
            print("<div id=\"sub_t\">Kontensteuerung</div>");
            put_konto_button(TRUE);
        }
        else
            put_konto_button(FALSE);
        if(is_admin())
        {
            if(isset($_POST["admin"]))
            {
                print("<div id=\"sub_t\">ADMINMODE</div>");
                put_admin_button(TRUE);
            }
            else
                put_admin_button(FALSE);
        }
    }
?>
        </div>

        <div id="koerper">
<?php
if($start == 0)
{
    if(isset($_POST["konto"]) || $_SESSION["c_pwd"])    /* Kontenverwaltung */
    {
        if(isset($_POST["update"]))
            update_konto();
        if(isset($_SESSION["user"]))
            put_konto_forms($_SESSION["c_pwd"]);
    }
    else if(isset($_POST["admin"]))                     /* ADMIN MODE */
    {
?>
<?php
        $ret = 0;
        if(isset($_POST["n_user"]))
            neues_mitglied();
        if(isset($_POST["b_user"]))
            sperre_mitglied();
        if(isset($_POST["l_user"]))
            loesche_mitglied();
        if(isset($_POST["a_user"]))
            admin_mitglied();
        if(isset($_POST["n_gruppe"]))
            neue_allianz();
        if(isset($_POST["l_gruppe"]))
            entferne_allianz();
        if(isset($_POST["n_name"]))
            $ret = namens_aenderung();
        if(isset($_POST["n_allianz"]))
            $ret = allianz_aenderung();
        switch($ret)
        {
            case 1:
                put_namen_kombinieren();
                break;
            case 2:
                put_allianz_kombinieren();
                break;
            default:
                put_admin_forms();
        }
    }
    else                                                /* normal Modus */
    {
        get_post_vars();
        put_search_form();
        
        switch($post_vars["state"])
        {
            case "start":
                break;
            case "suchen":
                if($post_vars["spieler"] != "")
                    $ret = suche(TRUE, $post_vars["spieler"], $post_vars["exact"]);
                else if($post_vars["allianz"] != "")
                    $ret = suche(FALSE, $post_vars["allianz"], $post_vars["exact"]);
                else if($post_vars["galaxy"] != 0)
                    $ret = overview($post_vars["galaxy"], $post_vars["system"]);
                else
                    error_message("Sorry, leere Suchanfragen werden nichg unterstützt...");
                if(isset($ret) && $ret->rowCount() > 0)
                {
                    print("<div id=\"search_res\">");
                    $a = display_result($ret);
                    print("</div>");
                }
                else
                {
                    error_message("Nichts gefunden.");
                    $a = "";
                }
                if(!$post_vars["exact"])
                    $a = $post_vars["allianz"];
                put_add_form(isset($post_vars["spieler"]) ? $post_vars["spieler"]: "", $a == "" ? "-" : $a);
                break;
            case "einfügen":
                if(!isset($_POST["loeschen"]))
                    neue_kolonie($post_vars);
                else
                {
                    if(!isset($_POST["force"]))
                        error_message("Sicherheitsfrage nicht gesetzt! Kolonie wird nicht gelöscht!");
                    else
                        remove_kolonie($post_vars);
                }
                $ret = suche(TRUE, $post_vars["spieler"], TRUE);
                if(isset($ret) && $ret->rowCount() > 0)
                    display_result($ret);
                put_add_form($post_vars["spieler"], $post_vars["allianz"]);
                break;
            default:
                error_message("Sorry, aber so einfach ist das System nicht zu knacken ;-)");
        }
    }
}
else
{
    if($start == 2)
        error_message("Falscher Benutzername oder falsches Passwort!");
    else if($start == 3)
        error_message("Bitte später nochmal versuchen...");

    put_login_form();
}
?>
        </div>
        <div id="fuss">
            <div id="fuss_text">Bei Fehlern oder Fragen bitte eine in-game PM an 'Tiger' (10:283:4)</div>
            <div id="version"><?php print("Version " . $_VERSION); ?></div>
        </div>
    </body>
</html>
