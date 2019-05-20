-- MySQL dump 10.13  Distrib 5.7.22, for Linux (x86_64)
--
-- Host: 47.104.10.44    Database: shop
-- ------------------------------------------------------
-- Server version	5.7.22-log

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
-- Dumping data for table `admin_menu`
--

LOCK TABLES `admin_menu` WRITE;
/*!40000 ALTER TABLE `admin_menu` DISABLE KEYS */;
INSERT INTO `admin_menu` VALUES (1,0,1,'首页','fa-bar-chart','/',NULL,NULL,'2019-05-08 17:31:14'),(2,0,7,'系统管理','fa-tasks',NULL,NULL,NULL,'2019-05-20 14:17:21'),(3,2,8,'管理员','fa-users','auth/users',NULL,NULL,'2019-05-20 14:17:21'),(4,2,9,'角色','fa-user','auth/roles',NULL,NULL,'2019-05-20 14:17:22'),(5,2,10,'权限','fa-ban','auth/permissions',NULL,NULL,'2019-05-20 14:17:22'),(6,2,11,'菜单','fa-bars','auth/menu',NULL,NULL,'2019-05-20 14:17:22'),(7,2,12,'操作日志','fa-history','auth/logs',NULL,NULL,'2019-05-20 14:17:22'),(8,0,2,'用户管理','fa-user','/users',NULL,'2019-05-08 17:35:00','2019-05-08 17:40:34'),(9,0,5,'商品管理','fa-cubes','/products',NULL,'2019-05-08 17:49:36','2019-05-20 14:17:21'),(10,0,6,'订单管理','fa-rmb','/orders',NULL,'2019-05-11 17:45:12','2019-05-20 14:17:21'),(11,0,4,'优惠价管理','fa-tags','/coupon_codes',NULL,'2019-05-15 14:52:00','2019-05-20 14:17:21'),(12,0,3,'类目管理','fa-bars','/categories',NULL,'2019-05-20 14:16:53','2019-05-20 14:17:21');
/*!40000 ALTER TABLE `admin_menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `admin_permissions`
--

LOCK TABLES `admin_permissions` WRITE;
/*!40000 ALTER TABLE `admin_permissions` DISABLE KEYS */;
INSERT INTO `admin_permissions` VALUES (1,'All permission','*','','*',NULL,NULL),(2,'Dashboard','dashboard','GET','/',NULL,NULL),(3,'Login','auth.login','','/auth/login\r\n/auth/logout',NULL,NULL),(4,'User setting','auth.setting','GET,PUT','/auth/setting',NULL,NULL),(5,'Auth management','auth.management','','/auth/roles\r\n/auth/permissions\r\n/auth/menu\r\n/auth/logs',NULL,NULL),(6,'用户管理','users','','/users*','2019-05-08 17:44:22','2019-05-08 17:44:22'),(7,'商品管理','products','','/products*','2019-05-16 00:21:01','2019-05-16 00:21:01'),(8,'优惠券管理','coupon_codes','','/coupon_codes*','2019-05-16 00:21:39','2019-05-16 00:21:39'),(9,'订单管理','orders','','/orders*','2019-05-16 00:22:02','2019-05-16 00:22:02');
/*!40000 ALTER TABLE `admin_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `admin_role_menu`
--

LOCK TABLES `admin_role_menu` WRITE;
/*!40000 ALTER TABLE `admin_role_menu` DISABLE KEYS */;
INSERT INTO `admin_role_menu` VALUES (1,2,NULL,NULL);
/*!40000 ALTER TABLE `admin_role_menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `admin_role_permissions`
--

LOCK TABLES `admin_role_permissions` WRITE;
/*!40000 ALTER TABLE `admin_role_permissions` DISABLE KEYS */;
INSERT INTO `admin_role_permissions` VALUES (1,1,NULL,NULL),(2,2,NULL,NULL),(2,3,NULL,NULL),(2,4,NULL,NULL),(2,6,NULL,NULL),(2,7,NULL,NULL),(2,8,NULL,NULL),(2,9,NULL,NULL);
/*!40000 ALTER TABLE `admin_role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `admin_role_users`
--

LOCK TABLES `admin_role_users` WRITE;
/*!40000 ALTER TABLE `admin_role_users` DISABLE KEYS */;
INSERT INTO `admin_role_users` VALUES (1,1,NULL,NULL),(2,2,NULL,NULL);
/*!40000 ALTER TABLE `admin_role_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `admin_roles`
--

LOCK TABLES `admin_roles` WRITE;
/*!40000 ALTER TABLE `admin_roles` DISABLE KEYS */;
INSERT INTO `admin_roles` VALUES (1,'Administrator','administrator','2019-05-07 17:53:41','2019-05-07 17:53:41'),(2,'运营','operation','2019-05-08 17:44:53','2019-05-08 17:44:53');
/*!40000 ALTER TABLE `admin_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `admin_user_permissions`
--

LOCK TABLES `admin_user_permissions` WRITE;
/*!40000 ALTER TABLE `admin_user_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_user_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `admin_users`
--

LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES (1,'admin','$2y$10$TbMuTFhNJig.s0csl/ceku/FjppZsVvayF6rwWYhamvsKAKoMVbe2','Administrator',NULL,'q6WWenB3poVtoNexhJ1qJT24mxSXeGIYL0uFOpeACw1vPENNitzLg5SLrQT5','2019-05-07 17:53:41','2019-05-07 17:53:41'),(2,'operator','$2y$10$GN7AHJbZuHG2Y7pYvAaoGegbRllpFTLkNdhFEicxc0MP5hR1ozmji','运营',NULL,'VhhLuIEbMWu6cgi9TAPpEVd33H0jpqsFgLfSwm5jZY1SD7FHyxNibq1ErHlO','2019-05-08 17:45:29','2019-05-08 17:45:29');
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-05-20 22:42:43
