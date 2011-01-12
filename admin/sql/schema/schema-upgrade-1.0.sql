/****************************************************************
 * Upgrades OpenExam database schema to version 1.0
 *
 * Author: Anders Lövgren
 * Date:   2010-12-13
 ****************************************************************/

--
-- Always create the schemainfo table if it not yet exist.
--
CREATE TABLE IF NOT EXISTS `schemainfo` (
        `id` int(11) NOT NULL DEFAULT '0',
        `major` int(11) NOT NULL DEFAULT '0',
        `minor` int(11) NOT NULL DEFAULT '0',
        `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8

--
-- This just makes sure the version record exist.
--
INSERT INTO schemainfo(id) VALUES(1) ON DUPLICATE KEY UPDATE major = major;

--
-- Add missing bindings. How an question is considered as answered has changed
-- from query for existing bindings to using an explicit answered flag.
--
INSERT INTO answers(student_id, question_id)
SELECT  s.id, q.id
FROM    students s, exams e, questions q
WHERE   s.exam_id = e.id AND
        e.id = q.exam_id AND NOT EXISTS
( 
        SELECT  * FROM answers
        WHERE   q.id = question_id AND student_id = s.id
);
ALTER TABLE `answers` MODIFY `answer` text;
ALTER TABLE `answers` ADD `answered` enum('Y','N') DEFAULT 'N' AFTER student_id;
UPDATE answers SET answered = 'Y' WHERE answer != '';
UPDATE answers SET answered = 'N' WHERE answer = '';

--
-- Question topics is a new feature required for 'duggor'.
--
CREATE TABLE `topics` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `exam_id` int(11) NOT NULL,
        `name` varchar(50) NOT NULL,
        `randomize` int(11) NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

INSERT INTO topics(exam_id, name) SELECT DISTINCT exam_id, 'standard' FROM questions;
ALTER TABLE `topics` ADD CONSTRAINT `topics_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`)
ALTER TABLE `questions` ADD `topic_id` int(11) NOT NULL AFTER exam_id;
UPDATE questions q SET topic_id = (SELECT id FROM topics t WHERE q.exam_id = t.exam_id);
ALTER TABLE `questions` ADD CONSTRAINT `questions_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`);

--
-- Change storage engine to InnoDB for these tables:
--
ALTER TABLE `computers` ENGINE=InnoDB;
ALTER TABLE `locks` ENGINE=InnoDB;

--
-- Add missing foreign keys for locking.
--
ALTER TABLE `computers` ADD CONSTRAINT `computers_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`);
ALTER TABLE `locks` ADD CONSTRAINT `locks_ibfk_1` FOREIGN KEY (`computer_id`) REFERENCES `computers` (`id`);
ALTER TABLE `locks` ADD CONSTRAINT `locks_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`);

ALTER TABLE `exams` MODIFY `lockdown` enum('Y','N') NOT NULL DEFAULT 'Y';
ALTER TABLE `exams` MODIFY `name` varchar(200) NOT NULL;
ALTER TABLE `exams` MODIFY `orgunit` varchar(150) NOT NULL;

--
-- Increment the schema version.
--
UPDATE schemainfo SET major = 1, minor = 0;
