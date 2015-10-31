<?php
namespace GalClash {
    class GCSaR extends GCMode {
        /*
        ** displays search results
        */
        private function display_search($result)
        {
            $session = $this->session;
            $type    = $result['s_type'];
            $search  = $result['search'];
?>
            <div id="search_result">
                <fieldset>
                    <legend>Suchergebnis für <?php print(ucfirst($type)); ?>suche nach '<?php print($search); ?>'</legend>
                </fieldset>
            </div>
<?php
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
            return;
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

            $ret = array('overview' => 1, 'ally' => $req->ally);
$ret['forms'] = array('member', 'allies');
            if(isset($req->add_user))
            {
                $ally = trim($req->am_ally);
                // $this->add_member();
                $ret['ally'] = $ally;
                $ret['forms'] = array('member', 'allies');
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

        public function put_form() {}

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
    }
}

?>
