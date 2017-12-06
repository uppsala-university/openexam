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
ALTER TABLE exams MODIFY enquiry enum('Y','N') NOT NULL DEFAULT 'N' AFTER details;
