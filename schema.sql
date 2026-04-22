CREATE DATABASE IF NOT EXISTS torneo
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE torneo;

-- ------------------------------------------------------------
-- UTENTE
-- ------------------------------------------------------------
CREATE TABLE utente (
  id             INT NOT NULL AUTO_INCREMENT,
  nome           VARCHAR(100) NOT NULL,
  cognome        VARCHAR(100) NOT NULL,
  email          VARCHAR(255) NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  codice_fiscale VARCHAR(20) NOT NULL UNIQUE,

  PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TORNEO
-- ------------------------------------------------------------
CREATE TABLE torneo (
  id              INT NOT NULL AUTO_INCREMENT,
  nome            VARCHAR(150) NOT NULL,
  descrizione     VARCHAR(255),
  formato         ENUM('girone_unico','eliminazione_diretta','gironi_playoff') NOT NULL,
  tipo_partita    ENUM('andata','andata_ritorno') NOT NULL,
  visibilita      ENUM('pubblico','privato') NOT NULL DEFAULT 'pubblico',
  numero_squadre  INT NOT NULL,
  creato_da       INT NOT NULL,
  stato           ENUM('aperto','in_corso','completato') NOT NULL DEFAULT 'aperto',
  min_giocatori_per_squadra	INT NOT NULL,	
	max_giocatori_per_squadra	INT NOT NULL,

  PRIMARY KEY (id),

  CONSTRAINT fk_torneo_creato_da
    FOREIGN KEY (creato_da) REFERENCES utente(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TORNEO_SEGUITO
-- ------------------------------------------------------------
CREATE TABLE torneo_seguito (
  id         INT NOT NULL AUTO_INCREMENT,
  torneo_id  INT NOT NULL,
  utente_id  INT NOT NULL,

  PRIMARY KEY (id),

  UNIQUE KEY uq_torneo_seguito (torneo_id, utente_id),

  CONSTRAINT fk_tg_torneo
    FOREIGN KEY (torneo_id) REFERENCES torneo(id)
    ON DELETE CASCADE,

  CONSTRAINT fk_tg_utente
    FOREIGN KEY (utente_id) REFERENCES utente(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- SQUADRA
-- ------------------------------------------------------------
CREATE TABLE squadra (
  id           INT NOT NULL AUTO_INCREMENT,
  torneo_id    INT NOT NULL,
  nome         VARCHAR(100) NOT NULL,
  capitano_id  INT NOT NULL,
  stato        ENUM('in_attesa','approvata','rifiutata') NOT NULL DEFAULT 'in_attesa',

  PRIMARY KEY (id),

  CONSTRAINT fk_squadra_torneo
    FOREIGN KEY (torneo_id) REFERENCES torneo(id)
    ON DELETE CASCADE,

  CONSTRAINT fk_squadra_capitano
    FOREIGN KEY (capitano_id) REFERENCES utente(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- GIOCATORE_SQUADRA
-- ------------------------------------------------------------
CREATE TABLE giocatore_squadra (
  id          INT NOT NULL AUTO_INCREMENT,
  squadra_id  INT NOT NULL,
  utente_id   INT NOT NULL,

  PRIMARY KEY (id),

  UNIQUE KEY uq_giocatore_squadra (squadra_id, utente_id),

  CONSTRAINT fk_gs_squadra
    FOREIGN KEY (squadra_id) REFERENCES squadra(id)
    ON DELETE CASCADE,

  CONSTRAINT fk_gs_utente
    FOREIGN KEY (utente_id) REFERENCES utente(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- PARTITA
-- ------------------------------------------------------------
CREATE TABLE partita (
  id               INT NOT NULL AUTO_INCREMENT,
  torneo_id        INT NOT NULL,
  squadra_casa_id  INT NOT NULL,
  squadra_ospite_id INT NOT NULL,
  gol_casa         INT NULL,
  gol_ospite       INT NULL,
  girone           INT NULL,
  turno            ENUM('ottavi','quarti','semifinale','finale') NULL,
  giocata_il       TIMESTAMP NULL,
  stato            ENUM('programmata','in_corso','terminata') NOT NULL DEFAULT 'programmata',

  PRIMARY KEY (id),

  CONSTRAINT fk_partita_torneo
    FOREIGN KEY (torneo_id) REFERENCES torneo(id)
    ON DELETE CASCADE,

  CONSTRAINT fk_partita_casa
    FOREIGN KEY (squadra_casa_id) REFERENCES squadra(id)
    ON DELETE RESTRICT,

  CONSTRAINT fk_partita_ospite
    FOREIGN KEY (squadra_ospite_id) REFERENCES squadra(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- CLASSIFICA
-- ------------------------------------------------------------
CREATE TABLE classifica (
  id              INT NOT NULL AUTO_INCREMENT,
  torneo_id       INT NOT NULL,
  squadra_id      INT NOT NULL,
  partite_giocate INT NOT NULL DEFAULT 0,
  vittorie        INT NOT NULL DEFAULT 0,
  pareggi         INT NOT NULL DEFAULT 0,
  sconfitte       INT NOT NULL DEFAULT 0,
  gol_fatti       INT NOT NULL DEFAULT 0,
  gol_subiti      INT NOT NULL DEFAULT 0,
  punti           INT NOT NULL DEFAULT 0,

  PRIMARY KEY (id),

  UNIQUE KEY uq_classifica (torneo_id, squadra_id),

  CONSTRAINT fk_classifica_torneo
    FOREIGN KEY (torneo_id) REFERENCES torneo(id)
    ON DELETE CASCADE,

  CONSTRAINT fk_classifica_squadra
    FOREIGN KEY (squadra_id) REFERENCES squadra(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;