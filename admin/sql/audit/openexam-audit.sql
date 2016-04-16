-- MySQL dump 10.13  Distrib 5.6.28, for Linux (x86_64)
--
-- Host: localhost    Database: openexam_audit
-- ------------------------------------------------------
-- Server version	5.6.28-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `access`
--

DROP TABLE IF EXISTS `access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `access` (
  `id` int(11) DEFAULT NULL,
  `model` varchar(20) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` int(11) DEFAULT NULL,
  `model` varchar(20) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `answers`
--

DROP TABLE IF EXISTS `answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `answers` (
  `id` int(11) DEFAULT NULL,
  `model` varchar(20) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `computers`
--

DROP TABLE IF EXISTS `computers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `computers` (
  `id` int(11) DEFAULT NULL,
  `model` varchar(20) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contributors`
--

DROP TABLE IF EXISTS `contributors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contributors` (
  `id` int(11) DEFAULT NULL,
  `model` varchar(20) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `correctors`
--

DROP TABLE IF EXISTS `correctors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `correctors` (
  `id` int(11) DEFAULT NULL,
  `model` varchar(20) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `decoders`
--

DROP TABLE IF EXISTS `decoders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `decoders` (
  `id` int(11) DEFAULT NULL,
  `model` varchar(20) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `exams`
--

DROP TABLE IF EXISTS `exams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exams` (
  `id` int(11) DEFAULT NULL,
  `model` varchar(20) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `files`
--

DROP TABLE IF EXISTS `files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `files` (
  `id` int(11) DEFAULT NULL,
  `model` varchar(20) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `invigilators`
--

DROP TABLE IF EXISTS `invigilators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invigilators` (
  `id` int(11) DEFAULT NULL,
  `model` varchar(20) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `locks`
--

DROP TABLE IF EXISTS `locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `locks` (
  `id` int(11) DEFAULT NULL,
  `model` varchar(20) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `questions`
--

DROP TABLE IF EXISTS `questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `questions` (
  `id` int(11) DEFAULT NULL,
  `model` varchar(20) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `resources`
--

DROP TABLE IF EXISTS `resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resources` (
  `id` int(11) DEFAULT NULL,
  `model` varchar(20) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `results`
--

DROP TABLE IF EXISTS `results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `results` (
  `id` int(11) DEFAULT NULL,
  `model` varchar(20) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rooms`
--

DROP TABLE IF EXISTS `rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rooms` (
  `id` int(11) DEFAULT NULL,
  `model` varchar(20) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` int(11) DEFAULT NULL,
  `model` varchar(20) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(11) DEFAULT NULL,
  `model` varchar(20) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `students` (
  `id` int(11) DEFAULT NULL,
  `model` varchar(20) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teachers` (
  `id` int(11) DEFAULT NULL,
  `model` varchar(20) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `topics`
--

DROP TABLE IF EXISTS `topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `topics` (
  `id` int(11) DEFAULT NULL,
  `model` varchar(20) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2016-04-16 17:26:23
