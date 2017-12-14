-- MySQL dump 10.13  Distrib 5.6.38, for Linux (x86_64)
--
-- Host: localhost    Database: openexam2prod_audit
-- ------------------------------------------------------
-- Server version	5.6.38-log

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
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `res` varchar(20) DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `user` (`user`),
  KEY `rid` (`rid`),
  KEY `res` (`res`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `res` varchar(20) DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `user` (`user`),
  KEY `rid` (`rid`),
  KEY `res` (`res`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `answer`
--

DROP TABLE IF EXISTS `answer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `answer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `res` varchar(20) DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `user` (`user`),
  KEY `rid` (`rid`),
  KEY `res` (`res`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `audit`
--

DROP TABLE IF EXISTS `audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `computer`
--

DROP TABLE IF EXISTS `computer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `computer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `res` varchar(20) DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `user` (`user`),
  KEY `rid` (`rid`),
  KEY `res` (`res`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contributor`
--

DROP TABLE IF EXISTS `contributor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contributor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `res` varchar(20) DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `user` (`user`),
  KEY `rid` (`rid`),
  KEY `res` (`res`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `corrector`
--

DROP TABLE IF EXISTS `corrector`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `corrector` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `res` varchar(20) DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `user` (`user`),
  KEY `rid` (`rid`),
  KEY `res` (`res`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `decoder`
--

DROP TABLE IF EXISTS `decoder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `decoder` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `res` varchar(20) DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `user` (`user`),
  KEY `rid` (`rid`),
  KEY `res` (`res`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `exam`
--

DROP TABLE IF EXISTS `exam`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `res` varchar(20) DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `user` (`user`),
  KEY `rid` (`rid`),
  KEY `res` (`res`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `file`
--

DROP TABLE IF EXISTS `file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `res` varchar(20) DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `user` (`user`),
  KEY `rid` (`rid`),
  KEY `res` (`res`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `invigilator`
--

DROP TABLE IF EXISTS `invigilator`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invigilator` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `res` varchar(20) DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `user` (`user`),
  KEY `rid` (`rid`),
  KEY `res` (`res`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lock`
--

DROP TABLE IF EXISTS `lock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `res` varchar(20) DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `user` (`user`),
  KEY `rid` (`rid`),
  KEY `res` (`res`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `question`
--

DROP TABLE IF EXISTS `question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `question` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `res` varchar(20) DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `user` (`user`),
  KEY `rid` (`rid`),
  KEY `res` (`res`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `render`
--

DROP TABLE IF EXISTS `render`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `render` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `res` varchar(20) DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `user` (`user`),
  KEY `rid` (`rid`),
  KEY `res` (`res`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `resource`
--

DROP TABLE IF EXISTS `resource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resource` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `res` varchar(20) DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `user` (`user`),
  KEY `rid` (`rid`),
  KEY `res` (`res`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `result`
--

DROP TABLE IF EXISTS `result`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `result` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `res` varchar(20) DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `user` (`user`),
  KEY `rid` (`rid`),
  KEY `res` (`res`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `room`
--

DROP TABLE IF EXISTS `room`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `room` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `res` varchar(20) DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `user` (`user`),
  KEY `rid` (`rid`),
  KEY `res` (`res`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `session`
--

DROP TABLE IF EXISTS `session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `res` varchar(20) DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `user` (`user`),
  KEY `rid` (`rid`),
  KEY `res` (`res`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `setting`
--

DROP TABLE IF EXISTS `setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `res` varchar(20) DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `user` (`user`),
  KEY `rid` (`rid`),
  KEY `res` (`res`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `student`
--

DROP TABLE IF EXISTS `student`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `res` varchar(20) DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `user` (`user`),
  KEY `rid` (`rid`),
  KEY `res` (`res`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `teacher`
--

DROP TABLE IF EXISTS `teacher`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teacher` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `res` varchar(20) DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `user` (`user`),
  KEY `rid` (`rid`),
  KEY `res` (`res`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `topic`
--

DROP TABLE IF EXISTS `topic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `topic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `res` varchar(20) DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `user` (`user`),
  KEY `rid` (`rid`),
  KEY `res` (`res`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `res` varchar(20) DEFAULT NULL,
  `rid` int(11) DEFAULT NULL,
  `type` char(6) DEFAULT NULL,
  `user` varchar(60) DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `changes` mediumblob,
  PRIMARY KEY (`id`),
  KEY `time` (`time`),
  KEY `user` (`user`),
  KEY `rid` (`rid`),
  KEY `res` (`res`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-12-07 16:01:54
