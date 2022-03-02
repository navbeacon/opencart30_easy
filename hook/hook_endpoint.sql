-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Vært: mysql14.gigahost.dk
-- Genereringstid: 13. 10 2020 kl. 06:15:45
-- Serverversion: 5.7.28
-- PHP-version: 5.6.27-0+deb8u1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `your_database_name`
--

-- --------------------------------------------------------

--
-- Struktur-dump for tabellen `tbl_events`
--

CREATE TABLE `tbl_events` (
  `fldID` int(255) NOT NULL,
  `fldEvent` varchar(50) DEFAULT NULL,
  `fldDescription` text,
  `fldSort` int(3) DEFAULT NULL,
  `fldStatus` int(1) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Data dump for tabellen `tbl_events`
--

INSERT INTO `tbl_events` (`fldID`, `fldEvent`, `fldDescription`, `fldSort`, `fldStatus`) VALUES
(1, 'payment.created', 'When a payment is created.', 1, 1),
(2, 'payment.reservation.created', 'When a customer successfully has reserved.', 4, 0),
(3, 'payment.reservation.created.v2', 'When a customer successfully has reserved.', 3, 0),
(4, 'payment.checkout.completed', 'When Checkout is completed.', 2, 0),
(5, 'payment.charge.created', 'When a payment has been charged. Partially or fully.', 5, 0),
(6, 'payment.charge.created.v2', 'When a payment has been charged. Partially or fully.', 6, 0),
(7, 'payment.charge.failed', 'When a charge has failed.', 7, 0),
(8, 'payment.refund.initiated', 'When a refund is initiated.', 8, 0),
(9, 'payment.refund.initiated.v2', 'When a refund is initiated.', 9, 0),
(10, 'payment.refund.failed', 'When a refund has not gone through.', 10, 0),
(11, 'payment.refund.completed', 'When a refund has successfully been completed.', 11, 0),
(12, 'payment.cancel.created', 'When a reservation has been canceled.', 12, 0),
(13, 'payment.cancel.failed', 'When a cancellation did not go through.', 13, 0);

-- --------------------------------------------------------

--
-- Struktur-dump for tabellen `tbl_webhooks`
--

CREATE TABLE `tbl_webhooks` (
  `fldID` int(255) NOT NULL,
  `fldMID` int(10) DEFAULT NULL,
  `fldPID` varchar(50) DEFAULT NULL,
  `fldDate` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Struktur-dump for tabellen `tbl_webhook_events`
--

CREATE TABLE `tbl_webhook_events` (
  `fldID` int(255) NOT NULL,
  `fldHookID` int(255) NOT NULL,
  `fldEID` varchar(50) DEFAULT NULL,
  `fldEvent` varchar(100) DEFAULT NULL,
  `fldData` text,
  `fldSort` int(4) NOT NULL DEFAULT '0',
  `fldStamp` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Begrænsninger for dumpede tabeller
--

--
-- Indeks for tabel `tbl_events`
--
ALTER TABLE `tbl_events`
  ADD PRIMARY KEY (`fldID`);

--
-- Indeks for tabel `tbl_webhooks`
--
ALTER TABLE `tbl_webhooks`
  ADD PRIMARY KEY (`fldID`);

--
-- Indeks for tabel `tbl_webhook_events`
--
ALTER TABLE `tbl_webhook_events`
  ADD PRIMARY KEY (`fldID`);

--
-- Brug ikke AUTO_INCREMENT for slettede tabeller
--

--
-- Tilføj AUTO_INCREMENT i tabel `tbl_events`
--
ALTER TABLE `tbl_events`
  MODIFY `fldID` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Tilføj AUTO_INCREMENT i tabel `tbl_webhooks`
--
ALTER TABLE `tbl_webhooks`
  MODIFY `fldID` int(255) NOT NULL AUTO_INCREMENT;

--
-- Tilføj AUTO_INCREMENT i tabel `tbl_webhook_events`
--
ALTER TABLE `tbl_webhook_events`
  MODIFY `fldID` int(255) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
