-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Creato il: Apr 28, 2026 alle 14:19
-- Versione del server: 10.11.16-MariaDB-cll-lve-log
-- Versione PHP: 8.4.20

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
CREATE DATABASE IF NOT EXISTS `itpbrgro_wp761` DEFAULT CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci;
USE `itpbrgro_wp761`;

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

-- --------------------------------------------------------

--
-- Struttura della tabella `partita`
--

CREATE TABLE `partita` (
  `id` int(11) NOT NULL,
  `torneo_id` int(11) NOT NULL,
  `squadra_casa_id` int(11) NOT NULL,
  `squadra_ospite_id` int(11) NOT NULL,
  `gol_casa` int(11) DEFAULT NULL,
  `gol_ospite` int(11) DEFAULT NULL,
  `girone` int(11) DEFAULT NULL,
  `turno` enum('ottavi','quarti','semifinale','finale') DEFAULT NULL,
  `giocata_il` timestamp NULL DEFAULT NULL,
  `stato` enum('programmata','in_corso','terminata') NOT NULL DEFAULT 'programmata'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `squadra`
--

CREATE TABLE `squadra` (
  `id` int(11) NOT NULL,
  `torneo_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `capitano_id` int(11) NOT NULL,
  `stato` enum('in_attesa','approvata','rifiutata') NOT NULL DEFAULT 'in_attesa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struttura della tabella `torneo`
--

CREATE TABLE `torneo` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `descrizione` varchar(255) DEFAULT NULL,
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

INSERT INTO `torneo` (`id`, `nome`, `descrizione`, `formato`, `tipo_partita`, `visibilita`, `numero_squadre`, `creato_da`, `stato`, `min_giocatori_per_squadra`, `max_giocatori_per_squadra`, `min_squadre`, `data_chiusura_iscrizioni`, `codice_privato`) VALUES
(2, 'prova', 'prova', 'girone_unico', 'andata', 'pubblico', 8, 22, 'aperto', 5, 10, 8, '2026-05-27 17:41:00', NULL),
(3, 'prova1', 'prova1', 'gironi_playoff', 'andata_ritorno', 'privato', 8, 22, 'aperto', 5, 10, 8, '2026-05-27 17:41:00', 'EFADB86B');

-- --------------------------------------------------------

--
-- Struttura della tabella `torneo_seguito`
--

CREATE TABLE `torneo_seguito` (
  `id` int(11) NOT NULL,
  `torneo_id` int(11) NOT NULL,
  `utente_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dump dei dati per la tabella `utente`
--

INSERT INTO `utente` (`id`, `nome`, `cognome`, `email`, `password`, `cod_ci`, `verified`, `token`, `created_at`) VALUES
(22, 'Luca', 'Bertolotti', 'cluchybertolotti@gmail.com', 'chvQ7Ow41U4RU', 'CA09365PZ', 1, NULL, '2026-04-25 10:49:26'),
(24, 'Liam', 'Tu', 'tusailiam@gmail.com', 'chvQ7Ow41U4RU', 'CA1f23e45', 1, NULL, '2026-04-27 10:00:43'),
(25, 'Matteo', 'Luciano', 'matteo.luciano07@gmail.com', 'chvQ7Ow41U4RU', 'CA840', 1, NULL, '2026-04-27 10:03:36');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `partita`
--
ALTER TABLE `partita`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `squadra`
--
ALTER TABLE `squadra`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `torneo`
--
ALTER TABLE `torneo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT per la tabella `torneo_seguito`
--
ALTER TABLE `torneo_seguito`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT per la tabella `utente`
--
ALTER TABLE `utente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

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