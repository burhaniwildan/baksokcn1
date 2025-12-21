/*
SQLyog Community v13.2.1 (64 bit)
MySQL - 10.4.32-MariaDB : Database - baksokcn
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`baksokcn` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `baksokcn`;

/*Table structure for table `detail_transaksi` */

DROP TABLE IF EXISTS `detail_transaksi`;

CREATE TABLE `detail_transaksi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_transaksi` int(11) NOT NULL,
  `id_menu` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga_satuan` double NOT NULL,
  `subtotal` double NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_transaksi` (`id_transaksi`),
  KEY `id_menu` (`id_menu`),
  CONSTRAINT `detail_transaksi_ibfk_1` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `detail_transaksi_ibfk_2` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `detail_transaksi` */

insert  into `detail_transaksi`(`id`,`id_transaksi`,`id_menu`,`jumlah`,`harga_satuan`,`subtotal`) values 
(1,1,1,3,2000,6000),
(2,2,1,2,2000,4000),
(3,3,1,3,2000,6000),
(4,4,1,3,2000,6000),
(5,5,1,2,2000,4000),
(6,6,1,5,2000,10000),
(7,7,1,5,2000,10000),
(8,8,1,3,2000,6000),
(9,9,1,5,2000,10000),
(10,10,1,4,2000,8000),
(11,11,1,6,2000,12000),
(12,12,1,3,2000,6000),
(13,13,1,7,2000,14000),
(14,14,1,7,2000,14000),
(15,15,1,2,2000,4000),
(16,16,1,3,2000,6000),
(17,17,4,1,10000,10000),
(18,17,3,1,4000,4000),
(19,18,1,3,2000,6000),
(20,18,5,3,3000,9000),
(21,18,2,3,3000,9000),
(22,19,3,2,4000,8000),
(23,19,4,2,10000,20000),
(24,20,4,3,10000,30000),
(25,20,3,1,4000,4000),
(26,20,2,1,3000,3000),
(27,21,1,39,2000,78000),
(28,21,5,60,3000,180000),
(29,22,3,1,4000,4000),
(30,22,4,2,10000,20000),
(31,23,1,5,2000,10000),
(32,24,1,5,2000,10000),
(33,25,3,2,4000,8000),
(34,25,4,3,10000,30000),
(35,25,1,1,2000,2000),
(36,26,4,4,10000,40000),
(37,27,4,1,10000,10000),
(38,27,1,5,2000,10000),
(39,28,3,4,4000,16000),
(40,29,3,3,4000,12000);

/*Table structure for table `kategori` */

DROP TABLE IF EXISTS `kategori`;

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama_kategori` (`nama_kategori`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `kategori` */

insert  into `kategori`(`id`,`nama_kategori`) values 
(1,'Makanan'),
(2,'Minuman');

/*Table structure for table `menu` */

DROP TABLE IF EXISTS `menu`;

CREATE TABLE `menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_menu` varchar(150) NOT NULL,
  `harga` double NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `id_kategori` int(11) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_kategori` (`id_kategori`),
  CONSTRAINT `menu_ibfk_1` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `menu` */

insert  into `menu`(`id`,`nama_menu`,`harga`,`stok`,`id_kategori`,`gambar`) values 
(1,'Bakso Halus',2000,102,1,'6946db02ba50e.jpg'),
(2,'Es Teh',3000,96,2,'6946db1ab4976.jpg'),
(3,'Es Jeruk',4000,87,2,'6946db2bc9a9b.jpg'),
(4,'Bakso Komplit',10000,85,1,'6946db46e314b.jpg'),
(5,'Tahu Bakso',3000,37,1,'6946db7a33cf2.jpg');

/*Table structure for table `orders` */

DROP TABLE IF EXISTS `orders`;

CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_transaksi` int(11) NOT NULL,
  `id_pembeli` int(11) NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `jenis_pesanan` enum('dine_in','delivery') NOT NULL DEFAULT 'dine_in',
  PRIMARY KEY (`id`),
  KEY `id_transaksi` (`id_transaksi`),
  KEY `id_pembeli` (`id_pembeli`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`id_pembeli`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `orders` */

insert  into `orders`(`id`,`id_transaksi`,`id_pembeli`,`status`,`created_at`,`jenis_pesanan`) values 
(4,8,2,'received','2025-12-20 01:32:11','dine_in'),
(5,9,2,'cancelled','2025-12-20 01:46:40','dine_in'),
(6,10,2,'cancelled','2025-12-20 01:48:36','dine_in'),
(7,11,2,'cancelled','2025-12-20 02:09:06','dine_in'),
(8,12,2,'cancelled','2025-12-20 02:09:18','dine_in'),
(9,13,2,'received','2025-12-20 02:11:05','dine_in'),
(10,14,2,'received','2025-12-20 17:51:25','dine_in'),
(11,15,2,'received','2025-12-20 23:45:16','dine_in'),
(12,16,2,'received','2025-12-20 23:52:38','dine_in'),
(13,17,2,'cancelled','2025-12-21 00:24:24','dine_in'),
(14,18,2,'received','2025-12-21 00:24:34','dine_in'),
(15,19,2,'completed','2025-12-21 01:01:33','dine_in'),
(16,20,2,'completed','2025-12-21 01:02:26','dine_in'),
(17,21,2,'completed','2025-12-21 01:02:42','dine_in'),
(18,22,2,'completed','2025-12-21 23:37:29','dine_in'),
(19,24,2,'pending','2025-12-21 23:47:28','dine_in'),
(20,25,2,'pending','2025-12-21 23:48:04','dine_in'),
(21,26,2,'pending','2025-12-21 23:57:52','dine_in'),
(22,27,2,'pending','2025-12-22 00:10:06','dine_in'),
(23,28,2,'pending','2025-12-22 00:10:11','dine_in'),
(24,29,2,'pending','2025-12-22 00:10:15','dine_in');

/*Table structure for table `restock` */

DROP TABLE IF EXISTS `restock`;

CREATE TABLE `restock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_menu` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `total_harga` double NOT NULL,
  `tanggal` datetime NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `id_admin` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_menu` (`id_menu`),
  KEY `id_admin` (`id_admin`),
  CONSTRAINT `restock_ibfk_1` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `restock_ibfk_2` FOREIGN KEY (`id_admin`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `restock` */

insert  into `restock`(`id`,`id_menu`,`jumlah`,`total_harga`,`tanggal`,`keterangan`,`id_admin`) values 
(1,1,100,100000,'0000-00-00 00:00:00','ebtek bro',1);

/*Table structure for table `role` */

DROP TABLE IF EXISTS `role`;

CREATE TABLE `role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_role` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama_role` (`nama_role`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `role` */

insert  into `role`(`id`,`nama_role`) values 
(1,'admin'),
(2,'pembeli');

/*Table structure for table `transaksi` */

DROP TABLE IF EXISTS `transaksi`;

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `total` double NOT NULL,
  `pembayaran` double NOT NULL,
  `kembalian` double NOT NULL,
  `id_admin` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_admin` (`id_admin`),
  CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `transaksi` */

insert  into `transaksi`(`id`,`tanggal`,`total`,`pembayaran`,`kembalian`,`id_admin`) values 
(1,'2025-12-19',6000,6000,0,1),
(2,'2025-12-19',4000,0,0,NULL),
(3,'2025-12-19',6000,0,0,NULL),
(4,'2025-12-19',6000,0,0,NULL),
(5,'2025-12-19',4000,10000,6000,1),
(6,'2025-12-19',10000,10000,0,NULL),
(7,'2025-12-19',10000,100000,90000,NULL),
(8,'2025-12-19',6000,6000,0,NULL),
(9,'2025-12-19',10000,10000,0,NULL),
(10,'2025-12-19',8000,10000,2000,NULL),
(11,'2025-12-19',12000,12000,0,NULL),
(12,'2025-12-19',6000,6000,0,NULL),
(13,'2025-12-19',14000,14000,0,NULL),
(14,'2025-12-20',14000,14000,0,NULL),
(15,'2025-12-20',4000,4000,0,NULL),
(16,'2025-12-20',6000,6000,0,NULL),
(17,'2025-12-20',14000,14000,0,NULL),
(18,'2025-12-20',24000,24000,0,NULL),
(19,'2025-12-20',28000,28000,0,NULL),
(20,'2025-12-20',37000,37000,0,NULL),
(21,'2025-12-20',258000,258000,0,NULL),
(22,'2025-12-21',24000,24000,0,NULL),
(23,'2025-12-21',30000,30000,0,NULL),
(24,'2025-12-21',10000,10000,0,NULL),
(25,'2025-12-21',40000,40000,0,NULL),
(26,'2025-12-21',40000,40000,0,NULL),
(27,'2025-12-21',20000,20000,0,NULL),
(28,'2025-12-21',16000,16000,0,NULL),
(29,'2025-12-21',12000,12000,0,NULL);

/*Table structure for table `users` */

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*Data for the table `users` */

insert  into `users`(`id`,`nama`,`username`,`password`,`role_id`,`created_at`) values 
(1,'Admin Baksokcn','admin','$2y$10$dTCv9J4TAJpGzxxpJsZKTOjarWZQAIGdQYfWxNGluPzpA2PWEB2bG',1,'2025-12-16 17:22:54'),
(2,'Pembeli Umum','pembeli','$2y$10$JbAn/4J.4ypZ71lzpDkwqu9aErpyVtKI/gI2GdUQSeGHjdo3i1LoO',2,'2025-12-16 17:22:54');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
