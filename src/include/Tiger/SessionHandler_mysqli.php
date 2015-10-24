<?php

namespace Tiger {
    class SessionHandler_mysqli implements \SessionHandlerInterface
    {
        private $savePath;
        private $db;

        public function __construct($host, $port, $user, $password)
        {
            $this->host = $host;
            $this->port = $port;
            $this->user = $user;
            $this->pwd = $password;
        }

        public function open($savePath, $sessionName)
        {
            try {
                $this->db = new DB_mysqli($this->host, $this->port, 'PHP_SESSION_STORE', 'utf8', $this->user, $this->pwd);
            } catch (DB_Exception $e) {
                printf('<pre>%s</pre>', $e->getMessage());
                die;
            }

            return true;
        }

        public function close()
        {
            unset($this->db);

            return true;
        }

        public function read($id)
        {
            $dbh = $this->db->get_handle();

            if ($stmt = $dbh->prepare('CALL `session_store_get`(?, ?);')) {
                if ($stmt->bind_param('ss', $this->user, $id)) {
                    if ($stmt->execute()) {
                        if ($stmt->bind_result($res)) {
                            if (($ret = $stmt->fetch()) || ($ret === null)) {
                                $stmt->close();

                                return $res;
                            }
                        }
                    }
                }
            }
            if (DEBUG) {
                printf('<pre>ConnectError(%d) "%s"</pre>', $dbh->errno, $dbh->error);
            } else {
                printf('<pre>ConnectError(%d)</pre>', $dbh->errno);
            }
            die;
        }

        public function write($id, $data)
        {
            $dbh = $this->db->get_handle();

            if ($stmt = $dbh->prepare('CALL `session_store_set`(?, ?, ?);')) {
                if ($stmt->bind_param('sss', $this->user, $id, $data)) {
                    if ($stmt->execute()) {
                        $stmt->close();

                        return true;
                    }
                }
            }
            if (DEBUG) {
                printf('<pre>ConnectError(%d) "%s"</pre>', $dbh->errno, $dbh->error);
            } else {
                printf('<pre>ConnectError(%d)</pre>', $dbh->errno);
            }
            $stmt->close();

            return false;
        }

        public function destroy($id)
        {
            $dbh = $this->db->get_handle();

            if ($stmt = $dbh->prepare('CALL `session_store_destroy`(?, ?);')) {
                if ($stmt->bind_param('ss', $this->user, $id)) {
                    if ($stmt->execute()) {
                        $stmt->close();

                        return true;
                    }
                }
            }
            if (DEBUG) {
                printf('<pre>ConnectError(%d) "%s"</pre>', $dbh->errno, $dbh->error);
            } else {
                printf('<pre>ConnectError(%d)</pre>', $dbh->errno);
            }
            $stmt->close();

            return false;
        }

        public function gc($maxlifetime)
        {
            $dbh = $this->db->get_handle();

            if ($stmt = $dbh->prepare('CALL `session_store_gc`(?, ?);')) {
                if ($stmt->bind_param('si', $this->user, $maxlifetime)) {
                    if ($stmt->execute()) {
                        $stmt->close();

                        return true;
                    }
                }
            }
            if (DEBUG) {
                printf('<pre>ConnectError(%d) "%s"</pre>', $dbh->errno, $dbh->error);
            } else {
                printf('<pre>ConnectError(%d)</pre>', $dbh->errno);
            }
            $stmt->close();

            return false;
        }
    }
}
