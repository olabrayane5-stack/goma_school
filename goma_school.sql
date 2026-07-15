-- ============================================================
--   GOMA SCHOOL INT'L — goma_school.sql
--   Script SQL complet et unifié
--   Inclut : tables, relations, données de test
--   Nouveauté : table reset_mdp pour réinitialisation MDP
--   Auteur : Voltaire SEGNIBO
-- ============================================================

-- ── Créer et sélectionner la BDD ──────────────────────────
DROP DATABASE IF EXISTS goma_school;
CREATE DATABASE goma_school
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE goma_school;

-- ============================================================
--   1. UTILISATEURS (classe mère)
-- ============================================================
CREATE TABLE utilisateurs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nom             VARCHAR(100)    NOT NULL,
    email           VARCHAR(150)    NOT NULL UNIQUE,
    telephone       VARCHAR(20),
    mot_de_passe    VARCHAR(255)    NOT NULL,
    date_naissance  DATE,
    role            ENUM('eleve','professeur') NOT NULL,
    actif           TINYINT(1)      DEFAULT 1,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
--   2. ÉLÈVES (hérite de utilisateurs)
-- ============================================================
CREATE TABLE eleves (
    id                      INT PRIMARY KEY,
    domaine_apprentissage   VARCHAR(100),
    niveau_actuel           VARCHAR(50),
    FOREIGN KEY (id)
        REFERENCES utilisateurs(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
--   3. PROFESSEURS (hérite de utilisateurs)
-- ============================================================
CREATE TABLE professeurs (
    id          INT PRIMARY KEY,
    specialite  VARCHAR(150),
    FOREIGN KEY (id)
        REFERENCES utilisateurs(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
--   4. COURS
--   Un professeur définit 1..* cours
-- ============================================================
CREATE TABLE cours (
    id_cours        INT AUTO_INCREMENT PRIMARY KEY,
    intitule        VARCHAR(150)    NOT NULL,
    description     TEXT,
    id_professeur   INT             NOT NULL,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_professeur)
        REFERENCES professeurs(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
--   5. EMPLOIS DU TEMPS
--   Un professeur planifie 0..* séances
-- ============================================================
CREATE TABLE emplois_du_temps (
    id_planning         INT AUTO_INCREMENT PRIMARY KEY,
    site_de_formation   ENUM('Misséreté','Donoukin','Tokpota') NOT NULL,
    date_heure          DATETIME        NOT NULL,
    id_professeur       INT             NOT NULL,
    id_cours            INT,
    created_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_professeur)
        REFERENCES professeurs(id)
        ON DELETE CASCADE,
    FOREIGN KEY (id_cours)
        REFERENCES cours(id_cours)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
--   6. INSCRIPTIONS
--   Un élève s'inscrit à un domaine
-- ============================================================
CREATE TABLE inscriptions (
    id_inscription      INT AUTO_INCREMENT PRIMARY KEY,
    date_inscription    DATE            NOT NULL DEFAULT (CURDATE()),
    domaine_choisi      VARCHAR(100)    NOT NULL,
    id_eleve            INT             NOT NULL,
    created_at          TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_eleve)
        REFERENCES eleves(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
--   7. PAIEMENTS
--   Un élève effectue un paiement lié à une inscription
-- ============================================================
CREATE TABLE paiements (
    id_paiement     INT AUTO_INCREMENT PRIMARY KEY,
    montant         DECIMAL(10,2)   NOT NULL,
    date_paiement   DATE            NOT NULL DEFAULT (CURDATE()),
    mode_paiement   ENUM('mobile_money','especes') DEFAULT 'especes',
    telephone_mm    VARCHAR(20),
    statut          ENUM('en_attente','confirme','echoue') DEFAULT 'en_attente',
    id_eleve        INT             NOT NULL,
    id_inscription  INT             NOT NULL,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_eleve)
        REFERENCES eleves(id)
        ON DELETE CASCADE,
    FOREIGN KEY (id_inscription)
        REFERENCES inscriptions(id_inscription)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
--   8. REÇUS
--   Un paiement génère 1 reçu
-- ============================================================
CREATE TABLE recus (
    id_recu         INT AUTO_INCREMENT PRIMARY KEY,
    numero_recu     VARCHAR(30)     NOT NULL UNIQUE,
    fichier_pdf     VARCHAR(255),
    date_generation TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    id_paiement     INT             NOT NULL UNIQUE,
    FOREIGN KEY (id_paiement)
        REFERENCES paiements(id_paiement)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
--   9. NOTES
--   Un professeur évalue un élève
-- ============================================================
CREATE TABLE notes (
    id_note         INT AUTO_INCREMENT PRIMARY KEY,
    valeur          DECIMAL(4,2)    NOT NULL,
    commentaire     TEXT,
    id_eleve        INT             NOT NULL,
    id_professeur   INT             NOT NULL,
    id_inscription  INT             NOT NULL,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_eleve)
        REFERENCES eleves(id)
        ON DELETE CASCADE,
    FOREIGN KEY (id_professeur)
        REFERENCES professeurs(id)
        ON DELETE CASCADE,
    FOREIGN KEY (id_inscription)
        REFERENCES inscriptions(id_inscription)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
--   10. NOTIFICATIONS
--   Chaque évaluation génère une notification à l'élève
-- ============================================================
CREATE TABLE notifications (
    id_notification INT AUTO_INCREMENT PRIMARY KEY,
    message         TEXT            NOT NULL,
    lu              TINYINT(1)      DEFAULT 0,
    id_eleve        INT             NOT NULL,
    id_note         INT,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_eleve)
        REFERENCES eleves(id)
        ON DELETE CASCADE,
    FOREIGN KEY (id_note)
        REFERENCES notes(id_note)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
--   11. RESET MOT DE PASSE
--   Tokens pour réinitialisation par email
-- ============================================================
CREATE TABLE reset_mdp (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    id_user     INT             NOT NULL,
    token       VARCHAR(64)     NOT NULL UNIQUE,
    expire_at   DATETIME        NOT NULL,
    utilise     TINYINT(1)      DEFAULT 0,
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user)
        REFERENCES utilisateurs(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
--   INDEX POUR LES PERFORMANCES
-- ============================================================
CREATE INDEX idx_utilisateurs_email   ON utilisateurs(email);
CREATE INDEX idx_utilisateurs_role    ON utilisateurs(role);
CREATE INDEX idx_inscriptions_eleve   ON inscriptions(id_eleve);
CREATE INDEX idx_paiements_eleve      ON paiements(id_eleve);
CREATE INDEX idx_paiements_statut     ON paiements(statut);
CREATE INDEX idx_notes_eleve          ON notes(id_eleve);
CREATE INDEX idx_notes_prof           ON notes(id_professeur);
CREATE INDEX idx_notifs_eleve         ON notifications(id_eleve);
CREATE INDEX idx_notifs_lu            ON notifications(lu);
CREATE INDEX idx_planning_prof        ON emplois_du_temps(id_professeur);
CREATE INDEX idx_planning_site        ON emplois_du_temps(site_de_formation);
CREATE INDEX idx_reset_token          ON reset_mdp(token);
CREATE INDEX idx_reset_user           ON reset_mdp(id_user);

-- ============================================================
--   DONNÉES DE TEST
-- ============================================================

-- ── Professeur ────────────────────────────────────────────
INSERT INTO utilisateurs (nom, email, telephone, mot_de_passe, date_naissance, role)
VALUES (
    'Jean ADANLE',
    'jean.adanle@gomaschool.bj',
    '+22960000001',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.',
    -- mot de passe : password
    '1985-03-15',
    'professeur'
);

INSERT INTO professeurs (id, specialite)
VALUES (1, 'Piano et Théorie musicale');

-- ── Élève 1 ───────────────────────────────────────────────
INSERT INTO utilisateurs (nom, email, telephone, mot_de_passe, date_naissance, role)
VALUES (
    'Koffi MENSAH',
    'koffi.mensah@gmail.com',
    '+22961000002',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.',
    '2005-07-20',
    'eleve'
);

INSERT INTO eleves (id, domaine_apprentissage, niveau_actuel)
VALUES (2, 'Piano', 'Débutant');

-- ── Élève 2 ───────────────────────────────────────────────
INSERT INTO utilisateurs (nom, email, telephone, mot_de_passe, date_naissance, role)
VALUES (
    'Afi KOUDOU',
    'afi.koudou@gmail.com',
    '+22962000003',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.',
    '2007-02-10',
    'eleve'
);

INSERT INTO eleves (id, domaine_apprentissage, niveau_actuel)
VALUES (3, 'Guitare', 'Intermédiaire');

-- ── Cours ─────────────────────────────────────────────────
INSERT INTO cours (intitule, description, id_professeur) VALUES
('Piano débutant',     'Introduction aux bases du piano pour débutants', 1),
('Piano intermédiaire','Techniques avancées et lecture de partition',     1),
('Théorie musicale',   'Solfège, harmonie et composition',                1);

-- ── Emplois du temps ──────────────────────────────────────
INSERT INTO emplois_du_temps (site_de_formation, date_heure, id_professeur, id_cours) VALUES
('Misséreté', '2025-09-01 09:00:00', 1, 1),
('Donoukin',  '2025-09-02 10:00:00', 1, 2),
('Tokpota',   '2025-09-03 14:00:00', 1, 3);

-- ── Inscriptions ──────────────────────────────────────────
INSERT INTO inscriptions (date_inscription, domaine_choisi, id_eleve) VALUES
('2025-08-15', 'Piano',   2),
('2025-08-16', 'Guitare', 3);

-- ── Paiements ─────────────────────────────────────────────
INSERT INTO paiements (montant, date_paiement, mode_paiement, statut, id_eleve, id_inscription) VALUES
(15000.00, '2025-08-15', 'mobile_money', 'confirme', 2, 1),
(12000.00, '2025-08-16', 'especes',      'confirme', 3, 2);

-- ── Reçus ─────────────────────────────────────────────────
INSERT INTO recus (numero_recu, fichier_pdf, id_paiement) VALUES
('GOMA-20250815-0001', 'recus/GOMA-20250815-0001.pdf', 1),
('GOMA-20250816-0002', 'recus/GOMA-20250816-0002.pdf', 2);

-- ── Notes ─────────────────────────────────────────────────
INSERT INTO notes (valeur, commentaire, id_eleve, id_professeur, id_inscription) VALUES
(14.50, 'Bon début, posture à améliorer.',        2, 1, 1),
(16.00, 'Très bonne progression sur les accords.', 3, 1, 2);

-- ── Notifications ─────────────────────────────────────────
INSERT INTO notifications (message, id_eleve, id_note) VALUES
('Nouvelle note reçue : 14.50/20 — Bon début, posture à améliorer.',        2, 1),
('Nouvelle note reçue : 16.00/20 — Très bonne progression sur les accords.', 3, 2);

-- ============================================================
--   VÉRIFICATION FINALE
-- ============================================================
SELECT '✓ Tables créées :' AS '';
SHOW TABLES;

SELECT '✓ Utilisateurs :' AS '';
SELECT id, nom, email, role FROM utilisateurs;

SELECT '✓ Cours :' AS '';
SELECT id_cours, intitule FROM cours;
