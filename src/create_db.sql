DROP TABLE IF EXISTS profiles;
DROP TABLE IF EXISTS users;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS profiles (
  user_id INT PRIMARY KEY,
  telefono VARCHAR(20),
  citta VARCHAR(50),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;


INSERT INTO users (username, password) VALUES
('admin', 'Admin_Root#2024'),
('mario.rossi', 'MarioRossi!1'),
('luigi.verdi', 'Luigi_Verdi44'),
('giulia.bianchi', 'Giulietta89'),
('anna.neri', 'AnnaNeri$$$'),
('paolo.gialli', 'P_Gialli2023'),
('sara.blu', 'SaraBlu!'),
('marco.bruni', 'MarcoB_123'),
('elena.grigi', 'ElenaG_456'),
('matteo.rosa', 'MatteoR_789');


INSERT INTO profiles (user_id, telefono, citta) VALUES
(1, '3330000001', 'Roma'),
(2, '3330000002', 'Milano'),
(3, '3330000003', 'Napoli'),
(4, '3330000004', 'Torino'),
(5, '3330000005', 'Palermo'),
(6, '3330000006', 'Genova'),
(7, '3330000007', 'Bologna'),
(8, '3330000008', 'Firenze'),
(9, '3330000009', 'Bari'),
(10, '3330000010', 'Catania');



-- difesa implementata sul principio del least privilege

-- crea utente specifico
CREATE USER IF NOT EXISTS 'webapp_user'@'%' IDENTIFIED BY 'WebApp_Password!2024';

-- gli diamo permessi solo per leggere, inserire, aggiornare e eliminare righe singole
GRANT SELECT, INSERT, UPDATE, DELETE ON testdb.* TO 'webapp_user'@'%';

-- togliamo i permessi di modificare tutta la tabella
REVOKE DROP, ALTER, CREATE ON testdb.* FROM 'webapp_user'@'%';

-- ricarica i permessi del db
FLUSH PRIVILEGES;