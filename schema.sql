-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Creato il: Mag 28, 2026 alle 11:25
-- Versione del server: 10.11.16-MariaDB-cll-lve-log
-- Versione PHP: 8.4.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `itpbrgro_wp761`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `classifica`
--

CREATE TABLE `classifica` (
  `id` int(11) NOT NULL,
  `torneo_id` int(11) NOT NULL,
  `squadra_id` int(11) NOT NULL,
  `partite_giocate` int(11) NOT NULL DEFAULT 0,
  `vittorie` int(11) NOT NULL DEFAULT 0,
  `pareggi` int(11) NOT NULL DEFAULT 0,
  `sconfitte` int(11) NOT NULL DEFAULT 0,
  `punti_fatti` int(11) NOT NULL DEFAULT 0,
  `punti_subiti` int(11) NOT NULL DEFAULT 0,
  `punti` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `giocatore_squadra`
--

CREATE TABLE `giocatore_squadra` (
  `id` int(11) NOT NULL,
  `squadra_id` int(11) NOT NULL,
  `utente_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `giocatore_squadra`
--

INSERT INTO `giocatore_squadra` (`id`, `squadra_id`, `utente_id`) VALUES
(60, 19, 22),
(61, 20, 25),
(62, 21, 26),
(63, 22, 24),
(87, 48, 36),
(88, 49, 22),
(89, 50, 38),
(91, 52, 24),
(93, 54, 25),
(94, 55, 26),
(96, 57, 27),
(97, 58, 28),
(98, 59, 29),
(99, 60, 30),
(101, 62, 31),
(102, 63, 32),
(103, 64, 33),
(104, 65, 34),
(106, 67, 26),
(134, 87, 29),
(135, 88, 22),
(136, 89, 24),
(137, 90, 25),
(138, 91, 26),
(139, 92, 27),
(140, 93, 28),
(141, 94, 30),
(142, 95, 43),
(143, 96, 31),
(144, 97, 41),
(145, 98, 32),
(146, 99, 38),
(147, 100, 33),
(148, 101, 34),
(149, 102, 36),
(160, 108, 25),
(161, 109, 38),
(162, 110, 26),
(163, 111, 24),
(164, 112, 25),
(165, 113, 38),
(166, 114, 24),
(167, 115, 41),
(168, 116, 27),
(169, 117, 29),
(170, 118, 22),
(171, 119, 26),
(204, 141, 25),
(205, 142, 24),
(206, 143, 22),
(207, 144, 41),
(208, 145, 26),
(209, 146, 27),
(210, 147, 29),
(211, 148, 28),
(212, 149, 33),
(213, 150, 30),
(214, 151, 32),
(215, 152, 31),
(216, 153, 34),
(217, 154, 25),
(218, 155, 38),
(219, 156, 26),
(220, 157, 24),
(221, 158, 22),
(222, 159, 25),
(223, 160, 24),
(224, 161, 28),
(225, 162, 30),
(228, 165, 57),
(229, 166, 44),
(230, 167, 32),
(231, 168, 34),
(232, 169, 27),
(233, 170, 51),
(234, 171, 26),
(235, 172, 38),
(236, 173, 29),
(237, 174, 31),
(238, 175, 43);

-- --------------------------------------------------------

--
-- Struttura della tabella `partita`
--

CREATE TABLE `partita` (
  `id` int(11) NOT NULL,
  `torneo_id` int(11) NOT NULL,
  `squadra_casa_id` int(11) NOT NULL,
  `squadra_ospite_id` int(11) NOT NULL,
  `punti_casa` int(11) DEFAULT NULL,
  `punti_ospite` int(11) DEFAULT NULL,
  `girone` int(11) DEFAULT NULL,
  `turno` enum('ottavi','quarti','semifinale','finale') DEFAULT NULL,
  `orario` timestamp NULL DEFAULT NULL,
  `stato` enum('programmata','in_corso','terminata') NOT NULL DEFAULT 'programmata',
  `tipo` enum('andata','ritorno') DEFAULT 'andata'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `partita`
--

INSERT INTO `partita` (`id`, `torneo_id`, `squadra_casa_id`, `squadra_ospite_id`, `punti_casa`, `punti_ospite`, `girone`, `turno`, `orario`, `stato`, `tipo`) VALUES
(93, 26, 19, 22, 7, 7, 1, NULL, NULL, 'terminata', 'andata'),
(94, 26, 20, 21, 14, 1, 1, NULL, NULL, 'terminata', 'andata'),
(95, 26, 21, 19, 7, 1, 1, NULL, NULL, 'terminata', 'andata'),
(96, 26, 20, 22, 1, 4, 1, NULL, NULL, 'terminata', 'andata'),
(97, 26, 19, 20, 14, 1, 1, NULL, NULL, 'terminata', 'andata'),
(98, 26, 21, 22, 5, 6, 1, NULL, NULL, 'terminata', 'andata'),
(158, 33, 52, 58, 1, 2, 1, NULL, NULL, 'terminata', 'andata'),
(159, 33, 58, 52, 5, 4, 1, NULL, NULL, 'terminata', 'andata'),
(160, 33, 64, 52, 7, 6, 1, NULL, NULL, 'terminata', 'andata'),
(161, 33, 57, 55, 4, 4, 1, NULL, NULL, 'terminata', 'andata'),
(162, 33, 64, 55, 7, 1, 1, NULL, NULL, 'terminata', 'andata'),
(163, 33, 58, 55, 6, 3, 1, NULL, NULL, 'terminata', 'andata'),
(164, 33, 55, 57, 1, 1, 1, NULL, NULL, 'terminata', 'andata'),
(165, 33, 57, 52, 2, 1, 1, NULL, NULL, 'terminata', 'andata'),
(166, 33, 55, 64, 1, 2, 1, NULL, NULL, 'terminata', 'andata'),
(167, 33, 58, 57, 1, 2, 1, NULL, NULL, 'terminata', 'andata'),
(168, 33, 64, 57, 1, 2, 1, NULL, NULL, 'terminata', 'andata'),
(169, 33, 52, 55, 1, 2, 1, NULL, NULL, 'terminata', 'andata'),
(170, 33, 58, 64, 1, 2, 1, NULL, NULL, 'terminata', 'andata'),
(171, 33, 55, 58, 1, 1, 1, NULL, NULL, 'terminata', 'andata'),
(172, 33, 57, 64, 1, 1, 1, NULL, NULL, 'terminata', 'andata'),
(173, 33, 55, 52, 2, 2, 1, NULL, NULL, 'terminata', 'andata'),
(174, 33, 57, 58, 2, 1, 1, NULL, NULL, 'terminata', 'andata'),
(175, 33, 52, 64, 2, 1, 1, NULL, NULL, 'terminata', 'andata'),
(176, 33, 52, 57, 2, 1, 1, NULL, NULL, 'terminata', 'andata'),
(177, 33, 64, 58, 2, 1, 1, NULL, NULL, 'terminata', 'andata'),
(178, 33, 65, 63, 2, 1, 2, NULL, NULL, 'terminata', 'andata'),
(179, 33, 63, 54, 2, 1, 2, NULL, NULL, 'terminata', 'andata'),
(180, 33, 50, 62, 2, 1, 2, NULL, NULL, 'terminata', 'andata'),
(181, 33, 62, 54, 2, 1, 2, NULL, NULL, 'terminata', 'andata'),
(182, 33, 50, 63, 1, 2, 2, NULL, NULL, 'terminata', 'andata'),
(183, 33, 54, 65, 2, 1, 2, NULL, NULL, 'terminata', 'andata'),
(184, 33, 63, 50, 2, 1, 2, NULL, NULL, 'terminata', 'andata'),
(185, 33, 50, 54, 2, 1, 2, NULL, NULL, 'terminata', 'andata'),
(186, 33, 65, 62, 2, 1, 2, NULL, NULL, 'terminata', 'andata'),
(187, 33, 63, 65, 2, 1, 2, NULL, NULL, 'terminata', 'andata'),
(188, 33, 50, 65, 1, 1, 2, NULL, NULL, 'terminata', 'andata'),
(189, 33, 63, 62, 12, 1, 2, NULL, NULL, 'terminata', 'andata'),
(190, 33, 65, 54, 2, 1, 2, NULL, NULL, 'terminata', 'andata'),
(191, 33, 62, 50, 1, 2, 2, NULL, NULL, 'terminata', 'andata'),
(192, 33, 54, 63, 2, 2, 2, NULL, NULL, 'terminata', 'andata'),
(193, 33, 62, 63, 1, 2, 2, NULL, NULL, 'terminata', 'andata'),
(194, 33, 65, 50, 4, 2, 2, NULL, NULL, 'terminata', 'andata'),
(195, 33, 54, 50, 1, 2, 2, NULL, NULL, 'terminata', 'andata'),
(196, 33, 54, 62, 2, 1, 2, NULL, NULL, 'terminata', 'andata'),
(197, 33, 62, 65, 1, 2, 2, NULL, NULL, 'terminata', 'andata'),
(198, 33, 59, 60, 0, 2, 3, NULL, NULL, 'terminata', 'andata'),
(199, 33, 60, 59, 1, 2, 3, NULL, NULL, 'terminata', 'andata'),
(200, 33, 49, 59, 2, 1, 3, NULL, NULL, 'terminata', 'andata'),
(201, 33, 48, 60, 2, 1, 3, NULL, NULL, 'terminata', 'andata'),
(202, 33, 48, 49, 2, 1, 3, NULL, NULL, 'terminata', 'andata'),
(203, 33, 59, 48, 3, 2, 3, NULL, NULL, 'terminata', 'andata'),
(204, 33, 49, 48, 0, 1, 3, NULL, NULL, 'terminata', 'andata'),
(205, 33, 59, 49, 2, 1, 3, NULL, NULL, 'terminata', 'andata'),
(206, 33, 60, 48, 1, 2, 3, NULL, NULL, 'terminata', 'andata'),
(207, 33, 49, 60, 2, 3, 3, NULL, NULL, 'terminata', 'andata'),
(208, 33, 60, 49, 1, 2, 3, NULL, NULL, 'terminata', 'andata'),
(214, 33, 57, 59, 5, 4, NULL, 'quarti', NULL, 'terminata', 'andata'),
(215, 33, 50, 63, 1, 2, NULL, 'quarti', NULL, 'terminata', 'andata'),
(216, 33, 65, 58, 5, 4, NULL, 'quarti', NULL, 'terminata', 'andata'),
(217, 33, 64, 48, 2, 4, NULL, 'quarti', NULL, 'terminata', 'andata'),
(218, 33, 57, 48, 8, 7, NULL, 'semifinale', NULL, 'terminata', 'andata'),
(219, 33, 65, 63, 5, 4, NULL, 'semifinale', NULL, 'terminata', 'andata'),
(220, 33, 65, 57, 2, 1, NULL, 'finale', NULL, 'terminata', 'andata'),
(256, 42, 102, 87, 1, 2, 1, NULL, NULL, 'terminata', 'andata'),
(257, 42, 102, 91, 2, 11, 1, NULL, NULL, 'terminata', 'andata'),
(258, 42, 102, 89, 0, 3, 1, NULL, NULL, 'terminata', 'andata'),
(259, 42, 92, 91, 5, 6, 1, NULL, NULL, 'terminata', 'andata'),
(260, 42, 91, 87, 3, 6, 1, NULL, NULL, 'terminata', 'andata'),
(261, 42, 102, 93, 4, 8, 1, NULL, NULL, 'terminata', 'andata'),
(262, 42, 87, 89, 0, 5, 1, NULL, NULL, 'terminata', 'andata'),
(263, 42, 92, 102, 8, 4, 1, NULL, NULL, 'terminata', 'andata'),
(264, 42, 89, 87, 1, 2, 1, NULL, NULL, 'terminata', 'andata'),
(265, 42, 92, 93, 5, 7, 1, NULL, NULL, 'terminata', 'andata'),
(266, 42, 92, 89, 3, 2, 1, NULL, NULL, 'terminata', 'andata'),
(267, 42, 91, 92, 5, 7, 1, NULL, NULL, 'terminata', 'andata'),
(268, 42, 87, 92, 6, 9, 1, NULL, NULL, 'terminata', 'andata'),
(269, 42, 91, 102, 2, 1, 1, NULL, NULL, 'terminata', 'andata'),
(270, 42, 89, 102, 4, 5, 1, NULL, NULL, 'terminata', 'andata'),
(271, 42, 93, 89, 6, 7, 1, NULL, NULL, 'terminata', 'andata'),
(272, 42, 91, 89, 6, 7, 1, NULL, NULL, 'terminata', 'andata'),
(273, 42, 87, 91, 2, 1, 1, NULL, NULL, 'terminata', 'andata'),
(274, 42, 91, 93, 5, 9, 1, NULL, NULL, 'terminata', 'andata'),
(275, 42, 93, 87, 5, 4, 1, NULL, NULL, 'terminata', 'andata'),
(276, 42, 93, 102, 3, 1, 1, NULL, NULL, 'terminata', 'andata'),
(277, 42, 87, 102, 4, 5, 1, NULL, NULL, 'terminata', 'andata'),
(278, 42, 89, 93, 7, 6, 1, NULL, NULL, 'terminata', 'andata'),
(279, 42, 92, 87, 6, 7, 1, NULL, NULL, 'terminata', 'andata'),
(280, 42, 89, 91, 4, 0, 1, NULL, NULL, 'terminata', 'andata'),
(281, 42, 87, 93, 4, 2, 1, NULL, NULL, 'terminata', 'andata'),
(282, 42, 89, 92, 11, 2, 1, NULL, NULL, 'terminata', 'andata'),
(283, 42, 102, 92, 10, 4, 1, NULL, NULL, 'terminata', 'andata'),
(284, 42, 93, 92, 69, 43, 1, NULL, NULL, 'terminata', 'andata'),
(285, 42, 93, 91, 2, 3, 1, NULL, NULL, 'terminata', 'andata'),
(286, 42, 101, 100, 3, 1, 2, NULL, NULL, 'terminata', 'andata'),
(287, 42, 99, 101, 2, 3, 2, NULL, NULL, 'terminata', 'andata'),
(288, 42, 94, 101, 1, 1, 2, NULL, NULL, 'terminata', 'andata'),
(289, 42, 90, 100, 1, 1, 2, NULL, NULL, 'terminata', 'andata'),
(290, 42, 101, 99, 1, 1, 2, NULL, NULL, 'terminata', 'andata'),
(291, 42, 100, 101, 1, 1, 2, NULL, NULL, 'terminata', 'andata'),
(292, 42, 90, 99, 3, 1, 2, NULL, NULL, 'terminata', 'andata'),
(293, 42, 99, 100, 3, 1, 2, NULL, NULL, 'terminata', 'andata'),
(294, 42, 99, 94, 1, 3, 2, NULL, NULL, 'terminata', 'andata'),
(295, 42, 94, 90, 1, 3, 2, NULL, NULL, 'terminata', 'andata'),
(296, 42, 101, 94, 1, 3, 2, NULL, NULL, 'terminata', 'andata'),
(297, 42, 99, 90, 1, 3, 2, NULL, NULL, 'terminata', 'andata'),
(298, 42, 94, 100, 1, 3, 2, NULL, NULL, 'terminata', 'andata'),
(299, 42, 90, 101, 1, 3, 2, NULL, NULL, 'terminata', 'andata'),
(300, 42, 101, 90, 11, 3, 2, NULL, NULL, 'terminata', 'andata'),
(301, 42, 90, 94, 1, 3, 2, NULL, NULL, 'terminata', 'andata'),
(302, 42, 100, 94, 0, 3, 2, NULL, NULL, 'terminata', 'andata'),
(303, 42, 100, 99, 2, 5, 2, NULL, NULL, 'terminata', 'andata'),
(304, 42, 100, 90, 0, 1, 2, NULL, NULL, 'terminata', 'andata'),
(305, 42, 94, 99, 1, 2, 2, NULL, NULL, 'terminata', 'andata'),
(306, 42, 95, 97, 7, 8, 3, NULL, NULL, 'terminata', 'andata'),
(307, 42, 88, 97, 7, 8, 3, NULL, NULL, 'terminata', 'andata'),
(308, 42, 88, 95, 4, 5, 3, NULL, NULL, 'terminata', 'andata'),
(309, 42, 97, 88, 1, 2, 3, NULL, NULL, 'terminata', 'andata'),
(310, 42, 96, 97, 0, 1, 3, NULL, NULL, 'terminata', 'andata'),
(311, 42, 95, 96, 1, 2, 3, NULL, NULL, 'terminata', 'andata'),
(312, 42, 96, 95, 1, 2, 3, NULL, NULL, 'terminata', 'andata'),
(313, 42, 97, 98, 2, 1, 3, NULL, NULL, 'terminata', 'andata'),
(314, 42, 95, 98, 2, 1, 3, NULL, NULL, 'terminata', 'andata'),
(315, 42, 98, 97, 2, 1, 3, NULL, NULL, 'terminata', 'andata'),
(316, 42, 88, 96, 2, 0, 3, NULL, NULL, 'terminata', 'andata'),
(317, 42, 96, 88, 2, 1, 3, NULL, NULL, 'terminata', 'andata'),
(318, 42, 88, 98, 2, 1, 3, NULL, NULL, 'terminata', 'andata'),
(319, 42, 98, 95, 1, 2, 3, NULL, NULL, 'terminata', 'andata'),
(320, 42, 95, 88, 1, 2, 3, NULL, NULL, 'terminata', 'andata'),
(321, 42, 97, 95, 1, 2, 3, NULL, NULL, 'terminata', 'andata'),
(322, 42, 96, 98, 12, 2, 3, NULL, NULL, 'terminata', 'andata'),
(323, 42, 98, 96, 11, 2, 3, NULL, NULL, 'terminata', 'andata'),
(324, 42, 97, 96, 1, 2, 3, NULL, NULL, 'terminata', 'andata'),
(325, 42, 98, 88, 2, 4, 3, NULL, NULL, 'terminata', 'andata'),
(326, 42, 95, 89, 1, 2, NULL, 'quarti', NULL, 'terminata', 'andata'),
(327, 42, 90, 88, 1, 100, NULL, 'quarti', NULL, 'terminata', 'andata'),
(328, 42, 94, 87, 4, 5, NULL, 'quarti', NULL, 'terminata', 'andata'),
(329, 42, 101, 93, 4, 1, NULL, 'quarti', NULL, 'terminata', 'andata'),
(330, 42, 87, 101, 18, 2331, NULL, 'semifinale', NULL, 'terminata', 'andata'),
(331, 42, 88, 89, 1, 0, NULL, 'semifinale', NULL, 'terminata', 'andata'),
(332, 42, 101, 88, 3, 12345, NULL, 'finale', NULL, 'terminata', 'andata'),
(346, 48, 111, 109, 2, 1, NULL, 'semifinale', NULL, 'terminata', 'andata'),
(347, 48, 108, 110, 5, 4, NULL, 'semifinale', NULL, 'terminata', 'andata'),
(348, 48, 108, 111, 6, 7, NULL, 'finale', NULL, 'terminata', 'andata'),
(349, 49, 117, 113, 3, 2, 1, NULL, NULL, 'terminata', 'andata'),
(350, 49, 115, 112, 4, 5, 1, NULL, NULL, 'terminata', 'andata'),
(351, 49, 112, 113, 2, 2, 1, NULL, NULL, 'terminata', 'andata'),
(352, 49, 115, 117, 5, 1, 1, NULL, NULL, 'terminata', 'andata'),
(353, 49, 115, 113, 5, 5, 1, NULL, NULL, 'terminata', 'andata'),
(354, 49, 112, 117, 2, 2, 1, NULL, NULL, 'terminata', 'andata'),
(355, 49, 116, 114, 2, 2, 2, NULL, NULL, 'terminata', 'andata'),
(356, 49, 114, 118, 0, 0, 2, NULL, NULL, 'terminata', 'andata'),
(357, 49, 116, 118, 3, 2, 2, NULL, NULL, 'terminata', 'andata'),
(358, 49, 116, 115, 2, 0, NULL, 'semifinale', NULL, 'terminata', 'andata'),
(359, 49, 112, 114, 2, 0, NULL, 'semifinale', NULL, 'terminata', 'andata'),
(360, 49, 116, 112, 2, 6, NULL, 'finale', NULL, 'terminata', 'andata'),
(427, 63, 144, 150, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(428, 63, 143, 144, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(429, 63, 143, 147, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(430, 63, 143, 145, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(431, 63, 149, 143, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(432, 63, 144, 143, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(433, 63, 150, 149, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(434, 63, 145, 144, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(435, 63, 144, 145, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(436, 63, 145, 150, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(437, 63, 149, 147, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(438, 63, 144, 147, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(439, 63, 147, 149, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(440, 63, 143, 150, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(441, 63, 147, 150, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(442, 63, 147, 144, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(443, 63, 145, 143, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(444, 63, 149, 150, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(445, 63, 147, 145, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(446, 63, 149, 145, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(447, 63, 150, 144, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(448, 63, 149, 144, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(449, 63, 145, 149, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(450, 63, 144, 149, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(451, 63, 150, 147, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(452, 63, 150, 145, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(453, 63, 147, 143, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(454, 63, 150, 143, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(455, 63, 145, 147, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(456, 63, 143, 149, NULL, NULL, 1, NULL, NULL, 'programmata', 'andata'),
(457, 63, 142, 146, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(458, 63, 151, 146, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(459, 63, 148, 141, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(460, 63, 141, 151, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(461, 63, 142, 148, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(462, 63, 141, 148, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(463, 63, 152, 148, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(464, 63, 148, 142, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(465, 63, 146, 151, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(466, 63, 146, 148, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(467, 63, 146, 152, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(468, 63, 148, 151, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(469, 63, 148, 146, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(470, 63, 141, 146, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(471, 63, 148, 152, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(472, 63, 142, 141, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(473, 63, 142, 152, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(474, 63, 151, 142, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(475, 63, 146, 142, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(476, 63, 152, 146, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(477, 63, 152, 141, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(478, 63, 152, 142, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(479, 63, 141, 142, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(480, 63, 151, 152, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(481, 63, 142, 151, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(482, 63, 146, 141, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(483, 63, 151, 141, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(484, 63, 151, 148, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(485, 63, 152, 151, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(486, 63, 141, 152, NULL, NULL, 2, NULL, NULL, 'programmata', 'andata'),
(487, 65, 154, 155, 5, 1, NULL, 'semifinale', NULL, 'terminata', 'andata'),
(488, 65, 155, 154, 6, 2, NULL, 'semifinale', NULL, 'terminata', 'ritorno'),
(489, 65, 157, 156, 8, 8, NULL, 'semifinale', NULL, 'terminata', 'andata'),
(490, 65, 156, 157, 1, 2, NULL, 'semifinale', NULL, 'terminata', 'ritorno'),
(491, 65, 157, 154, 2, 1, NULL, 'finale', NULL, 'terminata', 'andata'),
(492, 65, 154, 157, 3, 5, NULL, 'finale', NULL, 'terminata', 'ritorno'),
(493, 66, 161, 174, NULL, NULL, NULL, 'ottavi', NULL, 'programmata', 'andata'),
(494, 66, 159, 158, NULL, NULL, NULL, 'ottavi', NULL, 'programmata', 'andata'),
(495, 66, 171, 168, NULL, NULL, NULL, 'ottavi', NULL, 'programmata', 'andata'),
(496, 66, 173, 170, NULL, NULL, NULL, 'ottavi', NULL, 'programmata', 'andata'),
(497, 66, 169, 167, NULL, NULL, NULL, 'ottavi', NULL, 'programmata', 'andata'),
(498, 66, 162, 175, NULL, NULL, NULL, 'ottavi', NULL, 'programmata', 'andata'),
(499, 66, 166, 160, NULL, NULL, NULL, 'ottavi', NULL, 'programmata', 'andata'),
(500, 66, 165, 172, NULL, NULL, NULL, 'ottavi', NULL, 'programmata', 'andata');

-- --------------------------------------------------------

--
-- Struttura della tabella `pranzi`
--

CREATE TABLE `pranzi` (
  `id` int(11) NOT NULL,
  `torneo_id` int(11) NOT NULL,
  `squadra_id` int(11) NOT NULL,
  `orario` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dump dei dati per la tabella `pranzi`
--

INSERT INTO `pranzi` (`id`, `torneo_id`, `squadra_id`, `orario`) VALUES
(2, 26, 19, '2026-05-15 11:33:00');

-- --------------------------------------------------------

--
-- Struttura della tabella `squadra`
--

CREATE TABLE `squadra` (
  `id` int(11) NOT NULL,
  `torneo_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `capitano_id` int(11) NOT NULL,
  `stato` enum('in_attesa','approvata','rifiutata') NOT NULL DEFAULT 'in_attesa',
  `token_approva` varchar(64) DEFAULT NULL,
  `token_rifiuta` varchar(64) DEFAULT NULL,
  `persone_pranzo` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `squadra`
--

INSERT INTO `squadra` (`id`, `torneo_id`, `nome`, `capitano_id`, `stato`, `token_approva`, `token_rifiuta`, `persone_pranzo`) VALUES
(19, 26, 'squadra1', 22, 'approvata', NULL, NULL, 0),
(20, 26, 'squadra2', 25, 'approvata', NULL, NULL, 20),
(21, 26, 'squadra3', 26, 'approvata', NULL, NULL, 0),
(22, 26, 'squadra4', 24, 'approvata', NULL, NULL, 0),
(36, 28, 's1', 25, 'approvata', NULL, NULL, 20),
(39, 28, 's2', 26, 'approvata', NULL, NULL, 0),
(48, 33, 'squadra1', 36, 'approvata', NULL, NULL, 0),
(49, 33, 'squadra2', 22, 'approvata', NULL, NULL, 0),
(50, 33, 'squadra3', 38, 'approvata', NULL, NULL, 0),
(52, 33, 'squadra4', 24, 'approvata', NULL, NULL, 0),
(54, 33, 'squadra5', 25, 'approvata', NULL, NULL, 0),
(55, 33, 'squadra6', 26, 'approvata', NULL, NULL, 0),
(57, 33, 'squadra7', 27, 'approvata', NULL, NULL, 0),
(58, 33, 'squadra8', 28, 'approvata', NULL, NULL, 0),
(59, 33, 'squadra9', 29, 'approvata', NULL, NULL, 0),
(60, 33, 'squadra10', 30, 'approvata', NULL, NULL, 0),
(62, 33, 'squadra11', 31, 'approvata', NULL, NULL, 0),
(63, 33, 'squadra12', 32, 'approvata', NULL, NULL, 0),
(64, 33, 'squadra13', 33, 'approvata', NULL, NULL, 0),
(65, 33, 'squadra14', 34, 'approvata', NULL, NULL, 0),
(67, 28, 's3', 26, 'approvata', NULL, NULL, 0),
(87, 42, 'Pikachu', 29, 'approvata', NULL, NULL, 0),
(88, 42, 'Charizard', 22, 'approvata', NULL, NULL, 0),
(89, 42, 'Squirtle', 24, 'approvata', NULL, NULL, 0),
(90, 42, 'Bulbasaur', 25, 'approvata', NULL, NULL, 0),
(91, 42, 'Muk', 26, 'approvata', NULL, NULL, 20),
(92, 42, 'Onix', 27, 'approvata', NULL, NULL, 0),
(93, 42, 'Finx', 28, 'approvata', NULL, NULL, 0),
(94, 42, 'Pichu', 30, 'approvata', NULL, NULL, 0),
(95, 42, 'snorlax', 43, 'approvata', NULL, NULL, 0),
(96, 42, 'Mimikyu', 31, 'approvata', NULL, NULL, 0),
(97, 42, 'mew', 41, 'approvata', NULL, NULL, 0),
(98, 42, 'Mewtwo', 32, 'approvata', NULL, NULL, 0),
(99, 42, 'blastois', 38, 'approvata', NULL, NULL, 0),
(100, 42, 'Honu-honu', 33, 'approvata', NULL, NULL, 0),
(101, 42, 'Zyagra', 34, 'approvata', NULL, NULL, 0),
(102, 42, 'charmilion', 36, 'approvata', NULL, NULL, 20),
(108, 48, 'matteo', 25, 'approvata', NULL, NULL, 0),
(109, 48, 'Luca', 38, 'approvata', NULL, NULL, 0),
(110, 48, 'Gari', 26, 'approvata', NULL, NULL, 0),
(111, 48, 'Liam', 24, 'approvata', NULL, NULL, 0),
(112, 49, 'matteo', 25, 'approvata', NULL, NULL, 0),
(113, 49, 'luca', 38, 'approvata', NULL, NULL, 0),
(114, 49, 'liam', 24, 'approvata', NULL, NULL, 0),
(115, 49, 'giacomo', 41, 'approvata', NULL, NULL, 0),
(116, 49, 'Marty', 27, 'approvata', NULL, NULL, 0),
(117, 49, 'liam_itis', 29, 'approvata', NULL, NULL, 0),
(118, 49, 'cluchy', 22, 'approvata', NULL, NULL, 0),
(119, 51, 'prova_s1', 26, 'in_attesa', '05544d510b0a8ef52c8dfa5c77f05163a3b5f39b1963a23cc17fe5e888012412', '83c9429bb815f643e6dd4a1f31949341854859c4d3bd098c69e8c50788ed8ff2', 0),
(141, 63, 'matteo', 25, 'approvata', NULL, NULL, 0),
(142, 63, 'liam', 24, 'approvata', NULL, NULL, 0),
(143, 63, 'CluchyAI', 22, 'approvata', NULL, NULL, 0),
(144, 63, 'giacomo1', 41, 'approvata', NULL, NULL, 0),
(145, 63, 'GariSchoolGpt', 26, 'approvata', NULL, NULL, 0),
(146, 63, 'MartiSeek', 27, 'approvata', NULL, NULL, 0),
(147, 63, 'Besiktas', 29, 'approvata', NULL, NULL, 0),
(148, 63, 'MoruGpt', 28, 'approvata', NULL, NULL, 0),
(149, 63, 'Fortnite', 33, 'approvata', NULL, NULL, 0),
(150, 63, 'GondyClean', 30, 'approvata', NULL, NULL, 0),
(151, 63, 'Inazuma', 32, 'approvata', NULL, NULL, 0),
(152, 63, 'TortaCheck', 31, 'approvata', NULL, NULL, 0),
(153, 63, 'Raimond', 34, 'in_attesa', '1228a594023d0dfbb4ffbe099c5e2c54f8cb02023651dd62dacf6f17bcad4b13', '12fac1b0ab63e0d3c52a12f43850b5c2022c396d43a9ac3d06f998e1f941fb4f', 0),
(154, 65, 'matteo', 25, 'approvata', NULL, NULL, 0),
(155, 65, 'cluchy', 38, 'approvata', NULL, NULL, 0),
(156, 65, 'giacomo_itis', 26, 'approvata', NULL, NULL, 0),
(157, 65, 'liam', 24, 'approvata', NULL, NULL, 0),
(158, 66, 'Milan', 22, 'approvata', NULL, NULL, 0),
(159, 66, 'Feynord', 25, 'approvata', NULL, NULL, 0),
(160, 66, 'Rennes', 24, 'approvata', NULL, NULL, 0),
(161, 66, 'Girona', 28, 'approvata', NULL, NULL, 0),
(162, 66, 'Lipsia', 30, 'approvata', NULL, NULL, 0),
(165, 66, 'Porto', 57, 'approvata', NULL, NULL, 0),
(166, 66, 'Ajax', 44, 'approvata', NULL, NULL, 0),
(167, 66, 'Napoli', 32, 'approvata', NULL, NULL, 0),
(168, 66, 'Monaco', 34, 'approvata', NULL, NULL, 0),
(169, 66, 'Wolfsburg', 27, 'approvata', NULL, NULL, 0),
(170, 66, 'Chelsea', 51, 'approvata', NULL, NULL, 0),
(171, 66, 'Malmo', 26, 'approvata', NULL, NULL, 15),
(172, 66, 'Udinese', 38, 'approvata', NULL, NULL, 0),
(173, 66, 'Liverpool', 29, 'approvata', NULL, NULL, 0),
(174, 66, 'Nizza', 31, 'approvata', NULL, NULL, 0),
(175, 66, 'Barcellona', 43, 'approvata', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Struttura della tabella `torneo`
--

CREATE TABLE `torneo` (
  `id` int(11) NOT NULL,
  `sport` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(150) NOT NULL,
  `descrizione` varchar(255) DEFAULT NULL,
  `luogo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `formato` enum('girone_unico','eliminazione_diretta','gironi_playoff') NOT NULL,
  `gironi_mode` enum('auto','manuale') NOT NULL DEFAULT 'auto',
  `tipo_partita` enum('andata','andata_ritorno') NOT NULL,
  `visibilita` enum('pubblico','privato') NOT NULL DEFAULT 'pubblico',
  `numero_squadre` int(11) NOT NULL,
  `creato_da` int(11) NOT NULL,
  `stato` enum('aperto','in_corso','completato') NOT NULL DEFAULT 'aperto',
  `min_giocatori_per_squadra` int(11) NOT NULL,
  `max_giocatori_per_squadra` int(11) NOT NULL,
  `min_squadre` int(11) NOT NULL DEFAULT 2,
  `data_chiusura_iscrizioni` datetime NOT NULL,
  `codice_privato` varchar(8) DEFAULT NULL,
  `nome_file` varchar(255) DEFAULT NULL,
  `percorso` varchar(255) DEFAULT NULL,
  `pranzo` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dump dei dati per la tabella `torneo`
--

INSERT INTO `torneo` (`id`, `sport`, `nome`, `descrizione`, `luogo`, `formato`, `gironi_mode`, `tipo_partita`, `visibilita`, `numero_squadre`, `creato_da`, `stato`, `min_giocatori_per_squadra`, `max_giocatori_per_squadra`, `min_squadre`, `data_chiusura_iscrizioni`, `codice_privato`, `nome_file`, `percorso`, `pranzo`) VALUES
(26, 'calcio', 'Youtuber league', 'Copia di PirlasV', 'Milano', 'girone_unico', 'auto', 'andata', 'pubblico', 8, 25, 'completato', 1, 5, 4, '2026-05-12 14:55:00', NULL, NULL, NULL, 1),
(28, 'calcio', 'Torneo della rocha', 'torneo che si terra\' al parchetto della rocha', 'Roccabruna', 'eliminazione_diretta', 'auto', 'andata_ritorno', 'privato', 8, 25, 'in_corso', 1, 5, 4, '2026-05-19 15:00:00', '28C5209C', NULL, NULL, 0),
(33, 'beachvolley', 'Torneo di Boves', NULL, 'Boves', 'gironi_playoff', 'auto', 'andata_ritorno', 'pubblico', 14, 36, 'completato', 1, 10, 4, '2026-05-15 13:15:00', NULL, NULL, NULL, 0),
(42, 'calcio', 'World Cup', 'coppa del mondo', 'Valdieri', 'gironi_playoff', 'auto', 'andata_ritorno', 'pubblico', 16, 29, 'completato', 1, 10, 16, '2026-05-19 15:10:00', NULL, NULL, NULL, 0),
(47, 'beachvolley', 'prova codice privato', 'prova', 'Berne city', 'eliminazione_diretta', 'auto', 'andata', 'privato', 8, 26, 'in_corso', 5, 10, 4, '2026-05-21 10:32:00', 'EF7C94C2', 'locandina_6a0ec3261a263.jpg', 'uploads/locandine/locandina_6a0ec3261a263.jpg', 0),
(48, 'beachvolley', 'Cuneo Tennis Club Padel Under 18', 'si terrà  a Cuneo', 'Cuneo', 'eliminazione_diretta', 'auto', 'andata', 'pubblico', 8, 25, 'completato', 1, 2, 4, '2026-05-21 09:46:06', NULL, NULL, NULL, 0),
(49, 'calcio', 'Calcio playoff', 'weshh', 'Roccabruna', 'gironi_playoff', 'auto', 'andata', 'pubblico', 8, 25, 'completato', 1, 10, 4, '2026-05-22 10:19:46', NULL, NULL, NULL, 0),
(50, 'padel', 'PADEL Caraglio', 'PADEL a Caraglio', 'Caraglio', 'eliminazione_diretta', 'auto', 'andata', 'privato', 8, 25, 'in_corso', 1, 1, 4, '2026-05-23 10:00:00', '3A455FDD', NULL, NULL, 0),
(51, 'padel', 'PADEL Caraglio2', 'PADEL a Caraglio2', 'Caraglio', 'eliminazione_diretta', 'auto', 'andata', 'pubblico', 8, 25, 'in_corso', 1, 1, 4, '2026-05-23 10:00:00', NULL, NULL, NULL, 0),
(62, 'calcio', 'Luca', NULL, 'Boves', 'gironi_playoff', 'auto', 'andata', 'privato', 8, 22, 'in_corso', 5, 10, 4, '2026-05-27 06:59:16', '52E4E291', NULL, NULL, 0),
(63, 'calcio', 'Champions League', 'wesh', 'Valdieri', 'gironi_playoff', 'manuale', 'andata_ritorno', 'pubblico', 16, 25, 'in_corso', 1, 10, 4, '2026-05-27 07:24:56', NULL, NULL, NULL, 0),
(65, 'calcio', 'Coppia di Cuneo', 'cops', 'Cuneo', 'eliminazione_diretta', 'auto', 'andata_ritorno', 'pubblico', 8, 25, 'completato', 1, 10, 4, '2026-05-27 07:37:06', NULL, NULL, NULL, 0),
(66, 'calcio', 'Europa League', 'torneo in Europa', 'Lisbona', 'eliminazione_diretta', 'auto', 'andata', 'privato', 16, 26, 'in_corso', 1, 10, 4, '2026-05-27 18:30:00', '7A4FA068', 'locandina_6a17197ab36a0.jpeg', 'uploads/locandine/locandina_6a17197ab36a0.jpeg', 1);

-- --------------------------------------------------------

--
-- Struttura della tabella `torneo_seguito`
--

CREATE TABLE `torneo_seguito` (
  `id` int(11) NOT NULL,
  `torneo_id` int(11) NOT NULL,
  `utente_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `torneo_seguito`
--

INSERT INTO `torneo_seguito` (`id`, `torneo_id`, `utente_id`) VALUES
(22, 28, 22),
(24, 28, 25),
(80, 51, 26),
(75, 63, 25),
(82, 63, 26),
(77, 65, 25);

-- --------------------------------------------------------

--
-- Struttura della tabella `utente`
--

CREATE TABLE `utente` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cognome` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `token` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `token_expiry` datetime DEFAULT NULL,
  `google_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `utente`
--

INSERT INTO `utente` (`id`, `nome`, `cognome`, `email`, `password`, `verified`, `token`, `created_at`, `token_expiry`, `google_id`) VALUES
(22, 'Luca', 'Bertolotti', 'cluchybertolotti@gmail.com', 'chvQ7Ow41U4RU', 1, NULL, '2026-04-25 10:49:26', NULL, '110887759088779038608'),
(24, 'Liam', 'Tu', 'tusailiam@gmail.com', 'chvQ7Ow41U4RU', 1, NULL, '2026-04-27 10:00:43', NULL, NULL),
(25, 'Matteo', 'Luciano', 'matteo.luciano07@gmail.com', 'chvQ7Ow41U4RU', 1, NULL, '2026-04-27 10:03:36', NULL, '105853773301771902786'),
(26, 'Giacomo', 'Garino', 'giacomo.garino@itiscuneo.edu.it', 'chvQ7Ow41U4RU', 1, NULL, '2026-04-28 12:24:34', NULL, '104343856757442640898'),
(27, 'Martina', 'Luciano', 'martina.luciano2009@gmail.com', 'chvQ7Ow41U4RU', 1, NULL, '2026-04-30 05:13:04', NULL, NULL),
(28, 'Matteo', 'Olivero', 'olivero.matteo007@gmail.com', 'chvQ7Ow41U4RU', 1, NULL, '2026-04-30 11:55:50', NULL, NULL),
(29, 'Sai', 'You', 'sailiam.tu@itiscuneo.edu.it', 'chvQ7Ow41U4RU', 1, NULL, '2026-05-05 12:12:30', NULL, NULL),
(30, 'Filippo', 'Gondolo', 'filippo.gondolo@gmail.com', 'chvQ7Ow41U4RU', 1, NULL, '2026-05-05 12:15:50', NULL, NULL),
(31, 'Francesco', 'Torterolo', 'torterolofrancesco@gmail.com', 'chvQ7Ow41U4RU', 1, NULL, '2026-05-05 12:18:57', NULL, NULL),
(32, 'Torta', 'France', 'francesco.torterolo@itiscuneo.edu.it', 'chvQ7Ow41U4RU', 1, NULL, '2026-05-05 12:21:59', NULL, NULL),
(33, 'Gondy', 'Pippo', 'filippo.gondolo@itiscuneo.edu.it', 'chvQ7Ow41U4RU', 1, NULL, '2026-05-05 12:23:24', NULL, NULL),
(34, 'Micol', 'Stanisci', 'micolstanisci@gmail.com', 'chvQ7Ow41U4RU', 1, NULL, '2026-05-06 05:47:30', NULL, '111201724558554007480'),
(36, 'Luca', 'Bertolotti', 'matchora.torneo@gmail.com', 'chvQ7Ow41U4RU', 1, NULL, '2026-05-15 10:09:15', NULL, '113207065162389185638'),
(38, 'Luca', 'Bertolotti', 'luca.bertolotti@itiscuneo.edu.it', 'chvQ7Ow41U4RU', 1, NULL, '2026-05-15 10:40:05', NULL, '100604646298391934409'),
(41, 'Giacomo', 'Garino', 'giacomo.garino1@gmail.com', 'chvQ7Ow41U4RU', 1, NULL, '2026-05-19 13:10:07', NULL, '107355331061947618827'),
(43, 'Matteo', 'Luciano', 'matteo.luciano@itiscuneo.edu.it', 'chvQ7Ow41U4RU', 1, NULL, '2026-05-19 13:10:26', NULL, NULL),
(44, 'gabri', 'borello', 'gabriele.borello@itiscuneo.edu.it', 'chDLBtW8JG.aY', 1, NULL, '2026-05-20 15:06:06', NULL, NULL),
(46, 'Luca', 'Bertolotti', 'luca.bertolotti@itiscuneo.eu', '$2y$12$B3IeM0Od0Dg47dffUtJEMO2ZpRcTzL2z0rlj5t9LzUN398Qwt2XLG', 1, NULL, '2026-05-21 09:24:06', NULL, NULL),
(49, 'micol', 'stanisci', 'micol.stanisci@bianchivirginio.it', '', 1, '', '2026-05-22 13:50:58', NULL, '117311837079654230771'),
(50, 'Michele', 'Severino', 'micheleseverino07@gmail.com', '', 1, '', '2026-05-22 16:33:43', NULL, '117998689113674193975'),
(51, 'Michele', 'Bertolotti', 'mitchbertolotti@gmail.com', '', 1, '', '2026-05-23 10:15:11', NULL, '101156005324611812505'),
(53, 'romina', 'cucchietti', 'rominacucchietti@gmail.com', '', 1, '', '2026-05-25 15:47:49', NULL, '114848088881666210309'),
(56, 'Luca', 'Bertolotti', 'cluchyspotify@gmail.com', '', 1, '', '2026-05-25 16:20:02', NULL, '117653811719969602767'),
(57, 'Michela', 'Costa', 'mikicosta00@gmail.com', '$2y$12$Rbx0fPkBfbGKcuSD9xMzbe1Eop/qJy5.pV0LWI8MKFgVVB4oPXg7y', 1, NULL, '2026-05-26 20:42:59', NULL, NULL),
(58, 'Giacomo', 'Garino', 'giacomogarino34@gmail.com', '', 1, '', '2026-05-27 06:07:12', NULL, '112419298679859783548');

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `classifica`
--
ALTER TABLE `classifica`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_classifica` (`torneo_id`,`squadra_id`),
  ADD KEY `fk_classifica_squadra` (`squadra_id`);

--
-- Indici per le tabelle `giocatore_squadra`
--
ALTER TABLE `giocatore_squadra`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_giocatore_squadra` (`squadra_id`,`utente_id`),
  ADD KEY `fk_gs_utente` (`utente_id`);

--
-- Indici per le tabelle `partita`
--
ALTER TABLE `partita`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_partita_torneo` (`torneo_id`),
  ADD KEY `fk_partita_casa` (`squadra_casa_id`),
  ADD KEY `fk_partita_ospite` (`squadra_ospite_id`);

--
-- Indici per le tabelle `pranzi`
--
ALTER TABLE `pranzi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_pranzo` (`torneo_id`,`squadra_id`),
  ADD KEY `squadra_id` (`squadra_id`);

--
-- Indici per le tabelle `squadra`
--
ALTER TABLE `squadra`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_squadra_torneo` (`torneo_id`),
  ADD KEY `fk_squadra_capitano` (`capitano_id`);

--
-- Indici per le tabelle `torneo`
--
ALTER TABLE `torneo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_torneo_creato_da` (`creato_da`);

--
-- Indici per le tabelle `torneo_seguito`
--
ALTER TABLE `torneo_seguito`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_torneo_seguito` (`torneo_id`,`utente_id`),
  ADD KEY `fk_tg_utente` (`utente_id`);

--
-- Indici per le tabelle `utente`
--
ALTER TABLE `utente`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `classifica`
--
ALTER TABLE `classifica`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `giocatore_squadra`
--
ALTER TABLE `giocatore_squadra`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=244;

--
-- AUTO_INCREMENT per la tabella `partita`
--
ALTER TABLE `partita`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=501;

--
-- AUTO_INCREMENT per la tabella `pranzi`
--
ALTER TABLE `pranzi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT per la tabella `squadra`
--
ALTER TABLE `squadra`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=178;

--
-- AUTO_INCREMENT per la tabella `torneo`
--
ALTER TABLE `torneo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT per la tabella `torneo_seguito`
--
ALTER TABLE `torneo_seguito`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT per la tabella `utente`
--
ALTER TABLE `utente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `classifica`
--
ALTER TABLE `classifica`
  ADD CONSTRAINT `fk_classifica_squadra` FOREIGN KEY (`squadra_id`) REFERENCES `squadra` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_classifica_torneo` FOREIGN KEY (`torneo_id`) REFERENCES `torneo` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `giocatore_squadra`
--
ALTER TABLE `giocatore_squadra`
  ADD CONSTRAINT `fk_gs_squadra` FOREIGN KEY (`squadra_id`) REFERENCES `squadra` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_gs_utente` FOREIGN KEY (`utente_id`) REFERENCES `utente` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `partita`
--
ALTER TABLE `partita`
  ADD CONSTRAINT `fk_partita_casa` FOREIGN KEY (`squadra_casa_id`) REFERENCES `squadra` (`id`),
  ADD CONSTRAINT `fk_partita_ospite` FOREIGN KEY (`squadra_ospite_id`) REFERENCES `squadra` (`id`),
  ADD CONSTRAINT `fk_partita_torneo` FOREIGN KEY (`torneo_id`) REFERENCES `torneo` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `pranzi`
--
ALTER TABLE `pranzi`
  ADD CONSTRAINT `pranzi_ibfk_1` FOREIGN KEY (`torneo_id`) REFERENCES `torneo` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pranzi_ibfk_2` FOREIGN KEY (`squadra_id`) REFERENCES `squadra` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `squadra`
--
ALTER TABLE `squadra`
  ADD CONSTRAINT `fk_squadra_capitano` FOREIGN KEY (`capitano_id`) REFERENCES `utente` (`id`),
  ADD CONSTRAINT `fk_squadra_torneo` FOREIGN KEY (`torneo_id`) REFERENCES `torneo` (`id`) ON DELETE CASCADE;

--
-- Limiti per la tabella `torneo`
--
ALTER TABLE `torneo`
  ADD CONSTRAINT `fk_torneo_creato_da` FOREIGN KEY (`creato_da`) REFERENCES `utente` (`id`);

--
-- Limiti per la tabella `torneo_seguito`
--
ALTER TABLE `torneo_seguito`
  ADD CONSTRAINT `fk_tg_torneo` FOREIGN KEY (`torneo_id`) REFERENCES `torneo` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tg_utente` FOREIGN KEY (`utente_id`) REFERENCES `utente` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
