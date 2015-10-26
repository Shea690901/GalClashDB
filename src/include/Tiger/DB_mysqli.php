<?php

namespace Tiger {
    class DB_mysqli
    {
        private $dbh = null;

        public function __construct($host, $port, $dbname, $charset, $user, $password)
        {
            if (\DEBUG) {
                $this->host = $host;
                $this->port = $port;
                $this->dbname = $dbname;
                $this->user = $user;
            }
            $dbh = new \mysqli();
            if ($dbh->options(MYSQLI_INIT_COMMAND, 'SET NAMES '.$charset)) {
                if ($dbh->real_connect($host, $user, $password, $dbname, $port)) {
                    $this->dbh = $dbh;

                    return;
                }
            }
            if (DEBUG) {
                throw new DB_Exception(DB_Exception::DB_INACCESSABLE, sprintf('ConnectError(%d) "%s"', $dbh->errno, $dbh->error));
            } else {
                throw new DB_Exception(DB_Exception::DB_INACCESSABLE, sprintf('ConnectError(%d)', $dbh->errno));
            }
        }

        public function __destruct()
        {
            $this->dbh->close();
            unset($this->dbh);
        }

        public function get_handle()
        {
            return $this->dbh;
        }
    }
}
