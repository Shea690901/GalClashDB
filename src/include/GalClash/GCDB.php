<?php
namespace GalClash {
    class GCDB extends \Tiger\DB_PDO {

        public function __construct($engine, $host, $port, $dbname, $charset, $user, $password)
        {
            parent::__construct($engine, $host, $port, $dbname, $charset, $user, $password);
        }

        public function __destruct()
        {
            parent::__destruct();
        }

        public function get_user_info($user)
        {
            $dbh  = $this->get_handle();
            $stmt = $dbh->prepare('SELECT `m_id`, `allianz`, `leiter`, `admin`, `c_pwd`, `blocked` FROM `V_user` NATURAL JOIN `allianzen` WHERE `name` = :user');
            try {
                $stmt->bindParam(':user', $user);
                $stmt->execute();
                $row = $stmt->fetch();
            }
            catch(PDOException $e) {
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage(%s/%s/%s): '%s'", $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage: '%d'<br />\n", $e->getCode()));
            }
            return $row ? array(
                    'uid' => $row->m_id,
                    'allianz' => $row->allianz,
                    'leiter' => $row->leiter,
                    'admin' => $row->admin,
                    'c_pwd' => $row->c_pwd,
                    'blocked' => $row->blocked
                    ) : FALSE;
        }

        public function get_pwd_entry($uid)
        {
            $dbh  = $this->get_handle();
            $stmt = $dbh->prepare('SELECT `pwd` FROM `V_user` WHERE `m_id` = :uid');
            try {
                $stmt->bindParam(':uid', $uid);
                $stmt->execute();
                $row = $stmt->fetch();
            }
            catch(PDOException $e) {
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage(%s/%s/%s): '%s'", $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage: '%d'<br />\n", $e->getCode()));
            }
            return $row ? $row->pwd : FALSE;
        }

        public function update_passwd($uid, $pwd)
        {
            $dbh  = $this->get_handle();
            $stmt = $dbh->prepare('CALL `P_update_passwd`(:uid, :pwd)');
            try {
                $stmt->bindParam(':uid', $uid);
                $stmt->bindParam(':pwd', $pwd);
                $stmt->execute();
            }
            catch(PDOException $e) {
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage(%s/%s/%s): '%s'", $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage: '%d'<br />\n", $e->getCode()));
            }
        }

        public function get_allianz_id($allianz)
        {
            $sth = $this->dbh->prepare("SELECT a_id FROM allianzen WHERE allianz = :allianz");
            try {
                $sth->bindValue(":allianz", $allianz);
                $sth->execute();
            }
            catch(\PDOException $e) {
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage(%s/%s/%s): '%s'", $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage: '%d'<br />\n", $e->getCode()));
            }
            if($sth->rowCount() == 1)
                return $sth->fetch(\PDO::FETCH_OBJ)->a_id;
            return -1;
        }

        /*
        ** returns array of alliances within blocked group
        */
        public function get_ally_group()
        {
            /* we weren't called yet */
            if(!isset($this->ally_group))
            {
                $dbh = $this->get_handle();
                $sth = $dbh->prepare("SELECT allianz FROM V_blacklisted");

                try {
                    $sth->execute();
                }
                catch(\PDOException $e) {
                    \GalClash\error_message(sprintf("Fehler bei Datenbankabfrage: '%s'<br />\n", $e->getMessage()));
                }
                $this->ally_group = [];
                if($sth->rowCount() > 0)
                {
                    $rows = $sth->fetchAll(\PDO::FETCH_OBJ);

                    foreach($rows as $row)
                        $this->ally_group[] = $row->allianz;
                }
            }
            return $this->ally_group;
        }
    }
}

?>
