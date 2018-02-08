/****************************************************************
 * Upgrades OpenExam database schema to version 2.0
 *
 * Author: Anders Lövgren
 * Date:   2017-12-06
 ****************************************************************/

/**
 * Author:  Anders Lövgren (QNET)
 * Created: 2018-jan-30
 */

-- 
-- Using Phalcon random UUID-generator:
-- 
ALTER TABLE questions MODIFY uuid VARCHAR(36);
ALTER TABLE topics MODIFY uuid VARCHAR(36);

-- 
-- Use InnoDB engine on all tables:
-- 
ALTER TABLE admins ENGINE=InnoDB;
ALTER TABLE sessions ENGINE=InnoDB;
ALTER TABLE settings ENGINE=InnoDB;
ALTER TABLE teachers ENGINE=InnoDB;

-- 
-- Use InnoDB engine on all audit tables:
-- 
ALTER TABLE access ENGINE=InnoDB;
ALTER TABLE admin ENGINE=InnoDB;
ALTER TABLE answer ENGINE=InnoDB;
ALTER TABLE audit ENGINE=InnoDB;
ALTER TABLE computer ENGINE=InnoDB;
ALTER TABLE contributor ENGINE=InnoDB;
ALTER TABLE corrector ENGINE=InnoDB;
ALTER TABLE decoder ENGINE=InnoDB;
ALTER TABLE exam ENGINE=InnoDB;
ALTER TABLE file ENGINE=InnoDB;
ALTER TABLE invigilator ENGINE=InnoDB;
ALTER TABLE `lock` ENGINE=InnoDB;
ALTER TABLE question ENGINE=InnoDB;
ALTER TABLE render ENGINE=InnoDB;
ALTER TABLE resource ENGINE=InnoDB;
ALTER TABLE result ENGINE=InnoDB;
ALTER TABLE room ENGINE=InnoDB;
ALTER TABLE session ENGINE=InnoDB;
ALTER TABLE setting ENGINE=InnoDB;
ALTER TABLE student ENGINE=InnoDB;
ALTER TABLE teacher ENGINE=InnoDB;
ALTER TABLE topic ENGINE=InnoDB;
ALTER TABLE user ENGINE=InnoDB;

-- 
-- Add index on username used frequent in role joins:
-- 
ALTER TABLE admins ADD INDEX (user);
ALTER TABLE contributors ADD INDEX (user);
ALTER TABLE correctors ADD INDEX (user);
ALTER TABLE decoders ADD INDEX (user);
ALTER TABLE invigilators ADD INDEX (user);
ALTER TABLE students ADD INDEX (user);
ALTER TABLE teachers ADD INDEX (user);
ALTER TABLE exams ADD INDEX (creator);

-- 
-- Add index for address/hostname used during exam locking:
-- 
ALTER TABLE computers ADD INDEX (hostname);
ALTER TABLE computers ADD INDEX (ipaddr);

-- 
-- Add index for the exam name is used in filtering:
-- 
ALTER TABLE exams ADD INDEX (name);

-- 
-- Add index for uniqueness check on files:
-- 
ALTER TABLE files ADD INDEX (name);
ALTER TABLE files ADD INDEX (path);

-- 
-- Add index on users table used as internal catalog service:
-- 
ALTER TABLE users ADD INDEX (principal);
ALTER TABLE users ADD INDEX (given_name);
ALTER TABLE users ADD INDEX (display_name);
ALTER TABLE users ADD INDEX (sn);
ALTER TABLE users ADD INDEX (cn);
ALTER TABLE users ADD INDEX (pnr);

-- 
-- Fix audit table format:
-- 
DROP TABLE IF EXISTS `audit`;
CREATE TABLE `audit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `res` varchar(20) DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob,
  PRIMARY KEY (`id`),
  KEY `user` (`user`),
  KEY `rid` (`rid`),
  KEY `res` (`res`),
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
