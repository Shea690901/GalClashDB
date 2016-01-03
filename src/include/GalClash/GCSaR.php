<?php
namespace GalClash {
    class GCSaR extends GCMode {
        /*
        ** depending on result of process_request print:
        ** - search form
        ** - history form (changes of alliance/name of a single player)
        ** - colony related forms (add/change owner/delete)
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
                        case 'search':          // we're searching for something
                            $this->display_search_form($ret);
                            unset($ret['result']);
                            break;
                        case 'history':         // any history available?
                            $this->display_history_form($ret);
                            unset($ret['result']);
                            break;
                        case 'colony':          // anything colony related
                            $this->display_colony_form($ret);
                            unset($ret['result']);
                            break;
                        default:
                            throw new \ErrorException('Unknown formular type!');
                            break;
                    }
                }
            }
        }

        private function display_search_form($args)
        {
            $req = $this->req;
            $ex = isset($req->exact) ? (bool) $req->exact : FALSE;
?>
    <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8" class="rcontainer "> 
<?php

            if(isset($args['result']))
            {
                print("<div id=\"search_res\">");
                $a = $this->display_result($args['result'], isset($args['history']));
                print("</div>");
            }

?>
                <div id="search_form" <?php print(isset($args['result']) ? '' : 'style="width:100%"'); ?>>
                    <fieldset>
                        <legend>Spieler oder Allianz suchen / DB Übersicht</legend>
                        <div>Kyrillische Zeichen für 'copy&amp;paste':</div>
                        <p text-size="110%" lang="ru">
                            А Б В Г Д Е Ё Ж З И Й К Л М Н О П Р С Т У Ф Х Ц Ч Ш Щ Ъ Ы Ь Э Ю Я<br />
                            а б в г д е ё ж з и й к л м н о п р с т у ф х ц ч ш щ ъ ы ь э ю я
                        </p>
                        <table border="0" cellpadding="0" cellspacing="4">
                            <tr>
                                <td align="right"><label for="s_player">Spieler:</label></td>
                                <td><input id="s_player" name="player" type="text" size="20" maxlength="20" /></td>
                            </tr>
                            <tr>
                                <td align="right"><label for="s_ally">Allianz ('-' für keine):</ally></td>
                                <td><input id="s_ally" name="ally" type="text" size="20" maxlength="20" /></td>
                            </tr>
                            <tr>
                                <td align="right"><label for="s_exact">exakte Suche:</label></td>
                                <td><input id="s_exact" name="exact" type="checkbox" value="1" <?php print($ex ? "checked=\"checked\"" : ""); ?> /></td>
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
                        <input type="submit" name="search" value="Suchen" /><input type="reset" value="Abbrechen" />
                    </fieldset>
<?php
            if($this->ses->is_admin() && isset($args["del"]))
            {
?>
                    <fieldset>
                        <legend>Kolonien löschen</legend>
                        <p>
                        Löscht die selektierten Kolonien.<br />
                        Zur Sicherheit ist die weiter unten befindliche Sicherheitsabfrage zu bejahen!
                        </p>
                        <input type="submit" name="del_colonies" value="Löschen" />
                        <input name="save_player" type="hidden" value="<?php print($req->player);?>" />
                        <input name="save_ally" type="hidden" value="<?php print($req->ally);?>" />
                        <input name="save_exact" type="hidden" value="<?php print($req->exact);?>" />
                        <input name="save_galaxy" type="hidden" value="<?php print($req->galaxy);?>" />
                        <input name="save_system" type="hidden" value="<?php print($req->system);?>" />
                        <input name="search" type="hidden" value="TRUE" />
                    </fieldset>
                    <input type="checkbox" name="del_security" value="1">Wollen sie die markierten Kolonien wirklich löschen?
<?php
            }
?>
                </div>
                <input name="state" type="hidden" value="work" />
            </form>
<?php
        }

        private function display_general_forms($args)
        {
?>
    <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8" class="rcontainer "> 
        <div id="admin_allies_forms">
            <fieldset>
                <legend>Name ändern</legend>
                <table border="0" cellpadding="0" cellspacing="4">
                    <tr>
                        <td align="right"><label for="oname">alter Name:</label></td>
                        <td><input name="oname" id="oname" type="text" size="20" maxlength="20" /></td>
                    </tr>
                    <tr>
                        <td align="right"><label for="nname">neuer Name:</label></td>
                        <td><input name="nname" id="nname" type="text" size="20" maxlength="20" /></td>
                    </tr>
                    <tr>
                        <td align="right"><label for="c_player">Spielername:</label></td>
                        <td><input type="radio" name="c_type" id="c_player" value="player" /></td>
                    </tr>
                    <tr>
                        <td align="right"><label for="c_player">Allianzname:</label></td>
                        <td><input type="radio" name="c_type" id="c_player" value="ally" /></td>
                    </tr>
                </table>
                <input type="submit" name="c_name" value="Ändern" />
                <input type="hidden" name="n_name" value="1" />
                <input type="hidden" name="admin" value="1" />
                <input type="hidden" name="force" value="0" />
                <input type="hidden" name="ally" value="<?php print($args['ally']); ?>" />
                <input type="hidden" name="state" value="work" />
            </fieldset>
        </div>
    </form>
<?php
        }

        /*
        ** displays search results
        */

        private function display_result($result, $hist)
        {
            $player = "";
            $ally   = "";
            if(!isset($result) || (sizeof($result) == 0))
            {
                info_message("Nichts gefunden…");
                return "";
            }
            else
            {
                $adm = $this->ses->is_admin();
        ?>
            <table border="1" rules="all">
                <colgroup span="6"></colgroup>
                <thead>
                    <tr>
                        <th rowspan="2">Allianz</th>
                        <th rowspan="2">Spieler</th>
                        <th colspan="3">Kolonien</th>
                        <th rowspan="2">Mark</th>
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
                        <td>
<?php
                    if($hist && ($ally != $row->allianz))
                    {
                            print('<input name="hist" type="radio" value="A' . $row->allianz .'">');
                    }
                    print($ally != $row->allianz ? $ally = $row->allianz : "");
?>
                        </td>
                        <td>
<?php
                    if($hist && ($player != $row->name))
                    {
                            print('<input name="hist" type="radio" value="P' . $row->name .'">');
                    }
                    print($player != $row->name ? $player = $row->name : "");
?>
                        </td>
                        <td align="center"><?php print($row->gal); ?></td>
                        <td align="center"><?php print($row->sys); ?></td>
                        <td align="center"><?php print($row->pla); ?></td>
                        <td><input type="checkbox" name="col_mark[]" value="<?php printf("%d:%d:%d", $row->gal, $row->sys, $row->pla); ?>"
                         <?php print($adm ? '' : 'disabled="disabled"'); ?> /></td>
                    </tr>
        <?php
                }
        ?>
                </tbody>
            </table>
        <?php
            }
            return $ally;
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
        public function process_request($arg = NULL)
        {
            $this->ret = $this->do_it();
        }

        private function do_it()
        {
            $req = $this->req;
            $state = trim($req->state);

            // nothing really to process…
            if($state == 'start')
            {
                $ret = array('forms' => array('search'));
            }

            // now begins the work
            elseif($state == 'work')
            {
                $ret    = array('forms' => array('search'), 'history' => TRUE);
                $player = $req->player;
                $ally   = $req->ally;
                $exact  = $req->exact;
                $galaxy = $req->galaxy;
                $system = $req->system;
                if(isset($req->del_colonies))
                {
                    if(!isset($req->force))
                        $this->store_error_message("Sicherheitsfrage nicht gesetzt! Kolonie wird nicht gelöscht!");
                    else
                    {
                        foreach($req->col_mark as $col)
                        {
                            $this->remove_kolonie($col);
                        }
                    }
                    $player = $req->save_player;
                    $ally   = $req->save_ally;
                    $exact  = $req->save_exact;
                    $galaxy = $req->save_galaxy;
                    $system = $req->save_system;
                }
                elseif(isset($req->add_colony))
                {
                    neue_kolonie($req);
                }
                if(isset($req->search))
                {
                    if($player != "")
                        $result = $this->search($player, TRUE, $exact);
                    else if($ally != "")
                        $result = $this->search($ally, FALSE, $exact);
                    else if($galaxy != 0)
                        $result = $this->overview($galaxy, $system);
                    else
                        $this->store_error_message("Sorry, leere Suchanfragen werden nicht unterstützt...");
                    if(isset($result))
                    {
                        $ret['result'] = $result;
                        if(sizeof($result))
                            $ret['del'] = TRUE;
                    }
                    if(!$req->exact)
                    {
                        $a = $req->ally;
                    }
                    else
                    {
                        $a = "";
                    }
                    put_add_form(isset($req->spieler) ? $req->spieler: "", $a);
                }
                /*
                $ret = suche(TRUE, $req->spieler, TRUE);
                if(isset($ret) && $ret->rowCount() > 0)
                    display_result($ret);
                put_add_form($request->spieler, $request->allianz);
                 */
                else
                    $this->store_error_message("Sorry, aber soo einfach ist das System nicht zu knacken ;-)");
            }
            return $ret;
        }

        private function search($name, $search_player, $exact_search)
        {
            $db = $this->db;
            try {
                return $db->search_colony($name, $search_player, $exact_search);
            }
            catch(Exception $e) {
                $this->store_error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
                return NULL;
            }
        }

        private function overview($gal, $sys)
        {
            $db = $this->db;
            try {
                return $db->gal_sys_overview($gal, $sys);
            }
            catch(Exception $e) {
                $this->store_error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
                return NULL;
            }
        }

        private function remove_kolonie($arg)
        {
            $db  = $this->db;
            $arr = explode($arg, ":");

                return;
            }
            \GalClash\error_message("Kolonie nicht gefunden!");
        }
    }
}

?>
