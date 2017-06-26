-- ------------------------------------------------------------------ --
--              БАЗА ДАННЫХ И ПОЛЬЗОВАТЕЛЬ БАЗЫ ДАННЫХ                --
-- ------------------------------------------------------------------ --
CREATE DATABASE IF NOT EXISTS belfry2; 
GRANT ALL PRIVILEGES ON belfry2.* TO belfry@localhost IDENTIFIED BY 'belfry';

USE belfry2;

-- ------------------------------------------------------------------ --
--                             ТАБЛИЦЫ                                --
-- ------------------------------------------------------------------ --
-- ------------------------------------------------------------------ --
--   Возможности реализуемые клиентами                                --
-- ------------------------------------------------------------------ --

CREATE TABLE IF NOT EXISTS `abilities` (
    `id`             int(11) NOT NULL AUTO_INCREMENT,
    `client`         int(11) NOT NULL,
    `location`       int(11) NOT NULL,
    `category`       int(11) NOT NULL,
    `state`          varchar(24) NOT NULL,
    `touch`          int(11) NOT NULL,
    `price`          float NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- ------------------------------------------------------------------ --
--   Администраторы системы                                           --
-- ------------------------------------------------------------------ --

CREATE TABLE IF NOT EXISTS `admins` (
    `id`             int(11) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- ------------------------------------------------------------------ --
--   Ключи авторизации                                                --
-- ------------------------------------------------------------------ --

CREATE TABLE IF NOT EXISTS `auth` (
    `id`             int(11) NOT NULL AUTO_INCREMENT,
    `token`          varchar(32) NOT NULL,
    `user`           int(11) NOT NULL,
    `expired`        int(11) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- ------------------------------------------------------------------ --
--   Звонки                                                           --
-- ------------------------------------------------------------------ --

CREATE TABLE IF NOT EXISTS `calls` (
    `hash`           varchar(32) NOT NULL,
    `route`          varchar(32) NOT NULL,
    `gate`           int(11) NOT NULL,
    `direction`      varchar(12) NOT NULL,
    `result`         varchar(24) NOT NULL,
    `dial_length`    int(11) NOT NULL,
    `answ_length`    int(11) NOT NULL,
    `created_on`     int(11) NOT NULL,
    `rec_hash`       varchar(32) NOT NULL,
    `state`          varchar(24) NOT NULL,
    PRIMARY KEY (`hash`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- ------------------------------------------------------------------ --
--   Категории услуг                                                  --
-- ------------------------------------------------------------------ --

CREATE TABLE IF NOT EXISTS `categories` (
    `id`             int(11) NOT NULL AUTO_INCREMENT,
    `name`           varchar(64) NOT NULL,
    `description`    text NOT NULL,
    `root`           int(11) NOT NULL,
    `price`          float NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1000 ;

-- ------------------------------------------------------------------ --
--   Пользователи системы - клиенты                                   --
-- ------------------------------------------------------------------ --

CREATE TABLE IF NOT EXISTS `clients` (
    `id`             int(11) NOT NULL,
    `balance`        float NOT NULL,
    `accept_status`  varchar(24) NOT NULL,
    `decline_reason` varchar(24) NOT NULL,
    `call_time_from` int(11) NOT NULL,
    `call_time_to`   int(11) NOT NULL,
    `call_dow_from`  int(11) NOT NULL,
    `call_dow_to`    int(11) NOT NULL,
    `timezone`       int(11) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- ------------------------------------------------------------------ --
--   Зарегистрированные модемы                                        --
-- ------------------------------------------------------------------ --

CREATE TABLE IF NOT EXISTS `dongles` (
    `id`             varchar(24) NOT NULL,
    `gate_id`        int(11) NOT NULL,
    `updated`        int(11) NOT NULL,
    `phone`          varchar(24) NOT NULL,
    `catid`          int(11) NOT NULL,
    `locid`          int(11) NOT NULL,
    `leaser`         int(11) NOT NULL DEFAULT '0',
    `routing`        varchar(12) NOT NULL DEFAULT 'STD',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

-- ------------------------------------------------------------------ --
--   Зарегистрированные шлюзы                                         --
-- ------------------------------------------------------------------ --

CREATE TABLE IF NOT EXISTS `gates` (
    `id`             int(11) NOT NULL,
    `url`            varchar(64) NOT NULL,
    `sw`             varchar(32) NOT NULL,
    `updated`        int(11) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `url` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

-- ------------------------------------------------------------------ --
--   Локации (категория геотаргетинга)                                --
-- ------------------------------------------------------------------ --

CREATE TABLE IF NOT EXISTS `locations` (
    `id`             int(11) NOT NULL AUTO_INCREMENT,
    `name`           varchar(64) NOT NULL,
    `description`    text NOT NULL,
    `root`           int(11) NOT NULL,
    `price`          float NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1000 ;

-- ------------------------------------------------------------------ --
--   Запросы на предоставление номеров от партнеров                   --
-- ------------------------------------------------------------------ --

CREATE TABLE IF NOT EXISTS `offer_requests` (
    `id`             int(11) NOT NULL AUTO_INCREMENT,
    `partner`        int(11) NOT NULL,
    `category`       int(11) NOT NULL,
    `location`       int(11) NOT NULL,
    `status`         varchar(24) NOT NULL,
    `created`        int(11) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- ------------------------------------------------------------------ --
--   Пользователи системы - партнеры                                  --
-- ------------------------------------------------------------------ --

CREATE TABLE IF NOT EXISTS `partners` (
    `id`             int(11) NOT NULL,
    `balance`        float NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

-- ------------------------------------------------------------------ --
--   Маршруты                                                         --
-- ------------------------------------------------------------------ --

CREATE TABLE IF NOT EXISTS `routes` (
    `id`             int(11) NOT NULL AUTO_INCREMENT,
    `hash`           varchar(32) NOT NULL,
    `user_id`        int(11) NOT NULL,
    `user_dongle`    varchar(32) NOT NULL,
    `client_dongle`  varchar(32) NOT NULL,
    `client_phone`   varchar(24) NOT NULL,
    `gate_id`        int(11) NOT NULL,
    `created`        int(11) NOT NULL,
    `expired`        int(11) NOT NULL,
    `state`          varchar(32),
    `generator`      int(11) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique` (`user_id`,
                         `user_dongle`,
                         `client_dongle`,
                         `client_phone`,
                         `gate_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- ------------------------------------------------------------------ --
--   Тарифы                                                           --
-- ------------------------------------------------------------------ --

CREATE TABLE IF NOT EXISTS `tarifs` (
      `location`     int(11) NOT NULL,
      `category`     int(11) NOT NULL,
      `price`        float NOT NULL,
      UNIQUE KEY `pair` (`location`,`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ------------------------------------------------------------------ --
--   Задачи                                                           --
-- ------------------------------------------------------------------ --

CREATE TABLE IF NOT EXISTS `tasks` (
    `id`             int(11) NOT NULL AUTO_INCREMENT,
    `user_id`        int(11) NOT NULL,
    `gate_id`        int(11) NOT NULL,
    `client_dongle`  varchar(24) NOT NULL,
    `dongles`        text,
    `state`          varchar(12) NOT NULL,    
    `expires_on`     int(11) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- ------------------------------------------------------------------ --
--   Транзакции                                                       --
-- ------------------------------------------------------------------ --

CREATE TABLE IF NOT EXISTS `transactions` (
    `id`             int(11) NOT NULL AUTO_INCREMENT,
    `pay_from`       int(11) NOT NULL,
    `pay_to`         int(11) NOT NULL,
    `amount`         float NOT NULL,
    `time`           int(11) NOT NULL,
    `comment`        text NOT NULL,
    `desc1`          varchar(32) NOT NULL,
    `desc2`          varchar(64) NOT NULL,
    `desc3`          varchar(128) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- ------------------------------------------------------------------ --
--   Пользователи системы (публичной части)                           --
-- ------------------------------------------------------------------ --

CREATE TABLE IF NOT EXISTS `users` (
    `id`             int(11) NOT NULL AUTO_INCREMENT,
    `phone`          varchar(24) NOT NULL,
    `password`       varchar(64) NOT NULL,
    `name`           varchar(32) NOT NULL,
    `second_name`    varchar(32) NOT NULL,
    `last_name`      varchar(32) NOT NULL,
    `email`          varchar(64) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1001 ;

-- ------------------------------------------------------------------ --
--                ХРАНИМЫЕ ФУНКЦИИ И ПРОЦЕДУРЫ                        --
-- ------------------------------------------------------------------ --

DROP PROCEDURE IF EXISTS `save_task`;
DROP PROCEDURE IF EXISTS `save_tasks`;
DROP PROCEDURE IF EXISTS `start_fillup_transaction`;
DROP PROCEDURE IF EXISTS `toggle_all_flags`;
DROP PROCEDURE IF EXISTS `toggle_one_flags`;
DROP PROCEDURE IF EXISTS `update_balance`;
DROP PROCEDURE IF EXISTS `update_tasks`;
DROP PROCEDURE IF EXISTS `update_service_tasks`;
DROP FUNCTION IF EXISTS `change_client_data`;
DROP FUNCTION IF EXISTS `finallize_fillup_transaction`;
DROP FUNCTION IF EXISTS `get_best_gate`;
DROP FUNCTION IF EXISTS `get_level_cat`;
DROP FUNCTION IF EXISTS `get_level_loc`;
DROP FUNCTION IF EXISTS `get_tarif0`;
DROP FUNCTION IF EXISTS `get_tarif1`;
DROP FUNCTION IF EXISTS `merge_call`;
DROP FUNCTION IF EXISTS `merge_dongle`;
DROP FUNCTION IF EXISTS `merge_route`;
DROP FUNCTION IF EXISTS `register_client`;
DROP FUNCTION IF EXISTS `reset_password`;
DROP FUNCTION IF EXISTS `_check_gate`;
DROP FUNCTION IF EXISTS `_check_pair`;
DROP FUNCTION IF EXISTS `_check_user_time`;

DELIMITER $$
--
-- Процедуры
--
CREATE DEFINER=`belfry`@`localhost` PROCEDURE `save_task`(IN `user` INT(11), IN `gate` INT(11), IN `dongle` VARCHAR(24) CHARSET utf8)
    MODIFIES SQL DATA
BEGIN
	DECLARE do_insert INT(11) DEFAULT 0;
	DECLARE done INT(11) DEFAULT 0;
	DECLARE dngls TEXT DEFAULT '';
	DECLARE curs CURSOR FOR SELECT `id` FROM `dongles` WHERE `gate_id` = gate ORDER BY RAND();
	DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET done = 1;
	
	OPEN curs;
	REPEAT BEGIN	
		DECLARE did VARCHAR(24);
		FETCH curs INTO did;
		IF NOT done THEN BEGIN
			DECLARE _r INT(11);
			SELECT _check_pair(did, dongle, user) INTO _r;
			IF _r > 0 THEN BEGIN
				SET dngls = CONCAT(dngls, '|', did);
				SET do_insert = 1;
			END; END IF;
		END; END IF;
	END; UNTIL done END REPEAT;
	CLOSE curs;		
	
	IF do_insert > 0 THEN BEGIN
		INSERT INTO `tasks` (`user_id`, `gate_id`, `client_dongle`, `dongles`, `state`, `expires_on`) VALUES (user, gate, dongle, dngls, 'active', UNIX_TIMESTAMP() + 3 * 60);
	END; END IF;
END$$

CREATE DEFINER=`belfry`@`localhost` PROCEDURE `save_tasks`(IN `user` INT(11), IN `gate` INT(11))
    MODIFIES SQL DATA
BEGIN
	DECLARE done INT(11) DEFAULT 0;
	DECLARE curs CURSOR FOR SELECT `id` FROM `dongles` WHERE `gate_id` = gate AND `routing` = 'STD';
	DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET done = 1;
	
	OPEN curs;
	REPEAT BEGIN	
		DECLARE did VARCHAR(24);
		FETCH curs INTO did;
		IF NOT done THEN BEGIN			
			CALL save_task(user, gate, did);
		END; END IF;
	END; UNTIL done END REPEAT;
	CLOSE curs;	
END$$

CREATE DEFINER=`belfry`@`localhost` PROCEDURE `start_fillup_transaction`(
	IN `user_id` INT(11),
	IN `amount` FLOAT,
	OUT `paycode` VARCHAR(12) CHARSET utf8,
	OUT `user_email` VARCHAR(64) CHARSET utf8
)
    MODIFIES SQL DATA
BEGIN
	DECLARE tr_time INT(11);	
	SET tr_time = UNIX_TIMESTAMP();
	
	INSERT INTO `transactions` (`pay_from`, `pay_to`, `amount`, `time`, `desc1`) 
	VALUES 
	('498', user_id, amount, tr_time, 'FU-STAGE1');
	
	SET paycode = LAST_INSERT_ID();
	
	SELECT `email` INTO user_email FROM `users` WHERE `id` = user_id;
	
END$$

CREATE DEFINER=`belfry`@`localhost` PROCEDURE `toggle_all_flags`(IN `pid` INT(11))
    MODIFIES SQL DATA
BEGIN
	DECLARE ups_amount INT(11) DEFAULT 0;
	SELECT COUNT(*) INTO ups_amount FROM `abilities` WHERE `state` = 'up' AND `client` = pid;	
	IF ups_amount > 0 THEN
		UPDATE `abilities` SET `state` = 'down' WHERE `client` = pid;
	ELSE
		UPDATE `abilities` SET `state` = 'up' WHERE `client` = pid;
	END IF;	
END$$

CREATE DEFINER=`belfry`@`localhost` PROCEDURE `toggle_one_flags`(IN `pid` INT(11), IN `abi` INT(11))
    MODIFIES SQL DATA
BEGIN
	UPDATE `abilities` SET `state` = IF(`state` = 'up', 'down', 'up') WHERE `client` = pid AND `id` = abi;
END$$

CREATE DEFINER=`belfry`@`localhost` PROCEDURE `update_balance`(IN `uid_` INT(11))
    MODIFIES SQL DATA
BEGIN
	-- обновление текущих балансов 	
	DECLARE _balance FLOAT DEFAULT 0;
	DECLARE _a FLOAT DEFAULT 0;
	DECLARE _t INT(11);
	DECLARE _f INT(11);
	
	-- пройдем по всем транзакциям системы в которых участвовал пользователь
	DECLARE done INT(11) DEFAULT 0;
	DECLARE curs CURSOR FOR SELECT `amount`, `pay_to`, `pay_from` FROM `transactions` WHERE `pay_to` = uid_ OR `pay_from` = uid_;
	DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET done = 1;
	
	OPEN curs;
	REPEAT 
		FETCH curs INTO _a, _t, _f;
		IF NOT done THEN 
			IF _t = uid_ AND _f <> uid_ THEN 
				SET _balance = _balance + _a;
			ELSEIF _f = uid_ AND _t <> uid_ THEN
				SET _balance = _balance - _a;			
			END IF;
		END IF;
	UNTIL done END REPEAT;
	CLOSE curs;
	
	UPDATE `clients` SET `balance` = _balance WHERE `id` = uid_;
	UPDATE `partners` SET `balance` = _balance WHERE `id` = uid_;	
END$$

CREATE DEFINER=`belfry`@`localhost` PROCEDURE `update_tasks`()
    MODIFIES SQL DATA
BEGIN	
	DECLARE done INT(11) DEFAULT 0;
	DECLARE curs CURSOR FOR SELECT `client` FROM `abilities` WHERE `state` = 'up' AND _check_user_time(`client`) = 1 AND `client` > 1000 GROUP BY `client` ORDER BY `touch` ASC;
	DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET done = 1;
	
	UPDATE `tasks` SET `state` = 'innactive' WHERE `state` = 'active' ;	
	SET done = 0;	

	OPEN curs;
	REPEAT BEGIN
		DECLARE clid INT(11);
		FETCH curs INTO clid;
		IF NOT done THEN BEGIN
			DECLARE gid INT(11);
			SELECT get_best_gate(clid) INTO gid;
			CALL save_tasks(clid, gid);
		END; END IF;
	END; UNTIL done END REPEAT;
	CLOSE curs;	
END$$

CREATE DEFINER=`belfry`@`localhost` PROCEDURE `update_service_tasks`()
    MODIFIES SQL DATA
BEGIN
    DECLARE sdid varchar(24);
    DECLARE odid varchar(24);
    DECLARE str TEXT DEFAULT '';
    DECLARE insertion INT(11) DEFAULT 0;
    DECLARE sgate INT(11);
    DECLARE ogate INT(11);

	DECLARE done INT(11) DEFAULT 0;
	DECLARE curs1 CURSOR FOR SELECT `id` FROM `dongles` WHERE `routing` = 'SVC';
    DECLARE curs2 CURSOR FOR SELECT `id` FROM `dongles`;
	DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET done = 1;

    OPEN curs1;
	REPEAT BEGIN
		FETCH curs1 INTO sdid;
        IF NOT done THEN BEGIN
            SET done = 0;
            SET insertion = 0;
            SET str = '';
            OPEN curs2;
            REPEAT BEGIN
                FETCH curs2 INTO odid;
				IF NOT done THEN BEGIN
                    
                    SELECT `gate_id` INTO sgate FROM `dongles` WHERE `id` = sdid;
                    SELECT `gate_id` INTO ogate FROM `dongles` WHERE `id` = odid;
                    
                    IF sgate = ogate AND sdid <> odid THEN BEGIN
                        SET str = CONCAT(str, '+', odid);
                        SET insertion = 1;
                    END; END IF;
                END; END IF;
            END; UNTIL done END REPEAT;
            CLOSE curs2;
            SET done = 0;
            
            IF insertion = 1 THEN BEGIN
                INSERT INTO `tasks` (`user_id`, `gate_id`, `client_dongle`, `dongles`, `state`, `expires_on`) VALUES ('1001', sgate, sdid, str, 'active', UNIX_TIMESTAMP() + 3 * 60);
            END; END IF;
		END; END IF;
	END; UNTIL done END REPEAT;
	CLOSE curs1;
END$$

--
-- Функции
--
CREATE DEFINER=`belfry`@`localhost` FUNCTION `change_client_data`(
	`pid_` INT(11),
	`last_name_` VARCHAR(32) CHARSET utf8, 
	`first_name_` VARCHAR(32) CHARSET utf8, 
	`second_name_` VARCHAR(32) CHARSET utf8, 
	`email_` VARCHAR(64) CHARSET utf8, 
--	`phone_` VARCHAR(24) CHARSET utf8, 
	`gc_from_time_` INT(11), 
	`gc_to_time_` INT(11), 
	`gc_from_dow_` INT(11), 
	`gc_to_dow_` INT(11), 
	`timezone_` INT(11)) RETURNS int(11)
    MODIFIES SQL DATA
BEGIN

	UPDATE `clients`
	SET
		`call_time_from` = gc_from_time_,
		`call_time_to` = gc_to_time_,
		`call_dow_from` = gc_from_dow_,
		`call_dow_to` = gc_to_dow_,
		`timezone` = timezone_
	WHERE
		`id` = pid_;
		
	UPDATE `users`
	SET 
--		`phone` = phone_, 
		`name` = first_name_,
		`second_name` = second_name_,
		`last_name` = last_name_,
		`email` = email_
	WHERE
		`id` = pid_;
		
	RETURN 0;
END$$

CREATE DEFINER=`belfry`@`localhost` FUNCTION `finallize_fillup_transaction`(`payid` INT(11), `amount` FLOAT) RETURNS varchar(12) CHARSET latin1
    MODIFIES SQL DATA
BEGIN
	DECLARE user_id INT(11) DEFAULT 0;
	
	SELECT `pay_to` INTO user_id FROM `transactions` WHERE `pay_from` = '498' AND `desc1` = 'FU-STAGE1' AND `id` = payid;
	
	IF user_id > 0 THEN
		UPDATE `transactions` SET `desc1` = 'FILLUP', `amount` = amount WHERE `id` = payid;
		CALL update_balance(user_id);
		
		RETURN 'ok';
	ELSE
		RETURN 'error';
	END IF;
	
END$$

CREATE DEFINER=`belfry`@`localhost` FUNCTION `get_best_gate`(`user` INT(11)) RETURNS int(11)
    READS SQL DATA
BEGIN
	DECLARE available_dongles INT(11) DEFAULT -1;
	DECLARE best_gate INT(11) DEFAULT -1;
	
	DECLARE done INT(11) DEFAULT 0;
	DECLARE curs CURSOR FOR SELECT `id` FROM `gates`;
	DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET done = 1;

	OPEN curs;
	REPEAT BEGIN
		DECLARE gid INT(11);
		FETCH curs INTO gid;
		IF NOT done THEN BEGIN
			DECLARE avail INT(11) DEFAULT -1;
			SELECT _check_gate(gid, user) INTO avail;
			IF avail > available_dongles THEN BEGIN
				SET available_dongles = avail;
				SET best_gate = gid;
			END; END IF;
		END; END IF;
	END; UNTIL done END REPEAT;
	CLOSE curs;
	
	RETURN best_gate;
END$$

CREATE DEFINER=`belfry`@`localhost` FUNCTION `get_level_cat`(`p_inter` INT(11), `p_outer` INT(11)) RETURNS int(11)
    READS SQL DATA
BEGIN
	DECLARE level INT(11);
	DECLARE im INT(11);
	DECLARE _im INT(11);

	IF `p_inter` = `p_outer` THEN RETURN 0;
	END IF;
	
	SET level = 0;
	SET im = p_inter;

	WHILE 1 DO
		SELECT `root` INTO _im FROM `categories` WHERE `id` = im LIMIT 1;
		SET im = _im;
		IF im = 0 THEN
			RETURN -1;
		ELSEIF ISNULL(im) THEN
			RETURN -2;
		ELSE
			SET level = level + 1;
			IF im = p_outer THEN
				RETURN level;
			END IF;
		END IF;
	
	END WHILE;
END$$

CREATE DEFINER=`belfry`@`localhost` FUNCTION `get_level_loc`(`p_inter` INT(11), `p_outer` INT(11)) RETURNS int(11)
    READS SQL DATA
BEGIN
	DECLARE level INT(11);
	DECLARE im INT(11);
	DECLARE _im INT(11);

	IF `p_inter` = `p_outer` THEN RETURN 0;
	END IF;
	
	SET level = 0;
	SET im = p_inter;

	WHILE 1 DO
		SELECT `root` INTO _im FROM `locations` WHERE `id` = im LIMIT 1;
		SET im = _im;
		IF im = 0 THEN
			RETURN -1;
		ELSEIF ISNULL(im) THEN
			RETURN -2;
		ELSE
			SET level = level + 1;
			IF im = p_outer THEN
				RETURN level;
			END IF;
		END IF;
	
	END WHILE;
END$$

CREATE DEFINER=`belfry`@`localhost` FUNCTION `get_tarif0`(`lid` INT(11), `cid` INT(11), `uid` INT(11)) RETURNS FLOAT
BEGIN
    DECLARE _result FLOAT DEFAULT 0;
    DECLARE loc_t FLOAT DEFAULT 0;
    DECLARE cat_t FLOAT DEFAULT 0;
    DECLARE pair_c FLOAT DEFAULT 0;
    DECLARE personal_c FLOAT DEFAULT 0;    

    SELECT `price` INTO loc_t FROM `locations` WHERE `id` = lid;
    SELECT `price` INTO cat_t FROM `categories` WHERE `id` = cid;
    SELECT `price` INTO pair_c FROM `tarifs` WHERE `location` = lid AND `category` = cid;
    SELECT `price` INTO personal_c FROM `abilities` WHERE `location` = lid AND `category` = cid AND `client` = uid;

    SET _result = loc_t + cat_t + pair_c + personal_c;
    RETURN _result;
END$$

CREATE DEFINER=`belfry`@`localhost` FUNCTION `get_tarif1`(`hash_` VARCHAR(32) CHARSET utf8) RETURNS FLOAT
BEGIN
    DECLARE lid INT(11) DEFAULT 0;
    DECLARE cid INT(11) DEFAULT 0;
    DECLARE uid INT(11) DEFAULT 0;
    DECLARE did VARCHAR(24) CHARSET utf8;
    DECLARE _result FLOAT DEFAULT 0;

    SELECT `user_id`, `client_dongle` INTO uid, did FROM `routes` WHERE `hash` = hash_;
    SELECT `catid`, `locid` INTO cid, lid FROM `dongles` WHERE `id` = did; 

    SELECT `get_tarif0`(lid, cid, uid) INTO _result;
    RETURN _result;
END$$

CREATE DEFINER=`belfry`@`localhost` FUNCTION `merge_call`(`hash_` VARCHAR(32) CHARSET utf8, `route_` VARCHAR(32) CHARSET utf8, `id_` INT(11), `direction_` VARCHAR(12) CHARSET utf8, `result_` VARCHAR(24) CHARSET utf8, `dial_length_` INT(11), `answ_length_` INT(11), `created_on_` INT(11), `rec_hash_` VARCHAR(32) CHARSET utf8) RETURNS int(11)
    MODIFIES SQL DATA
BEGIN
	-- слияние информации о звонках из шлюза с информацией в центральной базе
	DECLARE _sys_payer INT(11) DEFAULT 500; -- идентификатор системного пользователя - участника платежей за звонки
	DECLARE _rg_tariff1 FLOAT DEFAULT 0; -- стоимость генерации маршрута для пользователя
	DECLARE _rg_tariff2 FLOAT DEFAULT 0; -- стоимость генерации маршрута для партнера
	DECLARE _cl_tariff FLOAT DEFAULT 1.1; -- стоимость минуты разговора
	DECLARE _uid INT(11);
	DECLARE _cnt INT(11);
	DECLARE _rgid INT(11);
	SELECT COUNT(*) INTO _cnt FROM `calls` WHERE `hash` = hash_;
	
	IF _cnt < 1 THEN -- если добавляемый звонок отсутствует в таблице
		SELECT COUNT(*) INTO _cnt FROM `routes` WHERE `hash` = route_;
		
		IF _cnt > 0 THEN -- если существуют маршруты, к которым относится этот звонок
			SELECT `user_id`, `generator` INTO _uid, _rgid FROM `routes` WHERE `hash` = route_; -- узнаем, пользователя и генератора найденного маршрута			
			IF direction_ = 'RG' THEN -- если звонок сгенерировал маршрут
				-- сбрасываем кнопку
				UPDATE `abilities` SET `state` = 'down', `touch` = UNIX_TIMESTAMP() WHERE `client` = _uid;
                SELECT `get_tarif1`(route_) INTO _rg_tariff1;
				-- списвыаем деньги с пользователя за звонок по тарифу генерации маршрута
				INSERT INTO `transactions` (`pay_from`, `pay_to`, `amount`, `time`, `comment`, `desc1`, `desc2`, `desc3`)
					VALUES (_uid, _sys_payer, _rg_tariff1, UNIX_TIMESTAMP(), '', 'RG_PAYOUT', hash_, '');
				CALL update_balance(_uid); -- пересчитываем текущий баланс пользователя
				IF _rgid >= 1000 THEN -- если существует генератор маршрута среди партнеров
					-- зачислям деньги генератору маршрута по тарифу генерации маршрута
					INSERT INTO `transactions` (`pay_from`, `pay_to`, `amount`, `time`, `comment`, `desc1`, `desc2`, `desc3`)
						VALUES (_sys_payer, _rgid, _rg_tariff2, UNIX_TIMESTAMP(), '', 'RG_PAYIN', hash_, '');
					CALL update_balance(_rgid); -- пересчитываем текущий баланс партнера
				END IF;
			ELSE -- если зонок прошел по старому маршруту
				-- списвыаем деньги с пользователя за звонок по минутному тарифу 
				INSERT INTO `transactions` (`pay_from`, `pay_to`, `amount`, `time`, `comment`, `desc1`, `desc2`, `desc3`)
					VALUES (_uid, _sys_payer, CEILING(answ_length_/60)*_cl_tariff, UNIX_TIMESTAMP(), '', 'CALL_PAYOUT', hash_, '');
				CALL update_balance(_uid); -- пересчитываем текущий баланс пользователя
			END IF;			
		END IF;
		-- сохраняем информацию о звонке
		INSERT INTO `calls` (`hash`, `route`, `gate`, `direction`, `result`, `dial_length`, `answ_length`, `created_on`, `rec_hash`, `state`)
			VALUES (hash_, route_, id_, direction_, result_, dial_length_, answ_length_, created_on_, rec_hash_, 'new');		
	END IF;
	
	RETURN 0;
END$$

CREATE DEFINER=`belfry`@`localhost` FUNCTION `merge_dongle`(`imsi_` VARCHAR(24) CHARSET utf8, `gid_` INT(11), `updated_` INT(11)) RETURNS int(11)
    MODIFIES SQL DATA
BEGIN
	DECLARE _cnt INT(11);

	SELECT COUNT(*) INTO _cnt FROM `dongles` WHERE `id` = imsi_;
	IF _cnt > 0 THEN
		UPDATE `dongles` SET `updated` = updated_ WHERE `id` = imsi_;
	ELSE
		INSERT INTO `dongles` (`id`, `gate_id`, `updated`) VALUES (imsi_, gid_, updated_);
	END IF;
	RETURN 0;
END$$

CREATE DEFINER=`belfry`@`localhost` FUNCTION `merge_route`(`hash_` VARCHAR(32), `user_id_` INT(11), `user_dn_` VARCHAR(32), `client_dn_` VARCHAR(32), `client_ph_` VARCHAR(24), `id_` INT(11), `created_` INT(11), `expired_` INT(11), `state_` VARCHAR(32)) RETURNS int(11)
    MODIFIES SQL DATA
BEGIN
	-- слияние информации о маршрутах из шлюза с информацией в центральной базе
	DECLARE _cnt INT(11);
	DECLARE _rgid INT(11) DEFAULT 0;
	SELECT COUNT(*) INTO _cnt FROM `routes` WHERE `hash` = hash_;
	
	IF _cnt < 1 THEN -- если маршрута нет в центральной базе
		-- определяем генератора маршрута
		SELECT `leaser` INTO _rgid FROM `dongles` WHERE `id` = client_dn_;
		-- сохраняем маршрут
		INSERT INTO `routes` (`hash`, `user_id`, `user_dongle`, `client_dongle`, `client_phone`, `gate_id`, `created`, `expired`, `state`, `generator`) 
			VALUES (hash_, user_id_, user_dn_, client_dn_, client_ph_, id_, created_, expired_, state_, _rgid);
	END IF;
	
	RETURN 0;
END$$

CREATE DEFINER=`belfry`@`localhost` FUNCTION `register_client`(`user_ph` VARCHAR(24) CHARSET utf8, `pass` VARCHAR(64) CHARSET utf8) RETURNS varchar(24) CHARSET utf8
    MODIFIES SQL DATA
BEGIN
	DECLARE last_id INT(11);
	DECLARE already_registered INT(11);	
	SELECT COUNT(*) INTO already_registered FROM `users` WHERE `phone` = user_ph;
	IF already_registered > 0 THEN
		RETURN 'ALREADY_REGISTERED';
	ELSE
		INSERT INTO `users` (`phone`, `password`) VALUES (user_ph, pass);
		SELECT LAST_INSERT_ID() INTO last_id;
		INSERT INTO `clients` (
			`id`, `balance`, `accept_status`, 
			`decline_reason`, `call_time_from`, 
			`call_time_to`, `call_dow_from`, 
			`call_dow_to`, `timezone`
		) VALUES (last_id, 0, 'wait', 'no', 10, 19, 1, 5, 3);
	END IF;
	RETURN 'OK';
END$$

CREATE DEFINER=`belfry`@`localhost` FUNCTION `reset_password`(`user_ph` VARCHAR(24) CHARSET utf8, `pass` VARCHAR(64) CHARSET utf8) RETURNS varchar(24) CHARSET utf8
    MODIFIES SQL DATA
BEGIN
	DECLARE last_id INT(11);
	DECLARE already_registered INT(11);	
	SELECT COUNT(*) INTO already_registered FROM `users` WHERE `phone` = user_ph;
	IF already_registered < 1 THEN
		RETURN 'DOESNOT_REGISTERED';
	ELSE
		SELECT `id` INTO last_id FROM `users` WHERE `phone` = user_ph;
		UPDATE `users` SET `password` = pass WHERE `id` = last_id;
	END IF;
	RETURN 'OK';
END$$

CREATE DEFINER=`belfry`@`localhost` FUNCTION `_check_gate`(`gate` INT(11), `user` INT(11)) RETURNS int(11)
    READS SQL DATA
BEGIN
	DECLARE counter INT(11) DEFAULT 0;
	DECLARE amount INT(11) DEFAULT 0;
	DECLARE done INT(11) DEFAULT 0;
	DECLARE ud VARCHAR(24);
	DECLARE cd VARCHAR(24);	
	
	DECLARE curs1 CURSOR FOR SELECT `id` FROM `dongles` WHERE `gate_id` = gate;
	DECLARE curs2 CURSOR FOR SELECT `id` FROM `dongles` WHERE `gate_id` = gate;	
	DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET done = 1;	
	
	OPEN curs1;
	REPEAT BEGIN
		FETCH curs1 INTO ud;
		IF NOT done THEN BEGIN
			SET done = 0;
			OPEN curs2;
			REPEAT BEGIN
				FETCH curs2 INTO cd;
				IF NOT done THEN BEGIN
					SELECT _check_pair(ud, cd, user) INTO amount;
					SET counter = counter + amount;
				END; END IF;
			END; UNTIL done END REPEAT;
			CLOSE curs2;
			SET done = 0;
		END; END IF;		
	END; UNTIL done END REPEAT;
	CLOSE curs1;
	RETURN counter;
END$$

CREATE DEFINER=`belfry`@`localhost` FUNCTION `_check_pair`(`u_dongle` VARCHAR(24) CHARSET utf8, `c_dongle` VARCHAR(24) CHARSET utf8, `u_id` INT(11)) RETURNS int(11)
    READS SQL DATA
BEGIN
	DECLARE ud_already_used INT(11) DEFAULT 0;
	DECLARE c_dongle_li INT(11);
	DECLARE c_dongle_ci INT(11);
	IF u_dongle = c_dongle THEN RETURN 0; END IF;

	SELECT COUNT(*) INTO ud_already_used FROM `routes` WHERE `user_dongle` = u_dongle AND `user_id` = u_id AND `state` = 'active';
	SELECT `catid`, `locid` INTO c_dongle_ci, c_dongle_li FROM `dongles` WHERE `id` = c_dongle;

	IF ud_already_used = 0 THEN BEGIN
		DECLARE done INT(11) DEFAULT 0;
		DECLARE lo INT(11);
		DECLARE ca INT(11);
		DECLARE ab_cur CURSOR FOR SELECT `location`, `category` FROM `abilities` WHERE `client` = u_id AND `state` = 'up';
		DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET done = 1;
		OPEN ab_cur;		
		REPEAT BEGIN
			FETCH ab_cur INTO lo, ca;
			IF NOT done THEN BEGIN
				DECLARE lo_lvl INT(11);
				DECLARE ca_lvl INT(11);
				SELECT get_level_loc(c_dongle_li, lo) INTO lo_lvl;
				SELECT get_level_cat(c_dongle_ci, ca) INTO ca_lvl;
				IF lo_lvl >= 0 AND ca_lvl>= 0 THEN BEGIN
					CLOSE ab_cur;
					RETURN 1;
				END; END IF;
			END; END IF;
		END; UNTIL done END REPEAT;		
		CLOSE ab_cur;
	END; END IF;
	RETURN 0;
END$$

CREATE DEFINER=`belfry`@`localhost` FUNCTION `_check_user_time`(`clid` INT(11)) RETURNS int(11)
    READS SQL DATA
BEGIN
	DECLARE _now DATETIME;
	DECLARE _dow_now INT(11);
	DECLARE _h_now INT(11);
	
	DECLARE _dow_user_from INT(11);
	DECLARE _dow_user_to INT(11);
	DECLARE _h_user_from INT(11);
	DECLARE _h_user_to INT(11);
	DECLARE _tz INT(11);
	DECLARE _accept VARCHAR(24);
	
	DECLARE _cnt INT(11);
	
	SELECT COUNT(*) INTO _cnt FROM `clients` WHERE `id` = clid;
	
	IF _cnt < 1 THEN RETURN 0; END IF;
	
	SELECT `timezone`, `accept_status`, `call_time_from`, `call_time_to`, `call_dow_from`, `call_dow_to` 
	INTO _tz, _accept, _h_user_from, _h_user_to, _dow_user_from, _dow_user_to 
	FROM `clients` 
	WHERE `id` = clid;
	
	IF _accept <> 'accepted' THEN RETURN 0; END IF;
	
	SELECT NOW() INTO _now;
	SET _now = _now + INTERVAL _tz HOUR;
	SELECT HOUR(_now) INTO _h_now;	
	SELECT WEEKDAY(_now) + 1 INTO _dow_now;
	
	IF _h_user_from > _h_user_to THEN
		SET _h_user_to = _h_user_to + 24;
	END IF;
	
	IF _dow_user_from > _dow_user_to THEN
		SET _dow_user_to = _dow_user_to + 7;
	END IF;
	
	IF _h_user_from <= _h_now AND _h_user_to > _h_now AND 
		_dow_user_from <= _dow_now AND _dow_user_to >= _dow_now THEN
		RETURN 1;
	END IF;
	
	RETURN 0;
END$$

DELIMITER ;
