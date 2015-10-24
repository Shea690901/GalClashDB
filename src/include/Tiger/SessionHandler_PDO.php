<?php

namespace Tiger {
    class SessionHandler_PDO implements \SessionHandlerInterface
    {
        private $savePath;
        private $db;

        public function __construct($engine, $host, $port, $user, $password)
        {
            $this->engine = $engine;
            $this->host = $host;
            $this->port = $port;
            $this->user = $user;
            $this->pwd = $password;
        }

        public function open($savePath, $sessionName)
        {
            try {
                $this->db = new DB_PDO($this->engine, $this->host, $this->port, 'PHP_SESSION_STORE', 'utf8', $this->user, $this->pwd);
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

            try {
                $stmt = $dbh->prepare('CALL `session_store_get`(:user, :id);');
                $stmt->bindValue('user', $this->user);
                $stmt->bindValue('id', $id);
                $stmt->execute();
            } catch (\PDOException $e) {
                if ($e->getCode() != '42000') {
                    printf('<pre>%s</pre>', $e->getMessage());
                    die;
                }
            }
            $row = $stmt->fetch(\PDO::FETCH_OBJ);

            return ($row === false ? '' : $row->data);
        }

        public function write($id, $data)
        {
            $dbh = $this->db->get_handle();

            try {
                $stmt = $dbh->prepare('CALL `session_store_set`(:user, :id, :data);');
                $stmt->bindValue('user', $this->user);
                $stmt->bindValue('id', $id);
                $stmt->bindValue('data', $data);
                $stmt->execute();
            } catch (\PDOException $e) {
                printf('<pre>%s</pre>', $e->getMessage());

                return false;
            }

            return true;
        }

        public function destroy($id)
        {
            $dbh = $this->db->get_handle();

            try {
                $stmt = $dbh->prepare('CALL `session_store_destroy`(:user, :id);');
                $stmt->bindValue('user', $this->user);
                $stmt->bindValue('id', $id);
                $stmt->execute();
            } catch (\PDOException $e) {
                printf('<pre>%s</pre>', $e->getMessage());

                return false;
            }

            return true;
        }

        public function gc($maxlifetime)
        {
            $dbh = $this->db->get_handle();

            try {
                $stmt = $dbh->prepare('CALL `session_store_gc`(:user, :maxlifetime);');
                $stmt->bindValue('user', $this->user);
                $stmt->bindValue('maxlifetime', $maxlifetime);
                $stmt->execute();
            } catch (\PDOException $e) {
                printf('<pre>%s</pre>', $e->getMessage());

                return false;
            }

            return true;
        }
    }
}
