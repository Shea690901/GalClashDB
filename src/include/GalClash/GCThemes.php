<?php
namespace GalClash {
    class GCThemes
    {
        private $themes;
        private $selected;

        public function __construct()
        {
            $path = $_SERVER['DOCUMENT_ROOT'].CSS_PATH;
            $flist = glob($path.'*.css');
            $themes = [];

            foreach ($flist as $f) {
                $f = substr(strrchr($f, '/'), 1);
                $p = strpos($f, '.');
                $themes[] = substr($f, 0, $p);
            }

            $this->themes = array_filter($themes, [$this, 'check_theme']);
        }

        private function check_theme($arg)
        {
            if ($arg == 'default') {
                return false;
            }
            $path = $_SERVER['DOCUMENT_ROOT'].CSS_PATH;
            if (!is_readable($path.$arg.'.css') || is_dir($path.$arg.'.css')) {
                return false;
            }

            return true;
        }

        public function get_themes()
        {
            return $this->themes;
        }

        public function get_selected()
        {
            return $this->selected;
        }

        public function set_theme($cookie)
        {
            $theme = $cookie->get_key('theme');
            if (is_null($theme)) {
                $theme = DEFAULT_THEME;
            }
            if ((strpos($theme, '/') !== false) || ($this->check_theme($theme) == false)) {
                $theme = DEFAULT_THEME;
            }

            if (isset($_POST['theme']) && $this->check_theme($_POST['theme'])) {
                $cookie->set_key('theme', ($theme = $_POST['theme']));
            }

            $this->selected = $theme;
        }

        public function theme_select()
        {
            ?>
                <label for="theme_select">Theme</label>
                <select name="theme" id="theme_select" size="1" />
<?php
            foreach ($this->themes as $t) {
                if ($t == $this->selected) {
                    $fmt = '<option selected="selected">%s</option>';
                } else {
                    $fmt = '<option>%s</option>';
                }
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
