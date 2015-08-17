<?php
namespace GalClash {
    class GCPage extends \Tiger\Base {

        /*
        ** __constructor
        ** prints:
        ** - doctypedefonition
        ** - html opening tag
        ** - head section
        ** - body opening tag
        */
        public function __construct(GCRequest $request, GCSession $session = NULL, GCThemes $themes)
        {
            parent::__construct();
            $this->req    = $request;
            $this->ses    = $session;
            $this->themes = $themes;
?>
<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->

        <!-- Sorry, this page isn't cachable -->
        <meta http-equiv="expires" content="0" />
        <meta http-equiv="cache-control" content="no-cache" />
        <meta http-equiv="pragma" content="no-cache" />

        <!--neither should be followed by any spiders or stored in cache -->
        <meta name="robots" content="noindex, nofollow, noarchive" />

        <meta name="author" content="Tiger" />
<?php
            $stat   = stat($_SERVER["SCRIPT_FILENAME"]);
            $mtime  = $stat['mtime'];
            printf("\t\t<meta name=\"date\" content=\"%s\" />\n", date(DATE_RFC822, $mtime));
            unset($stat);
            unset($mtime);
?>

        <title>α KoordinatenDB für Galactic Clash</title>

        <link rel="stylesheet" type="text/css" href="<?php print(CSS_PATH); ?>default.css" />
        <link rel="stylesheet" type="text/css" href="<?php print(CSS_PATH . $this->themes->get_selected()); ?>.css" />
        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/html5shiv/last/mainfile"></script>
            <script src="https://oss.maxcdn.com/respond/last/mainfile"></script>
        <![endif]-->
<?php
            if(isset($this->ses) && $this->ses->use_java())
            {
                printf("\t\t<script src=\"%s\"></script>\n", JQUERY_PATH);
                printf("\t\t<script src=\"%s\"></script>\n", SCRIPT_PATH);
            }
 ?>
    </head>

    <body>
<?php
        }

        /*
        ** __destructor
        ** prints:
        ** body and html end tags
        */
        public function __destruct()
        {
?>
    </body>
</html>
<?php
            parent::__destruct();
        }

        /*
        ** header section
        */
        public function header()
        {
?>
        <header>
            <h1>KoordinatenDB für Galactic Clash</h1>
<?php
            if(isset($this->session) && $this->session->is_logged_in())
            {
                $subtitle = $this->session->is_admin() ? "ADMINMODE" :
                    (isset($this->request->konto) || $this->session->c_pwd) ? "Kontensteuerung" :
                    NULL;
                if(!is_null($subtitle))
                    printf("\t\t\t<h2>%s</h2>\n", $subtitle);
            }
?>
            <nav>
                <div id="theme-select">
                    <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
<?php
            $this->themes->theme_select();
            if(isset($this->req->admin))
                print("<input type=\"hidden\" name=\"admin\" value=\"1\" />");
            if(isset($this->req->konto))
                print("<input type=\"hidden\" name=\"konto\" value=\"1\" />");
?>
                    </form>
                </div>
<?php
            if(isset($this->ses) && $this->ses->is_logged_in())
            {
                $this->admin_button(isset($this->req->admin));
                $this->profile_button(isset($this->req->profile) || $this->ses->c_pwd);
                $this->ses->logout_button();
            }
?>
            </nav>
        </header>
<?php
        }

        public function start_main()
        {
?>
        <main>
            <div id="koerper">
<?php
        }

        public function end_main()
        {
?>
            </div>
        </main>
<?php
        }

        public function footer()
        {
            global $_VERSION;
?>
        <footer>
            <div id="fuss_text">Bei Fehlern oder Fragen bitte eine in-game PM an 'Tiger' (10:283:4)</div>
            <div id="version"><?php print("Version " . $_VERSION); ?></div>
        </footer>
<?php
        }

        private function profile_button($arg)
        {
?>
            <div id="profile_b">
                <form action="<?php print($_SERVER["PHP_SELF"]); ?>" method="post" accept-charset="utf-8"> 
<?php
            if(!$arg)
            {
?>
                    <input name="profile" type="hidden" value="1" />
                    <input type="submit" value="Benutzerprofil" />
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

        private function admin_button($arg)
        {
            if($this->ses->is_admin())
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
        }
    }
}

?>
