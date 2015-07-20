<?php
namespace GalClash {
    class GCThemes {
        private $themes;
        private $selected;

        public function __construct()
        {
            $path = $_SERVER["DOCUMENT_ROOT"] . CSS_PATH;
            $flist = glob($path . "*.css");
            $themes = array();

            foreach($flist as $f)
            {
                $f = substr(strrchr($f, "/"), 1);
                $p = strpos($f, ".");
                $themes[] = substr($f, 0, $p);
            }

            $this->themes = array_filter($themes, array($this, 'check_theme'));
        }

        private function check_theme($arg)
        {
            if($arg == "default")
                return FALSE;
            $path = $_SERVER["DOCUMENT_ROOT"] . CSS_PATH;
            if(!is_readable($path . $arg . ".css") || is_dir($path . $arg . ".css"))
                return FALSE;

            return TRUE;
        }

        public function get_themes()
        {
            return $this->themes;
        }

        public function get_selected()
        {
            return $this->selected;
        }

        public function set_theme()
        {
            $theme = isset($_COOKIE["GalClashDB"]["theme"]) ? $_COOKIE["GalClashDB"]["theme"] : DEFAULT_THEME;
            if((strpos($theme, "/") !== FALSE) || ($this->check_theme($theme) == FALSE))
                $theme = DEFAULT_THEME;

            if(isset($_POST["theme"]) && $this->check_theme($_POST["theme"]))
                setcookie("GalClashDB[theme]", $theme = $_POST["theme"], time() + 60*60*24*30);

            $this->selected = $theme;
        }
        
        public function theme_select()
        {
?>
                <label for="theme_select">Theme</label>
                <select name="theme" id="theme_select" size="1" />
<?php
            foreach($this->themes as $t)
            {
                if($t == $this->selected)
                    $fmt = "<option selected=\"selected\">%s</option>";
                else
                    $fmt = "<option>%s</option>";
                printf($fmt, $t);
            }
?>
                </select>
                <input type="submit" value="Auswählen" />
<?php
        }
    }
}

?>
