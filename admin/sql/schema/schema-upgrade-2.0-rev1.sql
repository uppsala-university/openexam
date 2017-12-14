/****************************************************************
 * Upgrades OpenExam database schema to version 2.0
 *
 * Author: Anders Lövgren
 * Date:   2017-12-06
 ****************************************************************/

/**
 * Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
 * Created: Dec 6, 2017
 */

-- 
-- Add question_id field in results:
-- 
ALTER TABLE results ADD question_id INT NOT NULL AFTER corrector_id;

-- 
-- Duplicate question_id from answers in results:
-- 
UPDATE results INNER JOIN answers 
    ON results.answer_id = answers.id 
    SET results.question_id = answers.question_id;

-- 
-- Add foreign key:
-- 
ALTER TABLE results ADD CONSTRAINT FOREIGN KEY (question_id) 
    REFERENCES questions(id) ON DELETE CASCADE;

-- 
-- Add enquiry enum field:
-- 
ALTER TABLE exams ADD enquiry enum('Y','N') NOT NULL DEFAULT 'N' AFTER details;
ALTER TABLE students ADD enquiry enum('Y','N') NOT NULL DEFAULT 'N' AFTER tag;

-- 
-- The render table with foreign key constraint:
-- 
CREATE TABLE render(
    id int(11) NOT NULL AUTO_INCREMENT, 
    exam_id int(11) NOT NULL, 
    queued timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    finish datetime DEFAULT NULL,
    user varchar(60) NOT NULL, 
    url varchar(300) NOT NULL, 
    path varchar(120),
    wait int(11) NOT NULL DEFAULT '0',
    type enum('result','archive','export','extern') NOT NULL DEFAULT 'result', 
    status enum('missing','queued','render','finish','failed') NOT NULL DEFAULT 'missing', 
    message varchar(100) DEFAULT NULL, 
    PRIMARY KEY (id)
);

ALTER TABLE render ADD CONSTRAINT FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE;
