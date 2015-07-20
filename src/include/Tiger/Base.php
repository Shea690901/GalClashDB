<?php
namespace Tiger {
    /*
    ** base class
    **
    ** extends the possibilities of not declared public class variables
    ** via __set, __get, …
    ** with the possibility of restricting the possible keys.
    **
    ** this allows for simpler extending interfaces by using the
    ** magic-interface while still having the possibility of error-messages
    ** when using disallowed (think: typo) member-variables.
    ** at the §ame time this class allows to iterate through it's
    ** (pseudo) member-varables
    */
    class Base implements \iterator {
        private $data;
        private $valid_keys;

        public function __construct($vk = NULL)
        {
            $this->data = array();
            $this->valid_keys = $vk;
        }

        public function __destruct()
        {
            unset($this->valid_keys);
            unset($this->data);
        }

        private function check_key($name)
        {
            if($this->valid_keys !== NULL)
            {
                if(array_search($name, $this->valid_keys) === FALSE)
                {
                    $trace = debug_backtrace();
                    trigger_error(
                        'Undefined ' . get_called_class() . '::property: "' . $name .
                        '" in ' . $trace[0]['file'] .
                        ' on line ' . $trace[0]['line'],
                        E_USER_ERROR);
                }
            }
        }
        
        public function __set($name, $value)
        {
            $this->check_key($name);
            $this->data[$name] = $value;
        }

        public function __get($name)
        {
            $this->check_key($name);
            if(array_key_exists($name, $this->data))
                return $this->data[$name];

            $trace = debug_backtrace();
            trigger_error(
                'Undefined ' . get_called_class() . '::property: "' . $name .
                '" in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line'],
                E_USER_ERROR);
        }

        public function __isset($name)
        {
            $this->check_key($name);
            return isset($this->data[$name]);
        }

        public function __unset($name)
        {
            $this->check_key($name);
            unset($this->data[$name]);
        }

        public function rewind()
        {
            reset($this->data);
        }

        public function current()
        {
            $var = current($this->data);
            return $var;
        }

        public function key() 
        {
            $var = key($this->data);
            return $var;
        }

        public function next() 
        {
            $var = next($this->data);
            return $var;
        }

        public function valid()
        {
            $key = key($this->data);
            $var = (($key !== NULL) && ($key !== FALSE));
            return $var;
        }
    }

    /*
    ** autoloader class
    **
    ** this class implements an easy to use autoloader for classes used
    ** before they are defined …
    ** application type pathes are searched in reverse definition order before
    ** library type paths, which are searched in definition order
    ** simply destroying an autoloader-object removes it's path from the
    ** search-path
    **
    ** all applications and libraries are assumed to reside in their own
    ** subdirectories under either 
    ** $_SERVER['DOCUMENT_ROOT'] . 'include/'
    ** or
    ** dirname($_SERVER['SCRIPT_FILENAME']) . '/include/'
    ** where the used directory name corresponds with the namespace used by
    ** either application or library
    */
    class AutoLoader {
        const LIBRARY     = 0;
        const APPLICATION = 1;

        private $name;
        private $path;

        public function __construct($type, $name)
        {
            $path = $this->search_path($name);
            if($path === FALSE)
                throw new \ErrorException(
                        'Path for ' . ($type == self::LIBRARY ? 'library' : 'application') . ' »' . $name . '« not found!',
                        0, 2
                        );
            $this->path = $path . '/';
            $this->name = $name;
            switch($type)
            {
                case self::LIBRARY:
                    spl_autoload_register(array($this, 'autoload'), true, false);
                    break;
                case self::APPLICATION:
                    spl_autoload_register(array($this, 'autoload'), true, true);
                    break;
                default:
                    throw new \ErrorException(
                            'Unknown type »' . $type . '«!',
                            0, 2
                            );
            }
        }

        public function __destruct()
        {
            spl_autoload_unregister(array($this, 'autoload'));
        }

        private function search_path($name)
        {
            if(is_dir($ret = ($_SERVER['DOCUMENT_ROOT'] . 'include/' . $name)))
                return $ret;
            else if(is_dir($ret = (dirname($_SERVER['SCRIPT_FILENAME']) . '/include/' . $name)))
                return $ret;
            return FALSE;
        }

        private function autoload($class_name)
        {
            $arr = explode('\\', $class_name);
            if($arr[0] == $this->name)
            {
                unset($arr[0]);
                $path = $this->path . implode($arr, '/') . '.php';
                if(require_once $path)
                    return;
                else
                    throw new Exception("Unable to load $path.");
            }
        }
    }

    /*
    ** error-handler which converts runtime errors into exceptions
    */
    function exception_error_handler($errno, $errstr, $errfile, $errline)
    {
        printf("<pre>%016b %016b\n%s\n%s\n%d</pre>", $errno, error_reporting(), $errstr, $errfile, $errline);
        if (!(error_reporting() & $errno))
        {
            // This error code is not included in error_reporting
            return;
        }
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    /*
    ** install error-handler and autoloader for this library
    */
    set_error_handler("\\Tiger\\exception_error_handler");
    $TigerAutoLoader = new \Tiger\AutoLoader(AutoLoader::LIBRARY, 'Tiger');
}

?>
