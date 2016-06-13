/****************************************************************
 * Upgrades OpenExam database schema to version 1.1
 *
 * Author: Anders Lövgren
 * Date:   2011-06-28
 ****************************************************************/

-- 
-- Details about exam to expose.
-- 
ALTER TABLE `exams` ADD `details` int(11) NOT NULL DEFAULT '3' AFTER `creator`;

-- 
-- Don't use lockdown mode by default.
--
ALTER TABLE `exams` MODIFY `lockdown` enum('Y','N') DEFAULT 'N';

-- 
-- Fix schemainfo.
-- 
ALTER TABLE `schemainfo` DROP `id`;
ALTER TABLE `schemainfo` DROP `updated`;
ALTER TABLE `schemainfo` MODIFY `major` int(11) NOT NULL DEFAULT '0';
ALTER TABLE `schemainfo` MODIFY `minor` int(11) NOT NULL DEFAULT '0';

--
-- Increment the schema version.
--
UPDATE schemainfo SET major = 1, minor = 1;
