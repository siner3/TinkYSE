-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 20, 2025 at 08:28 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tink_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `ADMIN_ID` int(11) NOT NULL,
  `ADMIN_NAME` varchar(100) NOT NULL,
  `ADMIN_USERNAME` varchar(50) NOT NULL,
  `ADMIN_PASSWORD` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`ADMIN_ID`, `ADMIN_NAME`, `ADMIN_USERNAME`, `ADMIN_PASSWORD`) VALUES
(1, 'Super Admin', 'admin', '$2y$10$d9jWWZhrxsIgO5juFO4Y2OaVxnNQeV1vfT0dtBikwYUlSL19PiQD2');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `CART_ID` int(11) NOT NULL,
  `CUSTOMER_ID` int(11) NOT NULL,
  `CART_STATUS` varchar(20) NOT NULL DEFAULT 'active'
) ;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`CART_ID`, `CUSTOMER_ID`, `CART_STATUS`) VALUES
(4001, 1001, 'active'),
(4002, 1002, 'active'),
(4003, 1003, 'converted'),
(4004, 1004, 'active'),
(4005, 1005, 'active'),
(4006, 1006, 'abandoned'),
(4007, 1007, 'active'),
(4008, 1008, 'converted'),
(4009, 1009, 'active'),
(4010, 1010, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `cartitem`
--

CREATE TABLE `cartitem` (
  `CARTITEM_ID` int(11) NOT NULL,
  `CART_ID` int(11) NOT NULL,
  `ITEM_ID` int(11) NOT NULL,
  `CARTITEM_QUANTITY` int(11) NOT NULL,
  `CARTITEM_PRICE` decimal(10,2) NOT NULL,
  `CARTITEM_ENGRAVING` varchar(255) DEFAULT NULL
) ;

--
-- Dumping data for table `cartitem`
--

INSERT INTO `cartitem` (`CARTITEM_ID`, `CART_ID`, `ITEM_ID`, `CARTITEM_QUANTITY`, `CARTITEM_PRICE`, `CARTITEM_ENGRAVING`) VALUES
(1, 4001, 2001, 2, 89.99, NULL),
(2, 4001, 2003, 1, 65.50, NULL),
(3, 4002, 2005, 1, 74.99, NULL),
(4, 4003, 2001, 1, 89.99, NULL),
(5, 4003, 2002, 1, 59.99, NULL),
(6, 4004, 2004, 1, 299.99, NULL),
(7, 4005, 2006, 2, 54.99, NULL),
(8, 4006, 2007, 3, 39.99, NULL),
(9, 4007, 2009, 1, 79.99, NULL),
(10, 4008, 2002, 1, 59.99, NULL),
(11, 4008, 2010, 2, 44.99, NULL),
(12, 4009, 2008, 1, 89.99, NULL),
(13, 4010, 2005, 1, 74.99, NULL),
(14, 4010, 2007, 1, 39.99, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cartitem_charm`
--

CREATE TABLE `cartitem_charm` (
  `ID` int(11) NOT NULL,
  `CARTITEM_ID` int(11) NOT NULL,
  `CHARM_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `charm`
--

CREATE TABLE `charm` (
  `CHARM_ID` int(11) NOT NULL,
  `CHARM_NAME` varchar(100) NOT NULL,
  `CHARM_TYPE` varchar(50) NOT NULL,
  `CHARM_MATERIAL` varchar(100) NOT NULL,
  `CHARM_PRICE` decimal(10,2) NOT NULL,
  `CHARM_COMPATIBLE_CAT` varchar(100) NOT NULL,
  `CHARM_IMAGE` varchar(255) DEFAULT NULL,
  `CHARM_ACTIVE` tinyint(1) NOT NULL DEFAULT 1
) ;

--
-- Dumping data for table `charm`
--

INSERT INTO `charm` (`CHARM_ID`, `CHARM_NAME`, `CHARM_TYPE`, `CHARM_MATERIAL`, `CHARM_PRICE`, `CHARM_COMPATIBLE_CAT`, `CHARM_IMAGE`, `CHARM_ACTIVE`) VALUES
(11001, 'Heart Charm', 'Heart', '925 Sterling Silver', 15.00, 'Bracelets', '/images/charms/charm_11001.jpg', 1),
(11002, 'Star Charm', 'Star', '925 Sterling Silver', 12.00, 'Bracelets', '/images/charms/charm_11002.jpg', 1),
(11003, 'Moon Charm', 'Moon', '925 Sterling Silver', 14.00, 'Bracelets', '/images/charms/charm_11003.jpg', 1),
(11004, 'Flower Charm', 'Flower', '925 Sterling Silver', 13.00, 'Bracelets', '/images/charms/charm_11004.jpg', 1),
(11005, 'Letter A Charm', 'Letter', '925 Sterling Silver', 10.00, 'Bracelets', '/images/charms/charm_11005.jpg', 1),
(11006, 'Letter B Charm', 'Letter', '925 Sterling Silver', 10.00, 'Bracelets', '/images/charms/charm_11006.jpg', 1),
(11007, 'Love Charm', 'Word', '925 Sterling Silver', 16.00, 'Bracelets', '/images/charms/charm_11007.jpg', 1),
(11008, 'Crown Charm', 'Crown', '925 Sterling Silver', 18.00, 'Bracelets', '/images/charms/charm_11008.jpg', 1),
(11009, 'Butterfly Charm', 'Butterfly', '925 Sterling Silver', 14.50, 'Bracelets', '/images/charms/charm_11009.jpg', 1),
(11010, 'Cross Charm', 'Cross', '925 Sterling Silver', 12.00, 'Bracelets', '/images/charms/charm_11010.jpg', 1);

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `CUSTOMER_ID` int(11) NOT NULL,
  `CUSTOMER_NAME` varchar(100) NOT NULL,
  `CUSTOMER_EMAIL` varchar(100) NOT NULL,
  `CUSTOMER_PW` varchar(255) NOT NULL,
  `CUSTOMER_TEL` varchar(15) NOT NULL,
  `CUSTOMER_ADDRESS` varchar(255) NOT NULL,
  `CUSTOMER_DATE` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`CUSTOMER_ID`, `CUSTOMER_NAME`, `CUSTOMER_EMAIL`, `CUSTOMER_PW`, `CUSTOMER_TEL`, `CUSTOMER_ADDRESS`, `CUSTOMER_DATE`) VALUES
(1001, 'Nur Aini', 'nur.aini@email.com', '$2y$10$dummy', '012-345-6789', '123 Jalan Merdeka, KL', '2025-01-10 08:30:00'),
(1002, 'Siti Farah', 'siti.farah@email.com', '$2y$10$dummy', '011-222-3333', '456 Persiaran Sultan, KL', '2025-01-11 09:15:00'),
(1003, 'Fatimah Zahra', 'fatimah.z@email.com', '$2y$10$dummy', '016-444-5555', '789 Jalan Bukit, KL', '2025-01-12 10:45:00'),
(1004, 'Yasmin Sofiya', 'yasmin.s@email.com', '$2y$10$dummy', '013-666-7777', '321 Jalan Ampang, KL', '2025-01-13 11:20:00'),
(1005, 'Leila Nazira', 'leila.n@email.com', '$2y$10$dummy', '017-888-9999', '654 Jalan Kebun, KL', '2025-01-14 12:30:00'),
(1006, 'Amira Putri', 'amira.p@email.com', '$2y$10$dummy', '010-111-2222', '987 Jalan Damansara, KL', '2025-01-15 13:45:00'),
(1007, 'Nadia Husna', 'nadia.h@email.com', '$2y$10$dummy', '014-333-4444', '111 Jalan Kota, KL', '2025-01-16 14:15:00'),
(1008, 'Hana Salma', 'hana.s@email.com', '$2y$10$dummy', '015-555-6666', '222 Jalan Sultan, KL', '2025-01-17 15:30:00'),
(1009, 'Zara Eka', 'zara.e@email.com', '$2y$10$dummy', '012-777-8888', '333 Jalan Taming, KL', '2025-01-18 16:45:00'),
(1010, 'Maya Ilya', 'maya.i@email.com', '$2y$10$dummy', '011-999-0000', '444 Jalan Raja, KL', '2025-01-19 17:20:00'),
(1011, 'Omar Harris', 'omar.h@email.com', '$2y$10$dummy', '019-123-4567', '88 Jalan Tun Razak, KL', '2025-02-01 09:00:00'),
(1012, 'Jessica Lim', 'jess.lim@email.com', '$2y$10$dummy', '012-987-6543', '12 Taman Tun, KL', '2025-02-02 10:15:00'),
(1013, 'Ravi Kumar', 'ravi.k@email.com', '$2y$10$dummy', '016-555-0101', '45 Brickfields, KL', '2025-02-05 11:30:00'),
(1014, 'Mei Ling', 'mei.ling@email.com', '$2y$10$dummy', '017-333-2222', 'Subang Jaya, SEL', '2025-02-10 14:00:00'),
(1015, 'Sarah Ahmad', 'sarah.ah@email.com', '$2y$10$dummy', '013-444-5555', 'Shah Alam, SEL', '2025-02-12 16:45:00'),
(1016, 'David Tan', 'david.t@email.com', '$2y$10$dummy', '011-111-9999', 'Petaling Jaya, SEL', '2025-02-15 09:20:00'),
(1017, 'Priya Menon', 'priya.m@email.com', '$2y$10$dummy', '012-222-8888', 'Bangsar, KL', '2025-02-20 12:10:00'),
(1018, 'Alex Wong', 'alex.w@email.com', '$2y$10$dummy', '010-666-7777', 'Cheras, KL', '2025-03-01 15:30:00'),
(1019, 'Nina Ricci', 'nina.r@email.com', '$2y$10$dummy', '018-777-6666', 'Mont Kiara, KL', '2025-03-05 10:00:00'),
(1020, 'Kenji Sato', 'kenji.s@email.com', '$2y$10$dummy', '019-888-5555', 'Desa Park City, KL', '2025-03-10 11:15:00'),
(1021, 'Alice Cooper', 'alice.c@email.com', '$2y$10$dummy', '017-999-4444', 'Ampang Hilir, KL', '2025-03-15 13:45:00'),
(1022, 'Bob Marley', 'bob.m@email.com', '$2y$10$dummy', '012-000-1111', 'Setia Alam, SEL', '2025-03-20 09:30:00'),
(1023, 'Cindy Crawford', 'cindy.c@email.com', '$2y$10$dummy', '013-121-2121', 'Klang, SEL', '2025-03-25 14:20:00'),
(1024, 'Daniel Craig', 'daniel.c@email.com', '$2y$10$dummy', '014-343-4343', 'Puchong, SEL', '2025-04-01 16:00:00'),
(1025, 'Eva Green', 'eva.g@email.com', '$2y$10$dummy', '015-565-6565', 'Cyberjaya, SEL', '2025-04-05 10:30:00'),
(1026, 'Frank Sinatra', 'frank.s@email.com', '$2y$10$dummy', '016-787-8787', 'Putrajaya', '2025-04-10 11:50:00'),
(1027, 'Grace Kelly', 'grace.k@email.com', '$2y$10$dummy', '017-909-0909', 'Sepang, SEL', '2025-04-15 13:10:00'),
(1028, 'Harry Potter', 'harry.p@email.com', '$2y$10$dummy', '018-123-3210', 'Gombak, SEL', '2025-04-20 15:40:00'),
(1029, 'Iris West', 'iris.w@email.com', '$2y$10$dummy', '019-456-6540', 'Selayang, SEL', '2025-04-25 09:15:00'),
(1030, 'Jack Sparrow', 'jack.s@email.com', '$2y$10$dummy', '011-789-9870', 'Port Klang, SEL', '2025-04-30 14:55:00');

-- --------------------------------------------------------

--
-- Table structure for table `designer`
--

CREATE TABLE `designer` (
  `DESIGNER_ID` int(11) NOT NULL,
  `DESIGNER_NAME` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `designer`
--

INSERT INTO `designer` (`DESIGNER_ID`, `DESIGNER_NAME`) VALUES
(5, 'Aura Gemstones'),
(2, 'Elegance Jewelry Studio'),
(3, 'Lumiere Fine Jewelry'),
(1, 'Sterling Creations Ltd'),
(4, 'Vintage Vogue Co.');

-- --------------------------------------------------------

--
-- Table structure for table `item`
--

CREATE TABLE `item` (
  `ITEM_ID` int(11) NOT NULL,
  `DESIGNER_ID` int(11) NOT NULL,
  `ITEM_CATEGORY` varchar(50) NOT NULL,
  `ITEM_NAME` varchar(150) NOT NULL,
  `ITEM_DESCRIPTION` varchar(500) NOT NULL,
  `ITEM_MATERIAL` varchar(100) NOT NULL,
  `ITEM_PRICE` decimal(10,2) NOT NULL,
  `ITEM_STOCK` int(11) NOT NULL DEFAULT 0,
  `ITEM_IMAGE` varchar(255) DEFAULT NULL,
  `ITEM_DATE` datetime NOT NULL DEFAULT current_timestamp(),
  `PARENT_ID` int(11) DEFAULT NULL,
  `IS_ENGRAVABLE` tinyint(1) DEFAULT 0,
  `ITEM_TAGS` varchar(255) DEFAULT NULL
) ;

--
-- Dumping data for table `item`
--

INSERT INTO `item` (`ITEM_ID`, `DESIGNER_ID`, `ITEM_CATEGORY`, `ITEM_NAME`, `ITEM_DESCRIPTION`, `ITEM_MATERIAL`, `ITEM_PRICE`, `ITEM_STOCK`, `ITEM_IMAGE`, `ITEM_DATE`, `PARENT_ID`, `IS_ENGRAVABLE`, `ITEM_TAGS`) VALUES
(2001, 1, 'Necklaces', 'Sterling Silver Heart Necklace', 'Elegant heart pendant', '925 Sterling Silver', 89.99, 45, '/images/products/item_2001.jpg', '2025-12-19 22:19:33', NULL, 0, NULL),
(2002, 1, 'Bracelets', 'Build-Your-Own Charm Bracelet', 'Customizable bracelet', '925 Sterling Silver', 59.99, 30, '/images/products/item_2002.jpg', '2025-12-19 22:19:33', NULL, 0, NULL),
(2003, 2, 'Earrings', 'Pearl Drop Earrings', 'Classic pearl drop', '18K Gold Plating', 65.50, 25, '/images/products/item_2003.jpg', '2025-12-19 22:19:33', NULL, 0, NULL),
(2004, 2, 'Rings', 'Diamond Solitaire Ring', 'Elegant engagement ring', '925 Sterling Silver', 299.99, 12, '/images/products/item_2004.jpg', '2025-12-19 22:19:33', NULL, 0, NULL),
(2005, 1, 'Necklaces', 'Gold Infinity Pendant', 'Modern infinity symbol', '18K Gold Plating', 74.99, 20, '/images/products/item_2005.jpg', '2025-12-19 22:19:33', NULL, 0, NULL),
(2006, 2, 'Bracelets', 'Rose Gold Bangle Bracelet', 'Sleek bangle', 'Rose Gold Plating', 54.99, 35, '/images/products/item_2006.jpg', '2025-12-19 22:19:33', NULL, 0, NULL),
(2007, 1, 'Earrings', 'Cubic Zirconia Studs', 'Sparkling studs', 'Cubic Zirconia', 39.99, 50, '/images/products/item_2007.jpg', '2025-12-19 22:19:33', NULL, 0, NULL),
(2008, 2, 'Rings', 'Moonstone Ring', 'Mystical statement ring', 'Silver & Moonstone', 89.99, 15, '/images/products/item_2008.jpg', '2025-12-19 22:19:33', NULL, 0, NULL),
(2009, 1, 'Necklaces', 'Birthstone Pendant Necklace', 'Personalized pendant', '925 Sterling Silver', 79.99, 28, '/images/products/item_2009.jpg', '2025-12-19 22:19:33', NULL, 0, NULL),
(2010, 2, 'Bracelets', 'Beaded Stretch Bracelet', 'Colorful beads', 'Semi-Precious Beads', 44.99, 40, '/images/products/item_2010.jpg', '2025-12-19 22:19:33', NULL, 0, NULL),
(2011, 3, 'Necklaces', 'Sapphire Teardrop Pendant', 'Deep blue sapphire', '18K White Gold', 249.99, 10, '/images/products/item_2011.jpg', '2025-12-19 22:19:33', NULL, 0, NULL),
(2012, 3, 'Earrings', 'Diamond Halo Studs', '0.5 carat diamond', 'Platinum', 499.99, 5, '/images/products/item_2012.jpg', '2025-12-19 22:19:33', NULL, 0, NULL),
(2013, 4, 'Rings', 'Vintage Ruby Ring', 'Art-deco style', 'Gold Vermeil', 129.50, 8, '/images/products/item_2013.jpg', '2025-12-19 22:19:33', NULL, 0, NULL),
(2014, 4, 'Bracelets', 'Emerald Tennis Bracelet', 'Lab grown emeralds', 'Sterling Silver', 189.00, 15, '/images/products/item_2014.jpg', '2025-12-19 22:19:33', NULL, 0, NULL),
(2015, 5, 'Necklaces', 'Amethyst Crystal Choker', 'Bohemian raw amethyst', 'Leather & Silver', 45.00, 30, '/images/products/item_2015.jpg', '2025-12-19 22:19:33', NULL, 0, NULL),
(2016, 5, 'Rings', 'Turquoise Statement Ring', 'Large turquoise stone', 'Recycled Silver', 65.00, 12, '/images/products/item_2016.jpg', '2025-12-19 22:19:33', NULL, 0, NULL),
(2017, 1, 'Earrings', 'Silver Hoop Earrings', 'Classic large hoop', '925 Sterling Silver', 29.99, 50, '/images/products/item_2017.jpg', '2025-12-19 22:19:33', NULL, 0, NULL),
(2018, 2, 'Necklaces', 'Gold Layered Chain', 'Trendy multi-layer', '14K Gold Plating', 39.99, 40, '/images/products/item_2018.jpg', '2025-12-19 22:19:33', NULL, 0, NULL),
(2019, 3, 'Bracelets', 'Pearl Strand Bracelet', 'Freshwater pearls', 'Silk & Gold', 85.00, 20, '/images/products/item_2019.jpg', '2025-12-19 22:19:33', NULL, 0, NULL),
(2020, 4, 'Rings', 'Opal Signet Ring', 'Modern opal inlay', 'Gold Plated', 55.00, 18, '/images/products/item_2020.jpg', '2025-12-19 22:19:33', NULL, 0, NULL),
(2021, 5, 'Earrings', 'Garnet Drop Earrings', 'Red garnet stones', 'Rose Gold', 95.00, 10, '/images/products/item_2021.jpg', '2025-12-19 22:19:33', NULL, 0, NULL),
(2022, 1, 'Bracelets', 'Minimalist Cuff', 'Simple open cuff', 'Stainless Steel', 25.00, 60, '/images/products/item_2022.jpg', '2025-12-19 22:19:33', NULL, 0, NULL),
(2023, 2, 'Necklaces', 'Locket Necklace', 'Vintage style locket', 'Brass', 35.00, 25, '/images/products/item_2023.jpg', '2025-12-19 22:19:33', NULL, 0, NULL),
(2024, 3, 'Rings', 'Stackable Gold Bands', 'Set of 3 bands', '10K Gold', 110.00, 15, '/images/products/item_2024.jpg', '2025-12-19 22:19:33', NULL, 0, NULL),
(2025, 4, 'Earrings', 'Blue Topaz Studs', 'Bright blue topaz', 'Sterling Silver', 49.00, 22, '/images/products/item_2025.jpg', '2025-12-19 22:19:33', NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `itemcharm`
--

CREATE TABLE `itemcharm` (
  `ITEM_ID` int(11) NOT NULL,
  `CHARM_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `itemcharm`
--

INSERT INTO `itemcharm` (`ITEM_ID`, `CHARM_ID`) VALUES
(2002, 11001),
(2002, 11002),
(2002, 11003),
(2002, 11004),
(2002, 11005),
(2002, 11006),
(2002, 11007),
(2002, 11008),
(2006, 11001),
(2006, 11002);

-- --------------------------------------------------------

--
-- Table structure for table `item_gallery`
--

CREATE TABLE `item_gallery` (
  `GALLERY_ID` int(11) NOT NULL,
  `ITEM_ID` int(11) NOT NULL,
  `IMAGE_URL` varchar(255) NOT NULL,
  `IMAGE_TYPE` varchar(20) DEFAULT 'main'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `item_gallery`
--

INSERT INTO `item_gallery` (`GALLERY_ID`, `ITEM_ID`, `IMAGE_URL`, `IMAGE_TYPE`) VALUES
(1, 2001, '/images/products/item_2001.jpg', 'main'),
(2, 2002, '/images/products/item_2002.jpg', 'main'),
(3, 2003, '/images/products/item_2003.jpg', 'main'),
(4, 2004, '/images/products/item_2004.jpg', 'main'),
(5, 2005, '/images/products/item_2005.jpg', 'main'),
(6, 2006, '/images/products/item_2006.jpg', 'main'),
(7, 2007, '/images/products/item_2007.jpg', 'main'),
(8, 2008, '/images/products/item_2008.jpg', 'main'),
(9, 2009, '/images/products/item_2009.jpg', 'main'),
(10, 2010, '/images/products/item_2010.jpg', 'main'),
(11, 2011, '/images/products/item_2011.jpg', 'main'),
(12, 2012, '/images/products/item_2012.jpg', 'main'),
(13, 2013, '/images/products/item_2013.jpg', 'main'),
(14, 2014, '/images/products/item_2014.jpg', 'main'),
(15, 2015, '/images/products/item_2015.jpg', 'main'),
(16, 2016, '/images/products/item_2016.jpg', 'main'),
(17, 2017, '/images/products/item_2017.jpg', 'main'),
(18, 2018, '/images/products/item_2018.jpg', 'main'),
(19, 2019, '/images/products/item_2019.jpg', 'main'),
(20, 2020, '/images/products/item_2020.jpg', 'main'),
(21, 2021, '/images/products/item_2021.jpg', 'main'),
(22, 2022, '/images/products/item_2022.jpg', 'main'),
(23, 2023, '/images/products/item_2023.jpg', 'main'),
(24, 2024, '/images/products/item_2024.jpg', 'main'),
(25, 2025, '/images/products/item_2025.jpg', 'main');

-- --------------------------------------------------------

--
-- Table structure for table `order`
--

CREATE TABLE `order` (
  `ORDER_ID` int(11) NOT NULL,
  `CUSTOMER_ID` int(11) NOT NULL,
  `CART_ID` int(11) DEFAULT NULL,
  `ORDER_DATE` datetime NOT NULL DEFAULT current_timestamp(),
  `ORDER_STATUS` varchar(20) NOT NULL DEFAULT 'pending',
  `ORDER_TOTALAMOUNT` decimal(10,2) NOT NULL
) ;

--
-- Dumping data for table `order`
--

INSERT INTO `order` (`ORDER_ID`, `CUSTOMER_ID`, `CART_ID`, `ORDER_DATE`, `ORDER_STATUS`, `ORDER_TOTALAMOUNT`) VALUES
(6001, 1003, 4003, '2025-01-20 14:30:00', 'shipped', 149.98),
(6002, 1008, 4008, '2025-01-21 15:45:00', 'confirmed', 189.97),
(6003, 1001, 4001, '2025-01-22 16:20:00', 'pending', 245.48);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `PAYMENT_ID` int(11) NOT NULL,
  `ORDER_ID` int(11) NOT NULL,
  `PAYMENT_DATE` datetime NOT NULL DEFAULT current_timestamp(),
  `PAYMENT_AMOUNT` decimal(10,2) NOT NULL,
  `PAYMENT_METHOD` varchar(50) NOT NULL,
  `PAYMENT_STATUS` varchar(20) NOT NULL DEFAULT 'pending'
) ;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`PAYMENT_ID`, `ORDER_ID`, `PAYMENT_DATE`, `PAYMENT_AMOUNT`, `PAYMENT_METHOD`, `PAYMENT_STATUS`) VALUES
(8001, 6001, '2025-01-20 14:35:00', 149.98, 'CreditCard', 'successful'),
(8002, 6002, '2025-01-21 15:50:00', 189.97, 'OnlineBanking', 'successful'),
(8003, 6003, '2025-01-22 16:25:00', 245.48, 'EWallet', 'successful');

-- --------------------------------------------------------

--
-- Table structure for table `review`
--

CREATE TABLE `review` (
  `CUSTOMER_ID` int(11) NOT NULL,
  `ITEM_ID` int(11) NOT NULL,
  `REVIEW_RATING` decimal(2,1) NOT NULL,
  `REVIEW_COMMENT` text NOT NULL,
  `REVIEW_DATE` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `review`
--

INSERT INTO `review` (`CUSTOMER_ID`, `ITEM_ID`, `REVIEW_RATING`, `REVIEW_COMMENT`, `REVIEW_DATE`) VALUES
(1001, 2001, 5.0, 'Beautiful!', '2025-01-20 10:30:00'),
(1002, 2001, 4.5, 'Very nice.', '2025-01-21 11:15:00'),
(1003, 2003, 5.0, 'Stunning.', '2025-01-22 12:45:00'),
(1004, 2002, 4.0, 'Good quality.', '2025-01-23 13:20:00'),
(1005, 2005, 5.0, 'Gorgeous.', '2025-01-24 14:30:00'),
(1006, 2004, 4.5, 'Satisfied.', '2025-01-25 15:45:00'),
(1007, 2006, 5.0, 'Perfect.', '2025-01-26 16:20:00'),
(1008, 2007, 4.0, 'Sparkly.', '2025-01-27 17:10:00'),
(1009, 2009, 5.0, 'Perfect.', '2025-01-28 18:30:00'),
(1010, 2010, 4.5, 'Colorful.', '2025-01-29 19:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`ADMIN_ID`),
  ADD UNIQUE KEY `ADMIN_USERNAME` (`ADMIN_USERNAME`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`CART_ID`),
  ADD UNIQUE KEY `CUSTOMER_ID` (`CUSTOMER_ID`);

--
-- Indexes for table `cartitem`
--
ALTER TABLE `cartitem`
  ADD PRIMARY KEY (`CARTITEM_ID`),
  ADD KEY `FK_CARTITEM_CART` (`CART_ID`),
  ADD KEY `FK_CARTITEM_ITEM` (`ITEM_ID`);

--
-- Indexes for table `cartitem_charm`
--
ALTER TABLE `cartitem_charm`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `CARTITEM_ID` (`CARTITEM_ID`),
  ADD KEY `CHARM_ID` (`CHARM_ID`);

--
-- Indexes for table `charm`
--
ALTER TABLE `charm`
  ADD PRIMARY KEY (`CHARM_ID`),
  ADD UNIQUE KEY `CHARM_NAME` (`CHARM_NAME`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`CUSTOMER_ID`),
  ADD UNIQUE KEY `CUSTOMER_EMAIL` (`CUSTOMER_EMAIL`),
  ADD KEY `IDX_CUST_EMAIL` (`CUSTOMER_EMAIL`);

--
-- Indexes for table `designer`
--
ALTER TABLE `designer`
  ADD PRIMARY KEY (`DESIGNER_ID`),
  ADD UNIQUE KEY `DESIGNER_NAME` (`DESIGNER_NAME`);

--
-- Indexes for table `item`
--
ALTER TABLE `item`
  ADD PRIMARY KEY (`ITEM_ID`),
  ADD KEY `DESIGNER_ID` (`DESIGNER_ID`),
  ADD KEY `IDX_ITEM_NAME` (`ITEM_NAME`),
  ADD KEY `IDX_ITEM_CATEGORY` (`ITEM_CATEGORY`);

--
-- Indexes for table `itemcharm`
--
ALTER TABLE `itemcharm`
  ADD PRIMARY KEY (`ITEM_ID`,`CHARM_ID`),
  ADD KEY `CHARM_ID` (`CHARM_ID`);

--
-- Indexes for table `item_gallery`
--
ALTER TABLE `item_gallery`
  ADD PRIMARY KEY (`GALLERY_ID`),
  ADD KEY `ITEM_ID` (`ITEM_ID`);

--
-- Indexes for table `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`ORDER_ID`),
  ADD KEY `CART_ID` (`CART_ID`),
  ADD KEY `IDX_ORDER_DATE` (`ORDER_DATE`),
  ADD KEY `IDX_ORDER_STATUS` (`ORDER_STATUS`),
  ADD KEY `FK_ORDER_CUSTOMER` (`CUSTOMER_ID`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`PAYMENT_ID`),
  ADD KEY `ORDER_ID` (`ORDER_ID`);

--
-- Indexes for table `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`CUSTOMER_ID`,`ITEM_ID`),
  ADD KEY `ITEM_ID` (`ITEM_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `ADMIN_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `CART_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cartitem`
--
ALTER TABLE `cartitem`
  MODIFY `CARTITEM_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cartitem_charm`
--
ALTER TABLE `cartitem_charm`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `charm`
--
ALTER TABLE `charm`
  MODIFY `CHARM_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `CUSTOMER_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1031;

--
-- AUTO_INCREMENT for table `designer`
--
ALTER TABLE `designer`
  MODIFY `DESIGNER_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `item`
--
ALTER TABLE `item`
  MODIFY `ITEM_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `item_gallery`
--
ALTER TABLE `item_gallery`
  MODIFY `GALLERY_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `order`
--
ALTER TABLE `order`
  MODIFY `ORDER_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `PAYMENT_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`CUSTOMER_ID`) REFERENCES `customer` (`CUSTOMER_ID`) ON DELETE CASCADE;

--
-- Constraints for table `cartitem`
--
ALTER TABLE `cartitem`
  ADD CONSTRAINT `FK_CARTITEM_CART` FOREIGN KEY (`CART_ID`) REFERENCES `cart` (`CART_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_CARTITEM_ITEM` FOREIGN KEY (`ITEM_ID`) REFERENCES `item` (`ITEM_ID`);

--
-- Constraints for table `cartitem_charm`
--
ALTER TABLE `cartitem_charm`
  ADD CONSTRAINT `cartitem_charm_ibfk_1` FOREIGN KEY (`CARTITEM_ID`) REFERENCES `cartitem` (`CARTITEM_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `cartitem_charm_ibfk_2` FOREIGN KEY (`CHARM_ID`) REFERENCES `charm` (`CHARM_ID`);

--
-- Constraints for table `item`
--
ALTER TABLE `item`
  ADD CONSTRAINT `item_ibfk_1` FOREIGN KEY (`DESIGNER_ID`) REFERENCES `designer` (`DESIGNER_ID`);

--
-- Constraints for table `itemcharm`
--
ALTER TABLE `itemcharm`
  ADD CONSTRAINT `itemcharm_ibfk_1` FOREIGN KEY (`ITEM_ID`) REFERENCES `item` (`ITEM_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `itemcharm_ibfk_2` FOREIGN KEY (`CHARM_ID`) REFERENCES `charm` (`CHARM_ID`);

--
-- Constraints for table `item_gallery`
--
ALTER TABLE `item_gallery`
  ADD CONSTRAINT `item_gallery_ibfk_1` FOREIGN KEY (`ITEM_ID`) REFERENCES `item` (`ITEM_ID`) ON DELETE CASCADE;

--
-- Constraints for table `order`
--
ALTER TABLE `order`
  ADD CONSTRAINT `FK_ORDER_CUSTOMER` FOREIGN KEY (`CUSTOMER_ID`) REFERENCES `customer` (`CUSTOMER_ID`),
  ADD CONSTRAINT `order_ibfk_1` FOREIGN KEY (`CUSTOMER_ID`) REFERENCES `customer` (`CUSTOMER_ID`),
  ADD CONSTRAINT `order_ibfk_2` FOREIGN KEY (`CART_ID`) REFERENCES `cart` (`CART_ID`) ON DELETE SET NULL;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`ORDER_ID`) REFERENCES `order` (`ORDER_ID`);

--
-- Constraints for table `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `review_ibfk_1` FOREIGN KEY (`CUSTOMER_ID`) REFERENCES `customer` (`CUSTOMER_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_ibfk_2` FOREIGN KEY (`ITEM_ID`) REFERENCES `item` (`ITEM_ID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
