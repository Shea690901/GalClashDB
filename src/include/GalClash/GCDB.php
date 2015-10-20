<?php
namespace GalClash {
    // we make extensive use of the PDO database object
    use \PDO;
    use \Exception;

    class GCDB extends \Tiger\DB_PDO {
        private $nul_ally;
        private $nul_player;

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

        public function get_nul_ally()   { return $this->nul_ally; }
        public function get_nul_player() { return $this->nul_player; }

        public function ally_has_access($ally)
        {
            $dbh  = $this->get_handle();
            if(is_string($ally))
                $ally = $this->get_ally_id($ally);
            if($ally <= 0)
                return FALSE;

            $sth = $dbh->prepare('SELECT 1 FROM `blacklisted` WHERE `a_id` = :a_id');
            try {
                $sth->bindParam(':a_id', $ally, PDO::PARAM_INT);
                $sth->execute();
            }
            catch(\Exception $e) {
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage(%s/%s/%s): '%s'", __FUNCTION__, $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
            }
            return ($sth->rowCount() == 1);
        }

        public function player_has_access($user)
        {
            $dbh  = $this->get_handle();
            if(is_string($user))
                $user = $this->get_player_id($user);
            if($user <= 0)
                return FALSE;

            $sth = $dbh->prepare('SELECT 1 FROM `V_user` WHERE `s_id` = :p_id');
            try {
                $sth->bindParam(':p_id', $user, PDO::PARAM_INT);
                $sth->execute();
            }
            catch(\Exception $e) {
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage(%s/%s/%s): '%s'", __FUNCTION__, $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
            }
            return ($sth->rowCount() == 1);
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
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage(%s/%s/%s): '%s'", __FUNCTION__, $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
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
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage(%s/%s/%s): '%s'", __FUNCTION__, $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
            }
            return $row ? $row->pwd : FALSE;
        }

        public function update_passwd($uid, $pwd)
        {
            $dbh  = $this->get_handle();
            $sth = $dbh->prepare('UPDATE `user_pwd` SET `pwd` = :pwd WHERE `m_id` = :uid');
            try {
                $sth->bindParam(':uid', $uid);
                $sth->bindParam(':pwd', $pwd);
                $sth->execute();
            }
            catch(Exception $e) {
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage(%s/%s/%s): '%s'", __FUNCTION__, $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
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
            $sth = $this->get_handle()->prepare("SELECT `a_id` FROM `allianzen` WHERE `allianz` = :ally");
            try {
                $sth->bindValue(":ally", $ally);
                $sth->execute();
            }
            catch(Exception $e) {
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage(%s/%s/%s): '%s'", __FUNCTION__, $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
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
            $sth = $this->get_handle()->prepare("SELECT `s_id` FROM `spieler` WHERE `name` = :name");
            try {
                $sth->bindValue(":name", $name);
                $sth->execute();
            }
            catch(Exception $e) {
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage(%s/%s/%s): '%s'", __FUNCTION__, $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
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
            $sth = $this->get_handle()->prepare("SELECT `m_id` FROM `user_pwd` NATURAL JOIN `spieler` WHERE `name` = :name");
            try {
                $sth->bindValue(":name", $name);
                $sth->execute();
            }
            catch(Exception $e) {
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage(%s/%s/%s): '%s'", __FUNCTION__, $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
            }
            if($sth->rowCount() == 1)
                return (int) $sth->fetch(PDO::FETCH_OBJ)->m_id;
            return -1;
        }

        /*
        ** returns array of alliances (ids) with access rights
        */
        public function get_ally_group_ids()
        {
            /* we weren't called yet */
            if(!isset($this->ally_group_ids))
            {
                $dbh = $this->get_handle();
                $sth = $dbh->prepare("SELECT `a_id` FROM `blacklisted`");

                try {
                    $sth->execute();
                }
                catch(Exception $e) {
                    if(\DEBUG)
                    {
                        $ei = $sth->errorInfo();
                        throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage(%s/%s/%s): '%s'", __FUNCTION__, $ei[0], $ei[1], $ei[2], $e->getMessage()));
                    }
                    else
                        throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
                }
                $this->ally_group = [];
                if($sth->rowCount() > 0)
                {
                    $rows = $sth->fetchAll(PDO::FETCH_OBJ);

                    foreach($rows as $row)
                        $this->ally_group_ids[] = $row->a_id;
                }
            }
            return $this->ally_group_ids;
        }

        /*
        ** returns array of alliances (names) with access rights
        */
        public function get_ally_group()
        {
            /* we weren't called yet */
            if(!isset($this->ally_group))
            {
                $dbh = $this->get_handle();
                $sth = $dbh->prepare("SELECT `allianz` FROM `V_blacklisted`");

                try {
                    $sth->execute();
                }
                catch(Exception $e) {
                    if(\DEBUG)
                    {
                        $ei = $sth->errorInfo();
                        throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage(%s/%s/%s): '%s'", __FUNCTION__, $ei[0], $ei[1], $ei[2], $e->getMessage()));
                    }
                    else
                        throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
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

            $sth = $dbh->prepare("SELECT `name` FROM `spieler` JOIN `allianzen` on `leiter_id` = `spieler`.`s_id` WHERE `allianz` = :ally");
            try {
                $sth->bindValue(":ally", $ally);
                $sth->execute();
            }
            catch(Exception $e) {
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage(%s/%s/%s): '%s'", __FUNCTION__, $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
            }
            if($sth->rowCount() > 0)
            {
                $rows = $sth->fetchAll(PDO::FETCH_OBJ);
                return $rows[0]->name;
            }
            return '-';
        }

        /*
        ** returns all members of questioned ally
        */
        public function get_ally_players($ally)
        {
            /* we weren't called with this argument yet */
            if(!isset($this->players[$ally]))
            {
                $dbh = $this->get_handle();

                $sth = $dbh->prepare("SELECT `name` FROM `spieler` WHERE `a_id` = :a_id ORDER BY `name`");

                try {
                    $sth->bindValue(":a_id", $this->get_ally_id($ally));
                    $sth->execute();
                }
                catch(Exception $e) {
                    if(\DEBUG)
                    {
                        $ei = $sth->errorInfo();
                        throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage(%s/%s/%s): '%s'", __FUNCTION__, $ei[0], $ei[1], $ei[2], $e->getMessage()));
                    }
                    else
                        throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
                }

                $this->players[$ally] = [];
                if($sth->rowCount() > 0)
                {
                    $rows = $sth->fetchAll(PDO::FETCH_OBJ);
                    foreach($rows as $row)
                        $this->players[$ally][] = $row->name;
                }
            }
            return $this->players[$ally];
        }

        /*
        ** returns array of users in questioned ally
        **    contains admin/blocked info
        **
        ** !!! omits oneself and leader of questioned ally !!!
        */
        public function get_ally_users($ally)
        {
            /* we weren't called with this argument yet */
            if(!isset($this->users[$ally]))
            {
                $dbh = $this->get_handle();

                $sth = $dbh->prepare("SELECT `name`, `admin`, `blocked` FROM `V_user` WHERE `a_id` = :a_id " .
                        "AND `name` != :name " .
                        "AND `name` != ( " .
                            "SELECT `spieler`.`name` FROM ( " .
                                "`spieler` JOIN `allianzen` on `leiter_id` = `spieler`.`s_id` ) " .
                            "WHERE `spieler`.`a_id` = :a_id ) " .
                        "ORDER BY `name`");

                try {
                    $sth->bindValue(":name", $_SESSION["user"]);
                    $sth->bindValue(":a_id", $this->get_ally_id($ally));
                    $sth->execute();
                }
                catch(Exception $e) {
                    if(\DEBUG)
                    {
                        $ei = $sth->errorInfo();
                        throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage(%s/%s/%s): '%s'", __FUNCTION__, $ei[0], $ei[1], $ei[2], $e->getMessage()));
                    }
                    else
                        throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
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

        /*
        ** returns ally which the player is a member of or FALSE if not found
        */
        public function get_player_ally($name)
        {
            $sth = $this->get_handle()->prepare("SELECT `allianz` FROM `allianzen` NATURAL JOIN `spieler` WHERE `name` = :name");
            try {
                $sth->bindValue(":name", $name);
                $sth->execute();
            }
            catch(Exception $e) {
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage(%s/%s/%s): '%s'", __FUNCTION__, $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
            }
            if($sth->rowCount() == 1)
                return $sth->fetch(PDO::FETCH_OBJ)->allianz;
            return FALSE;
        }

        /*
        ** general functions to add/change/delete allies without access privileges
        */
        public function new_ally($ally)
        {
            if($this->get_ally_id($ally) != -1)
                throw new Exception('Allianz bereits eingetragen');

            try {
                $sth = $this->get_handle()->prepare("INSERT INTO `allianzen` ( `allianz` ) VALUES ( :ally )");
                $sth->bindValue(":ally", $ally);
                $sth->execute();
            }
            catch(Exception $e) {
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage(%s/%s/%s): '%s'", __FUNCTION__, $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
            }

            return $this->get_ally_id($ally);
        }

        public function change_ally_leader($a_id, $p_id)
        {
            try {
                $sth = $this->get_handle()->prepare("UPDATE `allianzen` SET `leiter_id` = :p_id WHERE `a_id` = :a_id");
                $sth->bindValue(":p_id", $p_id, PDO::PARAM_INT);
                $sth->bindValue(":a_id", $a_id, PDO::PARAM_INT);
                $sth->execute();
            }
            catch(Exception $e) {
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage(%s/%s/%s): '%s'", __FUNCTION__, $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
            }
        }

        /*
        ** general functions to add/change/delete players without access privileges
        */
        public function new_player($name, $a_id)
        {
            if($this->get_player_id($name) != -1)
                throw new Exception('Spieler bereits eingetragen');

            try {
                $sth = $this->get_handle()->prepare("INSERT INTO `spieler` ( `name`, `a_id` ) VALUES ( :name, :a_id )");
                $sth->bindValue(":name", $name);
                $sth->bindValue(":a_id", $a_id, PDO::PARAM_INT);
                $sth->execute();
            }
            catch(Exception $e) {
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage(%s/%s/%s): '%s'", __FUNCTION__, $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
            }

            return $this->get_player_id($name);
        }

        public function change_player_ally($p_id, $a_id, $trans = TRUE)
        {
            $dbh = $this->get_handle();

            $sth1 = $dbh->prepare("UPDATE `allianzen` SET `leiter_id` = :nul WHERE `leiter_id` = :p_id");
            $sth2 = $dbh->prepare("UPDATE `spieler` SET `a_id` = :a_id WHERE `s_id` = :p_id");
            try {
                if($trans)
                    $dbh->beginTransaction();

                $sth1->bindValue(":p_id", $p_id, PDO::PARAM_INT);
                $sth1->bindValue(":nul", $this->nul_player, PDO::PARAM_INT);
                $sth2->bindValue(":a_id", $a_id, PDO::PARAM_INT);
                $sth2->bindValue(":p_id", $p_id, PDO::PARAM_INT);
                $sth1->execute();
                $sth2->execute();

                if($trans)
                    $dbh->commit();
            }
            catch(Exception $e) {
                if($trans)
                    $dbh->rollBack();
                if(\DEBUG)
                {
                    $ei[0] = $sth1->errorInfo();
                    $ei[1] = $sth2->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s\nFehler bei Datenbankabfrage(%s/%s/%s)(%s/%s/%s):\n'%s'", __FUNCTION__, $ei[0][0], $ei[0][1], $ei[0][2], $ei[1][0], $ei[1][1], $ei[1][2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
            }
        }

        /*
        ** general functions to add/change/delete allies with access privileges
        */
        public function add_ally($ally, $leader, $pwd)
        {
            $dbh = $this->get_handle();

            $a_id = $this->get_ally_id($ally);
            $p_id = $this->get_player_id($leader);
            $u_id = $this->get_user_id($leader);

            $sth1 = $dbh->prepare("INSERT INTO `blacklisted` ( `a_id` ) VALUES ( :a_id )");
            $sth2 = $dbh->prepare("INSERT INTO `user_pwd` ( `s_id`, `b_id` ) SELECT `s_id`, :p_id FROM `spieler` " .
                    "WHERE `a_id` = :a_id AND NOT EXISTS ( SELECT 1 FROM `user_pwd` WHERE `user_pwd`.`s_id` = `spieler`.`s_id` )");

            try {
                $dbh->beginTransaction();

                if($a_id == -1)
                    $a_id = $this->new_ally($ally);
                $ret = $this->add_user($ally, $leader, $pwd, FALSE);
                $p_id = $this->get_player_id($leader);
                $this->change_ally_leader($a_id, $p_id);

                $sth1->bindValue(":a_id", $a_id, PDO::PARAM_INT);
                $sth2->bindValue(":a_id", $a_id, PDO::PARAM_INT);
                $sth2->bindValue(":p_id", $p_id, PDO::PARAM_INT);
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
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s\nFehler bei Datenbankabfrage(%s/%s/%s)(%s/%s/%s):\n'%s'", __FUNCTION__, $ei[0][0], $ei[0][1], $ei[0][2], $ei[1][0], $ei[1][1], $ei[1][2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
            }
            return $ret;
        }

        public function del_ally($ally)
        {
            $dbh = $this->get_handle();
            $a_id = $this->get_ally_id($ally);

            $sth = $dbh->prepare("DELETE FROM `blacklisted` WHERE `a_id` = :a_id");
            try {
                $dbh->beginTransaction();

                $players = $this->get_ally_players($ally);
                foreach($players as $name)
                    $this->del_user($name, FALSE, FALSE);  // do normal del_user without change ally and local transaction(!!)
                $sth->bindValue(":a_id", $a_id, PDO::PARAM_INT);
                $sth->execute();

                $dbh->commit();
            }
            catch(Exception $e) {
                $dbh->rollBack();
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage(%s/%s/%s): '%s'", __FUNCTION__, $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
            }
            return count($players);
        }

        public function change_leader($ally, $name)
        {
            $dbh  = $this->get_handle();

            $a_id = $this->get_ally_id($ally);
            $p_id = $this->get_player_id($name);

            try {
                $dbh->beginTransaction();

                $this->admin_user($name, 1);
                $this->change_ally_leader($a_id, $p_id);

                $dbh->commit();
            }
            catch(Exception $e) {
                $dbh->rollBack();
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage(%s/%s/%s): '%s'", __FUNCTION__, $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
            }
        }

        /*
        ** general functions to add/change/delete players with access privileges (user)
        */
        public function add_user($ally, $name, $pwd, $trans = TRUE)
        {
            $dbh  = $this->get_handle();

            $a_id = $this->get_ally_id($ally);
            $p_id = $this->get_player_id($name);
            $u_id = $this->get_user_id($name);

            $leader = $this->get_ally_leader($ally2 = $this->get_player_ally($name));
            if(($ally != $ally2) && ($leader == $name) && $this->ally_has_access($ally2))
                throw new Exception(sprintf("%s ist bereits Leiter von %s", $name, $ally2));
            $sth = $dbh->prepare("INSERT INTO `user_pwd` ( `s_id`, `pwd` ) VALUES ( :p_id, :pwd )");
            try {
                if($trans)
                    $dbh->beginTransaction();

                if($p_id == -1)
                    $p_id = $this->new_player($name, $a_id);
                else
                    $this->change_player_ally($p_id, $a_id, FALSE);

                if($u_id == -1)
                {
                    $sth->bindValue(":p_id", $p_id, PDO::PARAM_INT);
                    $sth->bindValue(":pwd", $pwd);
                    $sth->execute();
                }

                if($trans)
                    $dbh->commit();
            }
            catch(Exception $e) {
                if($trans)
                    $dbh->rollBack();
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage(%s/%s/%s): '%s'", __FUNCTION__, $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
            }
            return $u_id;
        }

        public function del_user($name, $c_ally = TRUE, $trans = TRUE)
        {
            $dbh = $this->get_handle();

            try {
                if($trans)
                    $dbh->beginTransaction();

                $this->admin_user($name, 0);
                if($c_ally)
                    $this->change_player_ally($this->get_player_id($name), $this->get_nul_ally(), FALSE);

                if($trans)
                    $dbh->commit();
            }
            catch(Exception $e) {
                if($trans)
                    $dbh->rollBack();
                if(\DEBUG)
                {
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage: '%s'", __FUNCTION__, $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
            }
        }

        public function block_user($name, $from_id)
        {
            $info = $this->get_user_info($name);
            if($info == FALSE)
                throw new Exception("User nicht gefunden");
            if($info['bid'] != $this->nul_player)
                $from_id = $this->nul_player;

            $sth = $this->get_handle()->prepare("UPDATE `user_pwd` SET `b_id` = :b_id WHERE `m_id` = :u_id");
            try {
                $sth->bindValue(":b_id", $from_id, PDO::PARAM_INT);
                $sth->bindValue(":u_id", $info['uid'], PDO::PARAM_INT);
                $sth->execute();
            }
            catch(PDOException $e) {
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage(%s/%s/%s): '%s'", __FUNCTION__, $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
            }
            return;
        }

        public function admin_user($name, $force = -1)
        {
            $info = $this->get_user_info($name);
            if($info == FALSE)
                throw new Exception("User nicht gefunden");
            if(($info['bid'] != $this->nul_player) && ($force == -1))
                throw new Exception("User ist gesperrt");

            $sth = $this->get_handle()->prepare("UPDATE `user_pwd` SET `admin` = :admin WHERE `m_id` = :u_id");
            try {
                if($force == -1)
                    $sth->bindValue(":admin", 1 - $info['admin'], PDO::PARAM_INT);
                else
                    $sth->bindValue(":admin", $force ? 1 : 0, PDO::PARAM_INT);
                $sth->bindValue(":u_id", $info['uid'], PDO::PARAM_INT);
                $sth->execute();
            }
            catch(PDOException $e) {
                if(\DEBUG)
                {
                    $ei = $sth->errorInfo();
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage(%s/%s/%s): '%s'", __FUNCTION__, $ei[0], $ei[1], $ei[2], $e->getMessage()));
                }
                else
                    throw new \Tiger\DB_Exception(\Tiger\DB_Exception::DB_EXECUTION_ERROR, sprintf("%s:\nFehler bei Datenbankabfrage: '%d'<br />\n", __FUNCTION__, $e->getCode()));
            }
            return;
        }

    } // class GCDB
} // namespace GalClash
?>
