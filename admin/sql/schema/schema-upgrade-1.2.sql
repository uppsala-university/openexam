/****************************************************************
 * Upgrades OpenExam database schema to version 1.2
 *
 * Author: Anders Lövgren
 * Date:   2012-11-19
 ****************************************************************/

-- 
-- Allow nearly unlimited long answers.
-- 
ALTER TABLE answers MODIFY answer MEDIUMTEXT;

-- 
-- Accomodate for user principal names (user@domain).
-- 
ALTER TABLE admins MODIFY user VARCHAR(30) NOT NULL;
ALTER TABLE contributors MODIFY user VARCHAR(30) NOT NULL;
ALTER TABLE decoders MODIFY user VARCHAR(30) NOT NULL;
ALTER TABLE examinators MODIFY user VARCHAR(30) NOT NULL;
ALTER TABLE exams MODIFY creator VARCHAR(30) NOT NULL;
ALTER TABLE questions MODIFY user VARCHAR(30) NOT NULL;
ALTER TABLE students MODIFY user VARCHAR(30) NOT NULL;
ALTER TABLE teachers MODIFY user VARCHAR(30) NOT NULL;

--
-- Increment the schema version.
--
UPDATE schemainfo SET major = 1, minor = 2;
