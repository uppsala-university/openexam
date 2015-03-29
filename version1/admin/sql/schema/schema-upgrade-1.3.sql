/****************************************************************
 * Upgrades OpenExam database schema to version 1.3
 *
 * Author: Anders Lövgren
 * Date:   2014-04-02
 ****************************************************************/

-- 
-- For tagging/separation of students.
-- 
ALTER TABLE students ADD tag VARCHAR(30);

--
-- Increment the schema version.
--
UPDATE schemainfo SET major = 1, minor = 3;
