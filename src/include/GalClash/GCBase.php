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
        $session = new GalClash\GCSession($request);
        $session->open();
        $session->enable_java();                // <<<< delete this when ready <<<<
    }
    catch(Exception $e) {
        $early_errors[] = $e;
    }

    /*
    ** initialize css-themes
    */
    $themes = new \GalClash\GCThemes();
    $themes->set_theme();

    /*
    ** login
    */
    $login_ret = isset($session) ? $session->login($early_errors, $db) : NULL;

    /*
    ** destroy session (effectivly logout) in case of errors
    */
    if(sizeof($early_errors) && isset($session))
            $session->logout();
}

namespace GalClash {
    /*
    ** Some simple message outputs
    */
    function message($msg, $type, $close = FALSE)
    {
        global $session;

        printf('<div class="alert alert-%s">', $type);
        if($close && isset($session) && $session->use_java())
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
