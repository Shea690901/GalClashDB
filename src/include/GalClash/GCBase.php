<?php
namespace {
    /*
    ** compatibily layer for php 5.5 password-hashing-functions
    */
    require_once 'include/password.php';

    /*
    ** base class (with autoloader) for the used library
    ** and the application classes
    */
    require_once 'include/Tiger/Base.php';
    $GalClash = new \Tiger\AutoLoader(\Tiger\AutoLoader::APPLICATION, 'GalClash');

    if(DEBUG)
    {
        error_reporting(E_ALL|E_STRICT|E_NOTICE|E_DEPRECATED);
    }
    else
    {
        error_reporting(E_ALL);
    }

    /*
    ** storage for error messages which occured before sending the page-header
    */
    $early_errors = array();

    // we might have some cookies…
    $cookie = new \Tiger\Cookie('GalClashDB');
    // we need cookies…
    $cookie->allow();

    $javascript;

    function use_javascript()
    {
        global $javascript;

        return isset($javascript) ? $javascript : FALSE;
    }

    function enable_javascript()
    {
        global $javascript;
        global $cookie;

        $javascript = TRUE;
        $cookie->set_key('javascript', TRUE);
    }

    function disable_javascript()
    {
        global $javascript;
        global $cookie;

        $javascript = FALSE;
        $cookie->unset_key('javascript');
    }

    /*
    ** we might have some cookie set to know if we use javascript
    ** if not: we don't use it:
    */
    $val = $cookie->get_key('javascript');
    if(!is_null($val) && $val)
        enable_javascript();
    else
        disable_javascript();

    /*
    ** get sanitized request variables
    */
    $request = new GalClash\GCRequest();

    /*
    ** connect to the database
    */
    try {
        $db = new \GalClash\GCDB(DB_ENGINE, DB_HOST, DB_PORT, DB_NAME, DB_CHARSET, DB_USER, DB_PWD);
    }
    catch(Exception $e) {
        $early_errors[] = $e;
        $db             = NULL;
    }

    /*
    ** initialize the session
    */
    try {
        $session = new GalClash\GCSession($request, $cookie);
        $session->open();
    }
    catch(Exception $e) {
        $early_errors[] = $e;
    }

    /*
    ** initialize css-themes
    */
    $themes = new \GalClash\GCThemes();
    $themes->set_theme($cookie);

    /*
    ** login
    */
    $login_ret = isset($session) ? $session->login($early_errors, $db) : NULL;

    /*
    ** logout session in case of errors
    */
    if(sizeof($early_errors) && isset($session))
        $session->logout();

    /*
    ** processing the request needs to be done here
    ** e.g. we might be forced to logoff and thus have to change the displayed header…
    */
    if(isset($session) && $session->is_logged_in())         // do we have a logged_in session?
    {
        // OK! then we can work
        if(isset($request->profile) || $session->c_pwd)     // user profile
        {
            $content = new \GalClash\GCProfile($request, $session, $db);
        }
        else if(isset($request->admin))                     // ADMIN MODE
        {
            $content = new \GalClash\GCAdminMode($request, $session, $db);
        }
        else
        {
            $content = new \GalClash\GCSaR($request, $session, $db);
        }
        $content->process_request($cookie); // Profile changes might change cookies
    }

    /*
    ** here after cookies won't be set => we might destroy the cookie objet now
    ** (thus actually setting the cookie)
    */
    unset($cookie);

    /*
    ** Output begins here
    */
    $page = new \GalClash\GCPage($request, $session, $themes);


/*
** from here on:
** still mixed code (old & new)
*/
    debug_output();     // <<<< delete for production, together with definition in index.php <<<<

    /*
    ** display header section
    **  - title
    **  - optional subtitle
    **  - "menu bar"
    */
    $page->header();

    // start main section, contents comes later…
    $page->start_main();

    if(sizeof($early_errors))           // Ouch, we had some errrors
    {
        foreach($early_errors as $key => $value)
        {
            if($value !== NULL)
                \GalClash\error_message(sprintf('Fehler während Initialisierung:<br />%s', $value->getMessage()));
        }
    }
    if(!isset($session))                // Ups… without a session we have a problem…
    {
        \GalClash\error_message('Session konnte nicht initialisiert werden!');
    }
    else
    {
        // first look if we have some message boxes to display…
        if($request->is_valid())
        {
            $content->msg_boxes();
        }

        if(!$session->is_logged_in())                       // not logged in (anymore?)
        {
            if(is_null($login_ret))                         // we just tried to log in, but this try went wrong…
                \GalClash\error_message("Falscher Benutzername oder falsches Passwort!");

            $session->login_form();
        }
        else
        {
            $content->put_form();
        }
        /*
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
        */
    }

    // that's it…
    $page->end_main();
    $page->footer();
}

namespace GalClash {
    /*
    ** Some simple message outputs
    */
    function message($msg, $type, $close = FALSE)
    {
        global $session;

        printf('<div class="alert alert-%s">', $type);
        if($close && isset($session) && use_javascript())
            printf('<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>');
        printf('%s</div>', $msg);
    }

    function success_message($msg)
    {
        message($msg, 'success', TRUE);
    }

    function info_message($msg)
    {
        message($msg, 'info', TRUE);
    }

    function warning_message($msg)
    {
        message($msg, 'warning');
    }

    function error_message($msg)
    {
        message($msg, 'danger');
    }

    function check_password($db, $uid, $pwd)
    {
        $crypted = $db->get_pwd_entry($uid);

        if($_SERVER['SERVER_ADDR'] == '127.0.0.1')
        {
            $info = password_get_info($crypted);
            if($info['algo'])
            {
                if(password_verify($pwd, $crypted))
                    return (password_needs_rehash($crypted, PASSWORD_DEFAULT)) ? 2 : 1;
                else
                    return 0;
            }
            else
                return ($crypted == sha1($pwd)) ? 2 : ($crypted == '') ? 1 : 0;
        }
        else
            return ($crypted == sha1($pwd)) ? 1 : ($crypted == '') ? 1 : 0;
    }

    function update_passwd($db, $uid, $pwd)
    {
        $db->update_passwd($uid, password_hash($this->request_ob->pwd, PASSWORD_DEFAULT));
    }
}
?>
