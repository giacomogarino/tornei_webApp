-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Creato il: Mag 14, 2026 alle 11:22
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
  `gol_fatti` int(11) NOT NULL DEFAULT 0,
  `gol_subiti` int(11) NOT NULL DEFAULT 0,
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
(52, 12, 22),
(55, 14, 26),
(60, 19, 22),
(61, 20, 25),
(62, 21, 26),
(63, 22, 24),
(64, 23, 24),
(65, 24, 22),
(66, 25, 25),
(67, 26, 26),
(68, 27, 22),
(69, 28, 24),
(71, 28, 30),
(72, 28, 32),
(70, 28, 33),
(73, 29, 26),
(74, 30, 25),
(75, 31, 27),
(76, 32, 29),
(77, 33, 28),
(78, 34, 34);

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
(33, 25, 24, 25, 3, 2, NULL, 'semifinale', '2026-10-10 13:00:00', 'terminata', 'andata'),
(34, 25, 25, 24, 2, 9, NULL, 'semifinale', NULL, 'terminata', 'ritorno'),
(35, 25, 23, 26, 6, 3, NULL, 'semifinale', NULL, 'terminata', 'andata'),
(36, 25, 26, 23, 8, 8, NULL, 'semifinale', NULL, 'terminata', 'ritorno'),
(49, 26, 21, 19, NULL, NULL, NULL, 'semifinale', NULL, 'programmata', 'andata'),
(50, 26, 22, 20, NULL, NULL, NULL, 'semifinale', NULL, 'programmata', 'andata'),
(55, 25, 23, 24, 1, 0, NULL, '', NULL, 'terminata', 'andata'),
(56, 25, 24, 23, 2, 0, NULL, '', NULL, 'terminata', 'ritorno'),
(72, 31, 33, 29, 8, 7, 1, NULL, NULL, 'terminata', 'andata'),
(73, 31, 29, 30, 7, 8, 1, NULL, NULL, 'terminata', 'andata'),
(74, 31, 31, 29, 5, 4, 1, NULL, NULL, 'terminata', 'andata'),
(75, 31, 31, 30, 1, 1, 1, NULL, NULL, 'terminata', 'andata'),
(76, 31, 33, 30, 0, 1, 1, NULL, NULL, 'terminata', 'andata'),
(77, 31, 31, 33, 4, 2, 1, NULL, NULL, 'terminata', 'andata'),
(78, 31, 28, 34, 1, 2, 2, NULL, NULL, 'terminata', 'andata'),
(79, 31, 27, 34, 0, 1, 2, NULL, NULL, 'terminata', 'andata'),
(80, 31, 27, 28, 7, 1, 2, NULL, NULL, 'terminata', 'andata'),
(81, 31, 27, 32, 1, 0, 2, NULL, NULL, 'terminata', 'andata'),
(82, 31, 34, 32, 0, 8, 2, NULL, NULL, 'terminata', 'andata'),
(83, 31, 28, 32, 8, 7, 2, NULL, NULL, 'terminata', 'andata'),
(84, 31, 27, 34, 1, 2, NULL, 'semifinale', NULL, 'terminata', 'andata'),
(85, 31, 30, 31, 1, 7, NULL, 'semifinale', NULL, 'terminata', 'andata'),
(86, 31, 34, 31, 7, 8, NULL, 'finale', NULL, 'terminata', 'andata');

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
(12, 27, 'squadra1', 22, 'approvata', NULL, NULL, 0),
(14, 27, 'squadra2', 26, 'approvata', NULL, NULL, 0),
(19, 26, 'squadra1', 22, 'approvata', NULL, NULL, 0),
(20, 26, 'squadra2', 25, 'approvata', NULL, NULL, 0),
(21, 26, 'squadra3', 26, 'approvata', NULL, NULL, 0),
(22, 26, 'squadra4', 24, 'approvata', NULL, NULL, 0),
(23, 25, 'squadra1', 24, 'approvata', NULL, NULL, 0),
(24, 25, 'squadra2', 22, 'approvata', NULL, NULL, 0),
(25, 25, 'squadra3', 25, 'approvata', NULL, NULL, 0),
(26, 25, 'squadra4', 26, 'approvata', NULL, NULL, 0),
(27, 31, 'squadra1', 22, 'approvata', NULL, NULL, 0),
(28, 31, 'CianoGoatTeam', 24, 'approvata', NULL, NULL, 0),
(29, 31, 'squadra2', 26, 'approvata', NULL, NULL, 0),
(30, 31, 'squadra4', 25, 'approvata', NULL, NULL, 0),
(31, 31, 'squadra5', 27, 'approvata', NULL, NULL, 0),
(32, 31, 'GariRobloxTeam', 29, 'approvata', NULL, NULL, 0),
(33, 31, 'squadra6', 28, 'approvata', NULL, NULL, 0),
(34, 31, 'squadra7', 34, 'approvata', NULL, NULL, 0),
(36, 28, 's1', 25, 'approvata', NULL, NULL, 0),
(39, 28, 's2', 26, 'approvata', NULL, NULL, 0);

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
  `tipo_partita` enum('andata','andata_ritorno') NOT NULL,
  `visibilita` enum('pubblico','privato') NOT NULL DEFAULT 'pubblico',
  `numero_squadre` int(11) NOT NULL,
  `creato_da` int(11) NOT NULL,
  `stato` enum('aperto','in_corso','completato') NOT NULL DEFAULT 'aperto',
  `min_giocatori_per_squadra` int(11) NOT NULL,
  `max_giocatori_per_squadra` int(11) NOT NULL,
  `min_squadre` int(11) NOT NULL DEFAULT 2,
  `data_chiusura_iscrizioni` datetime NOT NULL,
  `codice_privato` varchar(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dump dei dati per la tabella `torneo`
--

INSERT INTO `torneo` (`id`, `sport`, `nome`, `descrizione`, `luogo`, `formato`, `tipo_partita`, `visibilita`, `numero_squadre`, `creato_da`, `stato`, `min_giocatori_per_squadra`, `max_giocatori_per_squadra`, `min_squadre`, `data_chiusura_iscrizioni`, `codice_privato`) VALUES
(25, 'calcio', 'Kings league', 'Copia di Blur', 'Cuneo', 'eliminazione_diretta', 'andata_ritorno', 'pubblico', 8, 25, 'in_corso', 1, 5, 4, '2026-05-11 14:55:00', NULL),
(26, 'calcio', 'Youtuber league', 'Copia di PirlasV', 'Milano', 'girone_unico', 'andata', 'pubblico', 8, 25, 'in_corso', 1, 5, 4, '2026-05-12 14:55:00', NULL),
(27, 'calcio', 'Goat league', 'Creato da Liam', 'Valdieri', 'eliminazione_diretta', 'andata', 'pubblico', 8, 25, 'aperto', 1, 5, 4, '2032-10-26 14:55:00', NULL),
(28, 'calcio', 'Torneo della rocha', 'torneo che si terra\' al parchetto della rocha', 'Roccabruna', 'eliminazione_diretta', 'andata_ritorno', 'privato', 8, 25, 'in_corso', 1, 5, 4, '2026-05-06 08:00:00', '28C5209C'),
(30, 'calcio', 'torneo bernezzo ', 'eliminazione diretta andata e ritorno', 'Bernezzo', 'eliminazione_diretta', 'andata_ritorno', 'privato', 8, 26, 'in_corso', 1, 5, 4, '2026-05-13 08:30:00', '0'),
(31, 'calcio', 'Torneo di San Benigno', 'torneo a san benigno', 'San Benigno', 'gironi_playoff', 'andata', 'pubblico', 8, 25, 'completato', 1, 5, 4, '2026-05-13 08:35:00', NULL);

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
(18, 27, 22),
(19, 27, 26),
(20, 28, 25);

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
  `cod_ci` varchar(20) NOT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `token` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `utente`
--

INSERT INTO `utente` (`id`, `nome`, `cognome`, `email`, `password`, `cod_ci`, `verified`, `token`, `created_at`, `token_expiry`) VALUES
(22, 'Luca', 'Bertolotti', 'cluchybertolotti@gmail.com', 'chvQ7Ow41U4RU', 'CA09365PZ', 1, NULL, '2026-04-25 10:49:26', NULL),
(24, 'Liam', 'Tu', 'tusailiam@gmail.com', 'chvQ7Ow41U4RU', 'CA1f23e45', 1, NULL, '2026-04-27 10:00:43', NULL),
(25, 'Matteo', 'Luciano', 'matteo.luciano07@gmail.com', 'chvQ7Ow41U4RU', 'CA840', 1, NULL, '2026-04-27 10:03:36', NULL),
(26, 'Giacomo', 'Garino', 'giacomo.garino@itiscuneo.edu.it', 'chvQ7Ow41U4RU', 'CA66868KR', 1, NULL, '2026-04-28 12:24:34', NULL),
(27, 'Martina', 'Luciano', 'martina.luciano2009@gmail.com', 'chvQ7Ow41U4RU', 'CA86945RB', 1, NULL, '2026-04-30 05:13:04', NULL),
(28, 'Matteo', 'Olivero', 'olivero.matteo007@gmail.com', 'chvQ7Ow41U4RU', 'Aa1123bb', 1, NULL, '2026-04-30 11:55:50', NULL),
(29, 'Sai', 'You', 'sailiam.tu@itiscuneo.edu.it', 'chHGEgcEngOcc', 'CA09365jstnf', 1, NULL, '2026-05-05 12:12:30', NULL),
(30, 'Filippo', 'Gondolo', 'filippo.gondolo@gmail.com', 'cha/.4kD3E0Ek', 'CAry87t9y09', 1, NULL, '2026-05-05 12:15:50', NULL),
(31, 'Francesco', 'Torterolo', 'torterolofrancesco@gmail.com', 'ch69ffFeBBhXM', 'CAei3mc0r2h9', 1, NULL, '2026-05-05 12:18:57', NULL),
(32, 'Torta', 'France', 'francesco.torterolo@itiscuneo.edu.it', 'chzEBL2/VXci.', 'CAhncr23902', 1, NULL, '2026-05-05 12:21:59', NULL),
(33, 'Gondy', 'Pippo', 'filippo.gondolo@itiscuneo.edu.it', 'chdxK5wD9Rt8I', 'CAnceh39h0', 1, NULL, '2026-05-05 12:23:24', NULL),
(34, 'Micol', 'Stanisci', 'micolstanisci@gmail.com', 'chvQ7Ow41U4RU', 'CA0011DD', 1, NULL, '2026-05-06 05:47:30', NULL);

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
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `codice_fiscale` (`cod_ci`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT per la tabella `partita`
--
ALTER TABLE `partita`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT per la tabella `pranzi`
--
ALTER TABLE `pranzi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT per la tabella `squadra`
--
ALTER TABLE `squadra`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT per la tabella `torneo`
--
ALTER TABLE `torneo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT per la tabella `torneo_seguito`
--
ALTER TABLE `torneo_seguito`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT per la tabella `utente`
--
ALTER TABLE `utente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

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
