-- Adminer 4.2.1 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DELIMITER ;;

DROP PROCEDURE IF EXISTS `IP_optimize_tables`;;
CREATE PROCEDURE `IP_optimize_tables`()
BEGIN
    DECLARE done int default false;
    DECLARE t varchar(64);
    DECLARE c cursor for SELECT `TABLE_NAME` FROM `IV_tables`;
    DECLARE continue handler FOR not found SET done = TRUE;

    Open c;

    Ol: loop
        Fetch c into t;
        IF done THEN
            Leave ol;
        END IF;
        SET @s = concat('Optimize table ', t);
        PREPARE stm FROM @s;
        EXECUTE stm;
        DEALLOCATE PREPARE stm;
    END loop;

    Close c;
END;;

DROP PROCEDURE IF EXISTS `IP_rename_quest-guest`;;
CREATE PROCEDURE `IP_rename_quest-guest`()
BEGIN
    START TRANSACTION;

    INSERT INTO I_name_clashes ( s_id, e_s_id )
        SELECT spieler.s_id, XXXX.s_id FROM spieler JOIN spieler AS XXXX
                ON spieler.name = concat( 'g', substr( XXXX.name, 2 ) )
            WHERE XXXX.name regexp '^quest[0-9]{6}$';
    SELECT * FROM I_name_clashes;
    DELETE FROM I_name_clashes;

    INSERT INTO I_new_names ( s_id, name ) SELECT s_id, concat( 'g', substr( name, 2 ) ) FROM spieler WHERE name regexp '^quest[0-9]{6}$';
    SELECT * FROM I_new_names;
    DELETE FROM I_new_names;

    COMMIT;
END;;

DROP PROCEDURE IF EXISTS `P_add_allianz`;;
CREATE PROCEDURE `P_add_allianz`(OUT `a_id` int(11), IN `allianz` varchar(20) CHARACTER SET 'utf8')
BEGIN
   DECLARE s varchar(256) ;
   DECLARE a varchar(20);

   SET @a = trim(allianz);
   SET @s = concat("INSERT INTO allianzen ( allianz ) VALUES ( '", @a, "' )");
   PREPARE stm FROM @s;
   EXECUTE stm;
   DEALLOCATE PREPARE stm;
   
   CALL P_get_a_id (a_id, @a) ;
END;;

DROP PROCEDURE IF EXISTS `P_add_spieler`;;
CREATE PROCEDURE `P_add_spieler`(OUT `s_id` int(11), IN `name` varchar(20) CHARACTER SET 'utf8', IN `a_id` int(11))
BEGIN
   DECLARE s varchar(256) ;
   DECLARE n varchar(20);

   SET @n = trim(name);
   SET @s = concat("INSERT INTO spieler ( name, a_id ) VALUES ( '", @n, "', ", a_id, ")");

   PREPARE stm FROM @s;
   EXECUTE stm;
   DEALLOCATE PREPARE stm;
   
   CALL P_get_s_id (s_id, @n);
END;;

DROP PROCEDURE IF EXISTS `P_get_a_id`;;
CREATE PROCEDURE `P_get_a_id`(OUT `a_id` int(11), IN `allianz` varchar(20) CHARACTER SET 'utf8')
BEGIN
   DECLARE s varchar(256);
   DECLARE a varchar(20);
   DECLARE id int;

   SET @id = NULL;
   SET @a = trim(allianz);
   SET @s = concat("SELECT `a_id` INTO @`id` FROM `allianzen` WHERE `allianz` = '", @a, "'");

   PREPARE stm FROM @s;
   EXECUTE stm;
   DEALLOCATE PREPARE stm;
   SET a_id = @id ;
END;;

DROP PROCEDURE IF EXISTS `P_get_s_id`;;
CREATE PROCEDURE `P_get_s_id`(OUT `s_id` int(11), IN `name` varchar(20) CHARACTER SET 'utf8')
BEGIN
   DECLARE s varchar(256);
   DECLARE n varchar(20);
   DECLARE id int;

   SET @id = NULL;
   SET @n = trim(name);
   SET @s = concat("SELECT `s_id` INTO @`id` FROM `spieler` WHERE `name` = '", @n, "'");

   PREPARE stm FROM @s;
   EXECUTE stm;
   DEALLOCATE PREPARE stm;
   SET s_id = @id ;
END;;

DROP PROCEDURE IF EXISTS `P_set_leiter`;;
CREATE PROCEDURE `P_set_leiter`(IN `allianz` varchar(20) CHARACTER SET 'utf8', IN `name` varchar(20) CHARACTER SET 'utf8')
BEGIN
   DECLARE s varchar(256);
   DECLARE s_id int default NULL;
   DECLARE a_id int default NULL;

   START TRANSACTION;

   CALL P_get_a_id (@a_id, allianz);
   IF @a_id IS NULL THEN
      CALL P_add_allianz (@a_id, allianz);
   END IF;

   CALL P_get_s_id (@s_id, name);
   IF @s_id IS NULL THEN
      CALL P_add_spieler (@s_id, name, @a_id);
   ELSE
      SET @s = concat("UPDATE spieler SET a_id = ", @a_id, " WHERE s_id = ",  @s_id);
      PREPARE stm FROM @s;
      EXECUTE stm;
      DEALLOCATE PREPARE stm;
   END IF;

   SET @s = concat("UPDATE allianzen SET leiter_id = ", @s_id, " WHERE a_id = ",  @a_id);
   PREPARE stm FROM @s;
   EXECUTE stm;
   DEALLOCATE PREPARE stm;

   COMMIT;
END;;

DELIMITER ;

DROP TABLE IF EXISTS `allianzen`;
CREATE TABLE `allianzen` (
  `a_id` int(11) NOT NULL AUTO_INCREMENT,
  `allianz` varchar(20) DEFAULT NULL,
  `leiter_id` int(11) DEFAULT '0',
  PRIMARY KEY (`a_id`),
  UNIQUE KEY `name` (`allianz`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `allianzen` (`a_id`, `allianz`, `leiter_id`) VALUES
(1,	'-',	1);

DROP TABLE IF EXISTS `blacklisted`;
CREATE TABLE `blacklisted` (
  `a_id` int(11) NOT NULL,
  KEY `a_id` (`a_id`),
  CONSTRAINT `blacklisted_ibfk_1` FOREIGN KEY (`a_id`) REFERENCES `allianzen` (`a_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `coords`;
CREATE TABLE `coords` (
  `c_id` int(11) NOT NULL AUTO_INCREMENT,
  `gal` int(3) DEFAULT NULL,
  `sys` int(4) DEFAULT NULL,
  `pla` int(2) DEFAULT NULL,
  `s_id` int(11) DEFAULT NULL,
  `freigegeben` int(11) DEFAULT NULL,
  `farm_von` int(11) DEFAULT NULL,
  PRIMARY KEY (`c_id`),
  UNIQUE KEY `gal_sys_pla` (`gal`,`sys`,`pla`),
  KEY `s_id` (`s_id`),
  KEY `farm_von` (`farm_von`),
  KEY `freigegeben` (`freigegeben`),
  CONSTRAINT `coords_ibfk_1` FOREIGN KEY (`s_id`) REFERENCES `spieler` (`s_id`),
  CONSTRAINT `coords_ibfk_2` FOREIGN KEY (`freigegeben`) REFERENCES `spieler` (`s_id`),
  CONSTRAINT `coords_ibfk_3` FOREIGN KEY (`farm_von`) REFERENCES `spieler` (`s_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP VIEW IF EXISTS `IV_tables`;
CREATE TABLE `IV_tables` (`TABLE_CATALOG` varchar(512), `TABLE_SCHEMA` varchar(64), `TABLE_NAME` varchar(64), `TABLE_TYPE` varchar(64), `ENGINE` varchar(64), `VERSION` bigint(21) unsigned, `ROW_FORMAT` varchar(10), `TABLE_ROWS` bigint(21) unsigned, `AVG_ROW_LENGTH` bigint(21) unsigned, `DATA_LENGTH` bigint(21) unsigned, `MAX_DATA_LENGTH` bigint(21) unsigned, `INDEX_LENGTH` bigint(21) unsigned, `DATA_FREE` bigint(21) unsigned, `AUTO_INCREMENT` bigint(21) unsigned, `CREATE_TIME` datetime, `UPDATE_TIME` datetime, `CHECK_TIME` datetime, `TABLE_COLLATION` varchar(32), `CHECKSUM` bigint(21) unsigned, `CREATE_OPTIONS` varchar(255), `TABLE_COMMENT` varchar(2048));


DROP TABLE IF EXISTS `I_name_clashes`;
CREATE TABLE `I_name_clashes` (
  `s_id` int(11) DEFAULT NULL,
  `e_s_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DELIMITER ;;

CREATE TRIGGER `IT_BI_I_name_clashes` BEFORE INSERT ON `I_name_clashes` FOR EACH ROW
update coords set coords.s_id = NEW.s_id where coords.s_id = NEW.e_s_id;;

CREATE TRIGGER `IT_BD_I_name_clashes` BEFORE DELETE ON `I_name_clashes` FOR EACH ROW
delete from spieler where spieler.s_id = OLD.e_s_id;;

DELIMITER ;

DROP TABLE IF EXISTS `I_new_names`;
CREATE TABLE `I_new_names` (
  `s_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DELIMITER ;;

CREATE TRIGGER `IT_BD_new_names` BEFORE DELETE ON `I_new_names` FOR EACH ROW
update spieler set spieler.name = OLD.name where spieler.s_id = OLD.s_id;;

DELIMITER ;

DROP TABLE IF EXISTS `spieler`;
CREATE TABLE `spieler` (
  `s_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `a_id` int(11) DEFAULT NULL,
  `comment` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`s_id`),
  UNIQUE KEY `name` (`name`),
  KEY `a_id` (`a_id`),
  CONSTRAINT `spieler_ibfk_1` FOREIGN KEY (`a_id`) REFERENCES `allianzen` (`a_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `spieler` (`s_id`, `name`, `a_id`, `comment`) VALUES
(1,	'-',	1,	'NULL');

DROP TABLE IF EXISTS `user_pwd`;
CREATE TABLE `user_pwd` (
  `m_id` int(11) NOT NULL AUTO_INCREMENT,
  `s_id` int(11) NOT NULL,
  `pwd` varchar(256) NOT NULL,
  `urlaub` date NOT NULL DEFAULT '0000-00-00',
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  `c_pwd` tinyint(1) NOT NULL DEFAULT '1',
  `b_id` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`m_id`),
  UNIQUE KEY `s_id` (`s_id`),
  KEY `b_id` (`b_id`),
  CONSTRAINT `user_pwd_ibfk_1` FOREIGN KEY (`s_id`) REFERENCES `spieler` (`s_id`),
  CONSTRAINT `user_pwd_ibfk_2` FOREIGN KEY (`b_id`) REFERENCES `spieler` (`s_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP VIEW IF EXISTS `V_admins`;
CREATE TABLE `V_admins` (`s_id` int(11), `name` varchar(20));


DROP VIEW IF EXISTS `V_allianzen_internal`;
CREATE TABLE `V_allianzen_internal` (`a_id` int(11), `allianz` varchar(20), `leiter_id` int(11), `leiter` varchar(20));


DROP VIEW IF EXISTS `V_blacklisted`;
CREATE TABLE `V_blacklisted` (`a_id` int(11), `allianz` varchar(20));


DROP VIEW IF EXISTS `V_check_allianzen`;
CREATE TABLE `V_check_allianzen` (`a_id` int(11), `allianz` varchar(20), `leiter_id` int(11));


DROP VIEW IF EXISTS `V_check_spieler`;
CREATE TABLE `V_check_spieler` (`s_id` int(11), `name` varchar(20), `a_id` int(11));


DROP VIEW IF EXISTS `V_check_user`;
CREATE TABLE `V_check_user` (`m_id` int(11), `name` varchar(20), `a_id` int(11), `pwd` varchar(256), `admin` tinyint(1), `leiter` varchar(1), `c_pwd` tinyint(1), `urlaub` date, `blocked` varchar(20));


DROP VIEW IF EXISTS `V_leiter_internal`;
CREATE TABLE `V_leiter_internal` (`s_id` int(11), `name` varchar(20), `a_id` int(11), `allianzen.a_id` int(11), `allianz` varchar(20), `leiter_id` int(11));


DROP VIEW IF EXISTS `V_spieler`;
CREATE TABLE `V_spieler` (`c_id` int(11), `s_id` int(11), `a_id` int(11), `name` varchar(20), `allianz` varchar(20), `gal` int(3), `sys` int(4), `pla` int(2), `farm` varchar(20), `freigegeben` varchar(20));


DROP VIEW IF EXISTS `V_spieler_alli`;
CREATE TABLE `V_spieler_alli` (`s_id` int(11), `a_id` int(11), `name` varchar(20), `allianz` varchar(20));


DROP VIEW IF EXISTS `V_spieler_internal`;
CREATE TABLE `V_spieler_internal` (`s_id` int(11), `name` varchar(20), `a_id` int(11), `allianz` varchar(20), `leiter` varchar(1));


DROP VIEW IF EXISTS `V_user`;
CREATE TABLE `V_user` (`m_id` int(11), `name` varchar(20), `a_id` int(11), `pwd` varchar(256), `admin` tinyint(1), `leiter` varchar(1), `c_pwd` tinyint(1), `urlaub` date, `blocked` varchar(20));


DROP TABLE IF EXISTS `IV_tables`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `IV_tables` AS select `information_schema`.`tables`.`TABLE_CATALOG` AS `TABLE_CATALOG`,`information_schema`.`tables`.`TABLE_SCHEMA` AS `TABLE_SCHEMA`,`information_schema`.`tables`.`TABLE_NAME` AS `TABLE_NAME`,`information_schema`.`tables`.`TABLE_TYPE` AS `TABLE_TYPE`,`information_schema`.`tables`.`ENGINE` AS `ENGINE`,`information_schema`.`tables`.`VERSION` AS `VERSION`,`information_schema`.`tables`.`ROW_FORMAT` AS `ROW_FORMAT`,`information_schema`.`tables`.`TABLE_ROWS` AS `TABLE_ROWS`,`information_schema`.`tables`.`AVG_ROW_LENGTH` AS `AVG_ROW_LENGTH`,`information_schema`.`tables`.`DATA_LENGTH` AS `DATA_LENGTH`,`information_schema`.`tables`.`MAX_DATA_LENGTH` AS `MAX_DATA_LENGTH`,`information_schema`.`tables`.`INDEX_LENGTH` AS `INDEX_LENGTH`,`information_schema`.`tables`.`DATA_FREE` AS `DATA_FREE`,`information_schema`.`tables`.`AUTO_INCREMENT` AS `AUTO_INCREMENT`,`information_schema`.`tables`.`CREATE_TIME` AS `CREATE_TIME`,`information_schema`.`tables`.`UPDATE_TIME` AS `UPDATE_TIME`,`information_schema`.`tables`.`CHECK_TIME` AS `CHECK_TIME`,`information_schema`.`tables`.`TABLE_COLLATION` AS `TABLE_COLLATION`,`information_schema`.`tables`.`CHECKSUM` AS `CHECKSUM`,`information_schema`.`tables`.`CREATE_OPTIONS` AS `CREATE_OPTIONS`,`information_schema`.`tables`.`TABLE_COMMENT` AS `TABLE_COMMENT` from `information_schema`.`tables` where ((`information_schema`.`tables`.`TABLE_SCHEMA` = 'tigersql1') and (`information_schema`.`tables`.`TABLE_TYPE` = 'base table'));

DROP TABLE IF EXISTS `V_admins`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `V_admins` AS select `spieler`.`s_id` AS `s_id`,`spieler`.`name` AS `name` from `spieler` where (exists(select 1 from (`allianzen` join `user_pwd`) where ((`allianzen`.`leiter_id` = `spieler`.`s_id`) and (`user_pwd`.`s_id` = `spieler`.`s_id`))) or exists(select 1 from `user_pwd` where ((`user_pwd`.`s_id` = `spieler`.`s_id`) and (`user_pwd`.`admin` = 1))));

DROP TABLE IF EXISTS `V_allianzen_internal`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `V_allianzen_internal` AS select `allianzen`.`a_id` AS `a_id`,`allianzen`.`allianz` AS `allianz`,`allianzen`.`leiter_id` AS `leiter_id`,`spieler`.`name` AS `leiter` from (`allianzen` left join `spieler` on((`allianzen`.`leiter_id` = `spieler`.`s_id`)));

DROP TABLE IF EXISTS `V_blacklisted`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `V_blacklisted` AS select `allianzen`.`a_id` AS `a_id`,`allianzen`.`allianz` AS `allianz` from `allianzen` where exists(select 1 from `blacklisted` where (`allianzen`.`a_id` = `blacklisted`.`a_id`));

DROP TABLE IF EXISTS `V_check_allianzen`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `V_check_allianzen` AS select `allianzen`.`a_id` AS `a_id`,`allianzen`.`allianz` AS `allianz`,`allianzen`.`leiter_id` AS `leiter_id` from (`allianzen` left join `spieler` on((`allianzen`.`a_id` = `spieler`.`a_id`))) where isnull(`spieler`.`s_id`);

DROP TABLE IF EXISTS `V_check_spieler`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `V_check_spieler` AS select `spieler`.`s_id` AS `s_id`,`spieler`.`name` AS `name`,`spieler`.`a_id` AS `a_id` from ((`spieler` left join `coords` on((`spieler`.`s_id` = `coords`.`s_id`))) left join `user_pwd` on((`spieler`.`s_id` = `user_pwd`.`s_id`))) where (isnull(`coords`.`c_id`) and isnull(`user_pwd`.`m_id`));

DROP TABLE IF EXISTS `V_check_user`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `V_check_user` AS select `V_user`.`m_id` AS `m_id`,`V_user`.`name` AS `name`,`V_user`.`a_id` AS `a_id`,`V_user`.`pwd` AS `pwd`,`V_user`.`admin` AS `admin`,`V_user`.`leiter` AS `leiter`,`V_user`.`c_pwd` AS `c_pwd`,`V_user`.`urlaub` AS `urlaub`,`V_user`.`blocked` AS `blocked` from `V_user` where (`V_user`.`c_pwd` = 1) order by `V_user`.`name`;

DROP TABLE IF EXISTS `V_leiter_internal`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `V_leiter_internal` AS select `spieler`.`s_id` AS `s_id`,`spieler`.`name` AS `name`,`spieler`.`a_id` AS `a_id`,`allianzen`.`a_id` AS `allianzen.a_id`,`allianzen`.`allianz` AS `allianz`,`allianzen`.`leiter_id` AS `leiter_id` from (`spieler` join `allianzen` on((`allianzen`.`leiter_id` = `spieler`.`s_id`)));

DROP TABLE IF EXISTS `V_spieler`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `V_spieler` AS select `coords`.`c_id` AS `c_id`,`V_spieler_alli`.`s_id` AS `s_id`,`V_spieler_alli`.`a_id` AS `a_id`,`V_spieler_alli`.`name` AS `name`,`V_spieler_alli`.`allianz` AS `allianz`,`coords`.`gal` AS `gal`,`coords`.`sys` AS `sys`,`coords`.`pla` AS `pla`,(select `spieler`.`name` from `spieler` where (`coords`.`farm_von` = `spieler`.`s_id`)) AS `farm`,(select `spieler`.`name` from `spieler` where (`coords`.`freigegeben` = `spieler`.`s_id`)) AS `freigegeben` from (`coords` join `V_spieler_alli` on((`coords`.`s_id` = `V_spieler_alli`.`s_id`))) order by `V_spieler_alli`.`allianz`,`V_spieler_alli`.`name`,`coords`.`gal`,`coords`.`sys`,`coords`.`pla`;

DROP TABLE IF EXISTS `V_spieler_alli`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `V_spieler_alli` AS select `spieler`.`s_id` AS `s_id`,`allianzen`.`a_id` AS `a_id`,`spieler`.`name` AS `name`,`allianzen`.`allianz` AS `allianz` from (`allianzen` join `spieler` on((`allianzen`.`a_id` = `spieler`.`a_id`))) where (not(exists(select 1 from `blacklisted` where (`allianzen`.`a_id` = `blacklisted`.`a_id`)))) order by `allianzen`.`allianz`,`spieler`.`name`;

DROP TABLE IF EXISTS `V_spieler_internal`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `V_spieler_internal` AS select `spieler`.`s_id` AS `s_id`,`spieler`.`name` AS `name`,`spieler`.`a_id` AS `a_id`,`allianzen`.`allianz` AS `allianz`,(select 'x' from `allianzen` where (`allianzen`.`leiter_id` = `spieler`.`s_id`)) AS `leiter` from (`spieler` join `allianzen` on((`spieler`.`a_id` = `allianzen`.`a_id`)));

DROP TABLE IF EXISTS `V_user`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `V_user` AS select `user_pwd`.`m_id` AS `m_id`,`spieler`.`name` AS `name`,`spieler`.`a_id` AS `a_id`,`user_pwd`.`pwd` AS `pwd`,`user_pwd`.`admin` AS `admin`,(select '1' from `allianzen` where (`allianzen`.`leiter_id` = `user_pwd`.`s_id`)) AS `leiter`,`user_pwd`.`c_pwd` AS `c_pwd`,`user_pwd`.`urlaub` AS `urlaub`,(select `spieler`.`name` from `spieler` where (`user_pwd`.`b_id` = `spieler`.`s_id`)) AS `blocked` from (`user_pwd` join `spieler` on((`user_pwd`.`s_id` = `spieler`.`s_id`)));

-- 2015-07-20 00:48:21
