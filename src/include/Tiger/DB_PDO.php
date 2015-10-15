<?php
namespace Tiger {
    use \PDO;       // this is an abstraction for the PDO database object

    class DB_PDO {
        private $dbh;

        function __construct($engine, $host, $port, $dbname, $charset, $user, $password)
        {
            $dsn = $engine . ':host=' . $host . ';' .
                ($port == 0 ? '' : 'port=' . $port . ';') .
                'dbname=' . $dbname .';charset=' . $charset;
            if(\DEBUG)
            {
                $this->dsn = $dsn;
                $this->user = $user;
            }
            $options = array(
                PDO::ATTR_PERSISTENT => TRUE,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ); 
            try {
                $this->dbh = new PDO($dsn, $user, $password, $options);
            }
            catch(\Exception $e) {
                if(DEBUG)
                    throw new DB_Exception(DB_Exception::DB_INACCESSABLE, sprintf('Exception(%s) for "%s"', $e->getMessage(), $dsn));
                else
                    throw new DB_Exception(DB_Exception::DB_INACCESSABLE, NULL, $e);
            }
        }

        public function __destruct()
        {
            unset($this->dbh);
        }

        public function get_handle()
        {
            return $this->dbh;
        }
    }
}

?>
