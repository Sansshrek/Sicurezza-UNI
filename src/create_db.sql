DROP TABLE IF EXISTS unsecure_contacts;
DROP TABLE IF EXISTS unsecure_users;
DROP TABLE IF EXISTS secure_contacts;
DROP TABLE IF EXISTS secure_users;

-- DB Vulnerabile
CREATE TABLE IF NOT EXISTS unsecure_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS unsecure_contacts (
  user_id INT PRIMARY KEY,
  telefono VARCHAR(20),
  email VARCHAR(50),
  FOREIGN KEY (user_id) REFERENCES unsecure_users(id) ON DELETE CASCADE
) ENGINE=InnoDB;


INSERT INTO unsecure_users (username, password) VALUES
('admin', 'Admin2026!'),
('mario.rossi', 'MarioRossi!1'),
('luigi.verdi', 'Luigi_Verdi44'),
('giulia.bianchi', 'Giulietta89'),
('anna.neri', 'AnnaNeri$$$'),
('paolo.gialli', 'P_Gialli2023'),
('sara.blu', 'SaraBlu!'),
('marco.bruni', 'MarcoB_123'),
('elena.grigi', 'ElenaG_456'),
('matteo.rosa', 'MatteoR_789');


INSERT INTO unsecure_contacts (user_id, telefono, email) VALUES
(1, '3537495043', 'admin@google.com'),
(2, '3829664242', 'mario.rossi@hotmail.com'),
(3, '3101453613', 'luigi.green420@outlook.com'),
(4, '3359589413', 'bianchi_giulyy@libero.it'),
(5, '3515901893', 'anna.neri03@google.com'),
(6, '3358920585', 'paolo_gialli@google.com'),
(7, '3295894955', 'saretta.blu@hotmail.com'),
(8, '3737749589', 'marco._.bruni@google.com'),
(9, '3575800589', 'ele.grigi@outlook.com'),
(10, '3905890489', 'matti.rosa96@hotmail.com');


-- DB Sicuro
CREATE TABLE IF NOT EXISTS secure_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS secure_contacts (
  user_id INT PRIMARY KEY,
  telefono VARCHAR(20),
  email VARCHAR(50),
  FOREIGN KEY (user_id) REFERENCES secure_users(id) ON DELETE CASCADE
) ENGINE=InnoDB;


INSERT INTO secure_users (username, password) VALUES
('admin', '$2y$12$VMgb3W9UhRkYp.IwgOkm2.Z8ELheWEwU1T2BqkwXBqfNFJkpTsuja'),
('mario.rossi', '$2y$12$1XUBZquuZX31DwNom64fOOh4IEQd0nWbmTwLPfqStMWl6mxpgLT7q'),
('luigi.verdi', '$2y$12$O5yFJ24gDFkaMJhXOPysr.4/8mWMDJULTwoUUmHvYrioYtuLrGhDa'),
('giulia.bianchi', '$2y$12$T2BA5DRxMeQ0qjw9dONzIeFPI1K3gHtHyMdrtRjwoYaIpG1ny/ycu'),
('anna.neri', '$2y$12$mUC94qfmm01lf5kvJQLrS.nNuTHEmO2zVOelN4KeVEZwAwwRhAcqO'),
('paolo.gialli', '$2y$12$Dz6F3kesNF/qM9FTItjkEOVPOi5yD5yckTLVmdVTRZdFeZ4MtGlZu'),
('sara.blu', '$2y$12$UBsEE02r1uwh1RppZb.l4.ZRIyMHS4RRjR99.y1h4WWzflMqEv0py'),
('marco.bruni', '$2y$12$X6FZinuZB7Ve8U0.dvmtH.O7qYcf8l2GVR6o/u0z3QIoFuWIFOIIe'),
('elena.grigi', '$2y$12$6jjBN4ug0xjfCUwnM9/XNeU0iLoZ8WL7NLVpELFlVpTwQMfdoBQZm'),
('matteo.rosa', '$2y$12$ameP2Sqd7nzZijmI.eE7K.EkGc1ibCnlAdxLVF54k72mVeokrwziS');


INSERT INTO secure_contacts (user_id, telefono, email) VALUES
(1, '3537495043', 'admin@google.com'),
(2, '3829664242', 'mario.rossi@hotmail.com'),
(3, '3101453613', 'luigi.green420@outlook.com'),
(4, '3359589413', 'bianchi_giulyy@libero.it'),
(5, '3515901893', 'anna.neri03@google.com'),
(6, '3358920585', 'paolo_gialli@google.com'),
(7, '3295894955', 'saretta.blu@hotmail.com'),
(8, '3737749589', 'marco._.bruni@google.com'),
(9, '3575800589', 'ele.grigi@outlook.com'),
(10, '3905890489', 'matti.rosa96@hotmail.com');


-- difesa implementata sul principio del least privilege

-- crea utente specifico
CREATE USER IF NOT EXISTS 'least_privilege_user'@'%' IDENTIFIED BY 'Password!2026';

-- gli diamo permessi solo per leggere, inserire, aggiornare e eliminare singole righe 
GRANT SELECT, INSERT, UPDATE, DELETE ON testdb.* TO 'least_privilege_user'@'%';

-- togliamo i permessi di modificare tutta la tabella
REVOKE DROP, ALTER, CREATE ON testdb.* FROM 'least_privilege_user'@'%';

-- ricarica i permessi del db
FLUSH PRIVILEGES;