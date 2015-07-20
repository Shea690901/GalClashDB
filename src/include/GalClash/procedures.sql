-- Adminer 4.2.1 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DELIMITER ;;

DROP PROCEDURE IF EXISTS `IP_optimize_tables`;;
CREATE PROCEDURE `IP_optimize_tables`()
BEGIN
    DECLARE done int default FALSE;
    DECLARE t varchar(64);
    DECLARE s varchar(2);
    DECLARE c cursor FOR SELECT `TABLE_NAME` FROM `IV_tables`;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN c;

    OL: LOOP
        FETCH c INTO t;
        IF done THEN
            LEAVE OL;
        END IF;
        SET @s = concat('OPTIMIZE TABLE `', t, '`');
        PREPARE STM FROM @s;
        EXECUTE stm;
        DEALLOCATE PREPARE stm;
    END LOOP;

    CLOSE c;
END;;

DROP PROCEDURE IF EXISTS `IP_rename_quest-guest`;;
CREATE PROCEDURE `IP_rename_quest-guest`()
BEGIN
    START TRANSACTION;

    INSERT INTO `I_name_clashes_spieler` ( `s_id`, `e_s_id` )
        SELECT `spieler`.`s_id`, `XXXX`.`s_id` FROM `spieler` JOIN `spieler` AS `XXXX`
                ON `spieler`.`name` = concat( 'g', substr( `XXXX`.`name`, 2 ) )
            WHERE `XXXX`.`name` regexp '^quest[0-9]{6}$';
    DELETE FROM `I_name_clashes_spieler`;

    INSERT INTO `I_new_names_spieler` ( `s_id`, `name` ) SELECT `s_id`, concat( 'g', substr( `name`, 2 ) ) FROM `spieler` WHERE `name` regexp '^quest[0-9]{6}$';
    DELETE FROM `I_new_names_spieler`;

    COMMIT;
END;;

DROP PROCEDURE IF EXISTS `P_add_allianz`;;
CREATE PROCEDURE `P_add_allianz`(OUT `a_id` int(11), IN `allianz` varchar(20))
BEGIN
   DECLARE s varchar(256) ;

   SET @s = concat("INSERT INTO `allianzen` ( `allianz` ) VALUES ( '", allianz, "' )");
   PREPARE stm FROM @s;
   EXECUTE stm;
   DEALLOCATE PREPARE stm;
   CALL P_get_a_id (a_id, allianz) ;
END;;

DROP PROCEDURE IF EXISTS `P_add_spieler`;;
CREATE PROCEDURE `P_add_spieler`(OUT `s_id` int(11), IN `name` varchar(20), IN `a_id` int(11))
BEGIN
   DECLARE s varchar(256) ;

   SET @s = concat("INSERT INTO spieler ( name, a_id ) VALUES ( '", name, "', ", a_id, ")");
   PREPARE stm FROM @s;
   EXECUTE stm;
   DEALLOCATE PREPARE stm;
   CALL P_get_s_id (s_id, name);
END;;

DROP PROCEDURE IF EXISTS `P_get_a_id`;;
CREATE PROCEDURE `P_get_a_id`(OUT `a_id` int(11), IN `allianz` varchar(20))
BEGIN
   DECLARE s varchar(256);
   DECLARE id int;

   SET @id = NULL;
   SET @s = concat("SELECT `a_id` INTO @`id` FROM `allianzen` WHERE `allianz` = '", allianz, "'");

   PREPARE stm FROM @s;
   EXECUTE stm;
   DEALLOCATE PREPARE stm;
   SET a_id = @id ;
END;;

DROP PROCEDURE IF EXISTS `P_get_allianz`;;
CREATE PROCEDURE `P_get_allianz`(OUT `ret` varchar(20), IN `name` varchar(20))
BEGIN
   DECLARE s varchar(256);
   DECLARE tmp varchar(20);

   SET @s = concat("SELECT `allianz` INTO @`tmp` FROM `allianzen` NATURAL JOIN `spieler` WHERE `name` = '", name, "'");

   PREPARE stm FROM @s;
   EXECUTE stm;
   DEALLOCATE PREPARE stm;
   SET ret = @tmp ;
END;;

DROP PROCEDURE IF EXISTS `P_get_s_id`;;
CREATE PROCEDURE `P_get_s_id`(OUT `s_id` int(11), IN `name` varchar(20))
BEGIN
   DECLARE s varchar(256);
   DECLARE id int;

   SET @id = NULL;
   SET @s = concat("SELECT `s_id` INTO @`id` FROM `spieler` WHERE `name` = '", name, "'");

   PREPARE stm FROM @s;
   EXECUTE stm;
   DEALLOCATE PREPARE stm;
   SET s_id = @id ;
END;;

DROP PROCEDURE IF EXISTS `P_set_leiter`;;
CREATE PROCEDURE `P_set_leiter`(IN `allianz` varchar(20), IN `name` varchar(20))
BEGIN
   DECLARE s varchar(256);
   DECLARE s_id int default null;
   DECLARE a_id int default null;

   START TRANSACTION;

   CALL P_get_a_id (@a_id, allianz);
   IF @a_id IS NULL THEN
      CALL P_add_allianz (@a_id, allianz);
   END IF;

   CALL P_get_s_id (@s_id, name);
   IF @s_id IS NULL THEN
      CALL P_add_spieler (@s_id, name, @a_id);
   ELSE
      SET @s = concat("UPDATE `spieler` SET `a_id` = ", @a_id, " WHERE `s_id` = ",  @s_id);
      PREPARE stm FROM @s;
      EXECUTE stm;
      DEALLOCATE PREPARE stm;
   END IF;
   
   SET @s = concat("UPDATE `allianzen` SET `leiter_id` = ", @s_id, " WHERE `a_id` = ",  @a_id);
   PREPARE stm FROM @s;
   EXECUTE stm;
   DEALLOCATE PREPARE stm;
   COMMIT;
END;;

DROP PROCEDURE IF EXISTS `P_get_user_info`;;
CREATE PROCEDURE `P_get_user_info`(OUT `allianz` varchar(20), OUT `leiter` int, OUT `admin` int, OUT `c_pwd` int, IN `name` varchar(20))
BEGIN
   DECLARE s  varchar(256);
   DECLARE t1 varchar(20);
   DECLARE t2 int;
   DECLARE t3 int;
   DECLARE t4 int;

   SET @s = concat("SELECT `allianz`, `leiter`, `admin`, `c_pwd` INTO @`t1`, @`t2`, @`t3`, @`t4` FROM `V_user` NATURAL JOIN `allianzen` WHERE `name` = '", name, "'");

   PREPARE stm FROM @s;
   EXECUTE stm;
   DEALLOCATE PREPARE stm;
   SET allianz = @t1 ;
   SET leiter = @t2 ;
   SET admin = @t3 ;
   SET c_pwd = @t4 ;
END;;

DROP PROCEDURE IF EXISTS `P_update_passwd`;;
CREATE PROCEDURE `P_update_passwd`(IN `name` varchar(20), IN `pwd` varchar(256))
BEGIN
   DECLARE s varchar(256);
   DECLARE s_id int default null;

   START TRANSACTION;

   l1: BEGIN
      CALL P_get_s_id (@s_id, name);
      IF @s_id IS NULL THEN
         ROLLBACK;
         LEAVE l1;
      END IF;
      SET @s = concat("UPDATE `user_pwd` SET `pwd` = '", pwd, "' WHERE `s_id` = ",  @s_id);
      PREPARE stm FROM @s;
      EXECUTE stm;
      DEALLOCATE PREPARE stm;
      COMMIT;
   END;
END;;

DROP EVENT IF EXISTS `IE_optimize_tables`;;
CREATE EVENT `IE_optimize_tables` ON SCHEDULE EVERY 1 WEEK STARTS '2015-04-08 10:15:23' ON COMPLETION PRESERVE ENABLE DO CALL IP_optimize_tables();;

DELIMITER ;

-- 2015-04-29 08:28:38
