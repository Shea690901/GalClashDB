<?php
namespace GalClash {
    // we make extensive use of the PDO database object
    use \PDO;
    use \Exception;

    class GCDB extends \Tiger\DB_PDO {

        public function __construct($engine, $host, $port, $dbname, $charset, $user, $password)
        {
            parent::__construct($engine, $host, $port, $dbname, $charset, $user, $password);
            $a_id = $this->get_ally_id('-');
            $s_id = $this->get_player_id('-');
            if(($a_id == -1) || ($s_id == -1))
                throw new Exception("Fehler in Datenbankstruktur");
            $this->nul_ally   = $a_id;
            $this->nul_player = $s_id;
        }

        public function __destruct()
        {
            parent::__destruct();
        }

        public function get_user_info($user)
        {
            $dbh  = $this->get_handle();
            $sth = $dbh->prepare('SELECT `m_id`, `s_id`, `a_id`, `allianz`, `leiter`, `admin`, `c_pwd`, `blocked`, `b_id`  FROM `V_user` NATURAL JOIN `allianzen` WHERE `name` = :user');
            try {
                $sth->bindParam(':user', $user);
                $sth->execute();
                $row = $sth->fetch();
            }
            catch(\Exception $e) {
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage(%s/%s/%s): '%s'", $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage: '%d'<br />\n", $e->getCode()));
            }
            return $row ? array(
                    'uid'     => $row->m_id,
                    'pid'     => $row->s_id,
                    'aid'     => $row->a_id,
                    'bid'     => $row->b_id,
                    'ally'    => $row->allianz,
                    'leader'  => $row->leiter,
                    'admin'   => $row->admin,
                    'c_pwd'   => $row->c_pwd,
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
            catch(Exception $e) {
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
            catch(Exception $e) {
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage(%s/%s/%s): '%s'", $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage: '%d'<br />\n", $e->getCode()));
            }
        }

        /*
        ** get_ally_id
        **
        ** in:
        ** - ally: name of ally to search for
        **
        ** out:
        ** returns id of ally if found, -1 otherwise
        */
        public function get_ally_id($ally)
        {
            $sth = $this->get_handle()->prepare("SELECT a_id FROM allianzen WHERE allianz = :ally");
            try {
                $sth->bindValue(":ally", $ally);
                $sth->execute();
            }
            catch(Exception $e) {
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage(%s/%s/%s): '%s'", $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage: '%d'<br />\n", $e->getCode()));
            }
            if($sth->rowCount() == 1)
                return (int) $sth->fetch(PDO::FETCH_OBJ)->a_id;
            return -1;
        }

        /*
        ** get_player_id
        **
        ** in:
        ** - name: name of player to search for
        **
        ** out:
        ** returns id of player if found, -1 otherwise
        */
        public function get_player_id($name)
        {
            $sth = $this->get_handle()->prepare("SELECT s_id FROM spieler WHERE name = :name");
            try {
                $sth->bindValue(":name", $name);
                $sth->execute();
            }
            catch(Exception $e) {
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage(%s/%s/%s): '%s'", $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage: '%d'<br />\n", $e->getCode()));
            }
            if($sth->rowCount() == 1)
                return (int) $sth->fetch(PDO::FETCH_OBJ)->s_id;
            return -1;
        }

        /*
        ** get_user_id
        **
        ** in:
        ** - name: name of user to search for
        **
        ** out:
        ** returns id of user if found, -1 otherwise
        */
        public function get_user_id($name)
        {
            $sth = $this->get_handle()->prepare("SELECT m_id FROM V_user WHERE name = :name");
            try {
                $sth->bindValue(":name", $name);
                $sth->execute();
            }
            catch(Exception $e) {
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage(%s/%s/%s): '%s'", $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage: '%d'<br />\n", $e->getCode()));
            }
            if($sth->rowCount() == 1)
                return (int) $sth->fetch(PDO::FETCH_OBJ)->m_id;
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
                catch(Exception $e) {
                    if(\DEBUG)
                    {
                        $ei = $sth->errorInfo();
                        throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage(%s/%s/%s): '%s'", $ei[0], $ei[1], $ei[2], $e->getMessage()));
                    }
                    else
                        throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage: '%d'<br />\n", $e->getCode()));
                }
                $this->ally_group = [];
                if($sth->rowCount() > 0)
                {
                    $rows = $sth->fetchAll(PDO::FETCH_OBJ);

                    foreach($rows as $row)
                        $this->ally_group[] = $row->allianz;
                }
            }
            return $this->ally_group;
        }

        /*
        ** returns leader of questioned ally as string
        */
        public function get_ally_leader($ally)
        {
            $dbh = $this->get_handle();

            $sth = $dbh->prepare("SELECT name FROM spieler JOIN allianzen on leiter_id = spieler.s_id WHERE allianz = :ally");
            try {
                $sth->bindValue(":ally", $ally);
                $sth->execute();
            }
            catch(Exception $e) {
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage(%s/%s/%s): '%s'", $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage: '%d'<br />\n", $e->getCode()));
            }
            if($sth->rowCount() > 0)
            {
                $rows = $sth->fetchAll(PDO::FETCH_OBJ);
                return $rows[0]->name;
            }
            return '-';
        }

        /*
        ** returns array of users in questioned ally
        **
        ** !!! omits oneself and leader of questioned ally !!!
        */
        public function get_ally_users($ally)
        {
            /* we weren't called with this argument yet */
            if(!isset($this->users[$ally]))
            {
                $dbh = $this->get_handle();

                $sth = $dbh->prepare("SELECT name, admin, blocked FROM V_user WHERE a_id = :a_id " .
                        "AND name != :name " .
                        "AND name != ( SELECT spieler.name FROM ( spieler JOIN allianzen on leiter_id = spieler.s_id ) WHERE spieler.a_id = :a_id ) " .
                        "ORDER BY name");

                try {
                    $sth->bindValue(":name", $_SESSION["user"]);
                    $sth->bindValue(":a_id", $this->get_ally_id($ally));
                    $sth->execute();
                }
                catch(Exception $e) {
                    if(\DEBUG)
                    {
                        $ei = $sth->errorInfo();
                        throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage(%s/%s/%s): '%s'", $ei[0], $ei[1], $ei[2], $e->getMessage()));
                    }
                    else
                        throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage: '%d'<br />\n", $e->getCode()));
                }

                $this->users[$ally] = [];
                if($sth->rowCount() > 0)
                {
                    $rows = $sth->fetchAll(PDO::FETCH_OBJ);
                    foreach($rows as $row)
                    {
                        $this->users[$ally][$row->name] = array(
                                'admin'   => $row->admin,
                                'blocked' => $row->blocked,
                                );
                    }
                }
            }
            return $this->users[$ally];
        }

        public function add_user($ally, $name, $pwd)
        {
            $dbh  = $this->get_handle();

            $a_id = $this->get_ally_id($ally);
            $s_id = $this->get_player_id($name);
            $m_id = $this->get_user_id($name);

            if($m_id != -1)
            {
                throw new Exception("User bereits eingetragen");
            }
            if($s_id == -1)
            {
                $sth1 = $dbh->prepare("INSERT INTO spieler (name, a_id) VALUES ( :name, :a_id )");
                $sth2 = $dbh->prepare("INSERT INTO user_pwd ( s_id, pwd ) VALUES ( ( SELECT s_id FROM spieler WHERE name = :name ), :pwd )");
            }
            else
            {
                $sth1 = $dbh->prepare("UPDATE spieler SET a_id = :a_id WHERE s_id = :s_id");
                $sth2 = $dbh->prepare("INSERT INTO user_pwd ( s_id, pwd ) VALUES ( :s_id, :pwd )");
            }
            try {
                $dbh->beginTransaction();

                $sth1->bindValue(":a_id", $a_id, PDO::PARAM_INT);
                $sth2->bindValue(":pwd", $pwd);
                if($s_id == -1)
                {
                    $sth1->bindValue(":name", $name);
                    $sth2->bindValue(":name", $name);
                }
                else
                {
                    $sth1->bindValue(":s_id", $s_id, PDO::PARAM_INT);
                    $sth2->bindValue(":s_id", $s_id, PDO::PARAM_INT);
                }
                $sth1->execute();
                $sth2->execute();

                $dbh->commit();
            }
            catch(Exception $e) {
                $dbh->rollBack();
                if(\DEBUG)
                {
                    $ei[0] = $sth1->errorInfo();
                    $ei[1] = $sth2->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage(%s/%s/%s)(%s/%s/%s):\n'%s'", $ei[0][0], $ei[0][1], $ei[0][2], $ei[1][0], $ei[1][1], $ei[1][2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage: '%d'<br />\n", $e->getCode()));
            }
        }

        public function del_user($name)
        {
            $dbh  = $this->get_handle();

            $m_id = $this->get_user_id($name);
            $s_id = $this->get_player_id($name);

            if($m_id == -1)
                throw new Exception("User nicht gefunden");
            if($s_id == -1)
                throw new Exception("Spieler nicht gefunden");

            $sth1 = $dbh->prepare("UPDATE spieler SET a_id = :a_id WHERE s_id = :s_id");
            $sth2 = $dbh->prepare("DELETE FROM user_pwd WHERE m_id = :m_id");
            try {
                $dbh->beginTransaction();

                $sth1->bindValue(":s_id", $s_id, PDO::PARAM_INT);
                $sth1->bindValue(":a_id", $this->nul_ally, PDO::PARAM_INT);
                $sth2->bindValue(":m_id", $m_id, PDO::PARAM_INT);
                $sth1->execute();
                $sth2->execute();

                $dbh->commit();
            }
            catch(PDOException $e) {
                $dbh->rollBack();
                if(\DEBUG)
                {
                    $ei[0] = $sth1->errorInfo();
                    $ei[1] = $sth2->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage(%s/%s/%s)(%s/%s/%s):\n'%s'", $ei[0][0], $ei[0][1], $ei[0][2], $ei[1][0], $ei[1][1], $ei[1][2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage: '%d'<br />\n", $e->getCode()));
            }
            return 0;
        }

        public function block_user($name, $from_id)
        {
            $dbh  = $this->get_handle();

            $info = $this->get_user_info($name);
            if($info == FALSE)
                throw new Exception("User nicht gefunden");
            if($info['bid'] != $this->nul_player)
                $from_id = $this->nul_player;

            $sth = $dbh->prepare("UPDATE user_pwd SET b_id = :b_id WHERE m_id = :m_id");
            try {
                $dbh->beginTransaction();

                $sth->bindValue(":b_id", $from_id, PDO::PARAM_INT);
                $sth->bindValue(":m_id", $info['uid'], PDO::PARAM_INT);
                $sth->execute();

                $dbh->commit();
            }
            catch(PDOException $e) {
                $dbh->rollBack();
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage(%s/%s/%s):\n'%s'", $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("Fehler bei Datenbankabfrage: '%d'<br />\n", $e->getCode()));
            }
            return;
        }
    } // class GCDB
} // namespace GalClash
?>
