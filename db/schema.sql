-- NONAKTIFKAN FOREIGN KEY CHECKS
SET FOREIGN_KEY_CHECKS = 0;

-- BERSIHKAN TABEL LAMA
DROP TABLE IF EXISTS Invitasi;
DROP TABLE IF EXISTS Keanggotaan_Tim;
DROP TABLE IF EXISTS Tim;
DROP TABLE IF EXISTS Lomba_Kategori;
DROP TABLE IF EXISTS Lomba;
DROP TABLE IF EXISTS Mahasiswa_Skill;
DROP TABLE IF EXISTS Mahasiswa_Role;
DROP TABLE IF EXISTS Dosen_Keahlian;
DROP TABLE IF EXISTS Dosen_Role;
DROP TABLE IF EXISTS Mahasiswa;
DROP TABLE IF EXISTS Dosen_Pembimbing;
DROP TABLE IF EXISTS Prodi;
DROP TABLE IF EXISTS Fakultas;
DROP TABLE IF EXISTS Admin;
DROP TABLE IF EXISTS Kategori_Lomba;
DROP TABLE IF EXISTS Jenis_Penyelenggara;
DROP TABLE IF EXISTS Tingkatan_Lomba;
DROP TABLE IF EXISTS Peringkat_Juara;
DROP TABLE IF EXISTS Skill;
DROP TABLE IF EXISTS Role_Tim;

SET FOREIGN_KEY_CHECKS = 1;

-- ================= MASTER DATA =================

CREATE TABLE Fakultas (
    ID_Fakultas INT AUTO_INCREMENT PRIMARY KEY,
    Nama_Fakultas VARCHAR(100) NOT NULL
);

CREATE TABLE Prodi (
    ID_Prodi INT AUTO_INCREMENT PRIMARY KEY,
    Nama_Prodi VARCHAR(100) NOT NULL,
    ID_Fakultas INT,
    FOREIGN KEY (ID_Fakultas) REFERENCES Fakultas(ID_Fakultas) ON DELETE SET NULL
);

CREATE TABLE Kategori_Lomba (
    ID_Kategori INT AUTO_INCREMENT PRIMARY KEY,
    Nama_Kategori VARCHAR(100) NOT NULL
);

CREATE TABLE Jenis_Penyelenggara (
    ID_Jenis INT AUTO_INCREMENT PRIMARY KEY,
    Nama_Jenis VARCHAR(100) NOT NULL,
    Bobot_Poin FLOAT DEFAULT 1.0
);

CREATE TABLE Tingkatan_Lomba (
    ID_Tingkatan INT AUTO_INCREMENT PRIMARY KEY,
    Nama_Tingkatan VARCHAR(50) NOT NULL,
    Poin_Dasar INT NOT NULL
);

CREATE TABLE Peringkat_Juara (
    ID_Peringkat INT AUTO_INCREMENT PRIMARY KEY,
    Nama_Peringkat VARCHAR(50) NOT NULL, 
    Multiplier_Poin FLOAT DEFAULT 0.0 
);

-- TABEL SKILL (TOOLS)
CREATE TABLE Skill (
    ID_Skill INT AUTO_INCREMENT PRIMARY KEY,
    Nama_Skill VARCHAR(100) NOT NULL
);

-- TABEL ROLE (PROFESI)
CREATE TABLE Role_Tim (
    ID_Role INT AUTO_INCREMENT PRIMARY KEY,
    Nama_Role VARCHAR(100) NOT NULL
);

-- ================= USER DATA =================

CREATE TABLE Admin (
    ID_Admin INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) UNIQUE NOT NULL,
    Password_Hash VARCHAR(255) NOT NULL,
    Nama_Lengkap VARCHAR(100),
    Level ENUM('superadmin', 'admin') NOT NULL DEFAULT 'admin',
    Foto_Profil VARCHAR(255) DEFAULT NULL
);

CREATE TABLE Dosen_Pembimbing (
    ID_Dosen INT AUTO_INCREMENT PRIMARY KEY,
    NIDN VARCHAR(20) UNIQUE NOT NULL,
    Nama_Dosen VARCHAR(150) NOT NULL,
    Email VARCHAR(100) UNIQUE,
    Password_Hash VARCHAR(255) NOT NULL,
    Tempat_Lahir VARCHAR(50),
    Tanggal_Lahir DATE,
    Bio TEXT,
    No_HP VARCHAR(20) DEFAULT NULL,
    LinkedIn VARCHAR(255) DEFAULT NULL,
    ID_Prodi INT,
    Foto_Profil VARCHAR(255) DEFAULT NULL,
    Is_Verified TINYINT(1) DEFAULT 1,
    Is_Admin TINYINT(1) DEFAULT 0, -- 1 = Bisa Kelola Data Lomba
    Verification_Token VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (ID_Prodi) REFERENCES Prodi(ID_Prodi) ON DELETE SET NULL
);

CREATE TABLE Mahasiswa (
    ID_Mahasiswa INT AUTO_INCREMENT PRIMARY KEY,
    NIM VARCHAR(20) UNIQUE DEFAULT NULL,
    Nama_Mahasiswa VARCHAR(150) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Password_Hash VARCHAR(255) NOT NULL,
    Tempat_Lahir VARCHAR(50),
    Tanggal_Lahir DATE,
    Bio TEXT,
    No_HP VARCHAR(20) DEFAULT NULL,
    LinkedIn VARCHAR(255) DEFAULT NULL,
    Total_Poin FLOAT DEFAULT 0,
    ID_Prodi INT DEFAULT NULL,
    Foto_Profil VARCHAR(255) DEFAULT NULL,
    Is_Verified TINYINT(1) DEFAULT 0,
    Verification_Token VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (ID_Prodi) REFERENCES Prodi(ID_Prodi) ON DELETE SET NULL
);

-- PIVOT: Mahasiswa - Skill
CREATE TABLE Mahasiswa_Skill (
    ID_Mhs_Skill INT AUTO_INCREMENT PRIMARY KEY,
    ID_Mahasiswa INT,
    ID_Skill INT,
    FOREIGN KEY (ID_Mahasiswa) REFERENCES Mahasiswa(ID_Mahasiswa) ON DELETE CASCADE,
    FOREIGN KEY (ID_Skill) REFERENCES Skill(ID_Skill) ON DELETE CASCADE
);

-- PIVOT: Mahasiswa - Role
CREATE TABLE Mahasiswa_Role (
    ID_Mhs_Role INT AUTO_INCREMENT PRIMARY KEY,
    ID_Mahasiswa INT,
    ID_Role INT,
    FOREIGN KEY (ID_Mahasiswa) REFERENCES Mahasiswa(ID_Mahasiswa) ON DELETE CASCADE,
    FOREIGN KEY (ID_Role) REFERENCES Role_Tim(ID_Role) ON DELETE CASCADE
);

-- PIVOT: Dosen - Skill
CREATE TABLE Dosen_Keahlian (
    ID_Dsn_Keahlian INT AUTO_INCREMENT PRIMARY KEY,
    ID_Dosen INT,
    ID_Skill INT,
    FOREIGN KEY (ID_Dosen) REFERENCES Dosen_Pembimbing(ID_Dosen) ON DELETE CASCADE,
    FOREIGN KEY (ID_Skill) REFERENCES Skill(ID_Skill) ON DELETE CASCADE
);

-- PIVOT: Dosen - Role (Minat Bimbingan)
CREATE TABLE Dosen_Role (
    ID_Dsn_Role INT AUTO_INCREMENT PRIMARY KEY,
    ID_Dosen INT,
    ID_Role INT,
    FOREIGN KEY (ID_Dosen) REFERENCES Dosen_Pembimbing(ID_Dosen) ON DELETE CASCADE,
    FOREIGN KEY (ID_Role) REFERENCES Role_Tim(ID_Role) ON DELETE CASCADE
);

-- ================= KOMPETISI & TIM =================

CREATE TABLE Lomba (
    ID_Lomba INT AUTO_INCREMENT PRIMARY KEY,
    Nama_Lomba VARCHAR(200) NOT NULL,
    Deskripsi TEXT,
    Lokasi VARCHAR(100),
    Link_Web VARCHAR(255),
    Tanggal_Mulai DATE,
    Tanggal_Selesai DATE,
    ID_Jenis_Penyelenggara INT,
    ID_Tingkatan INT,
    FOREIGN KEY (ID_Jenis_Penyelenggara) REFERENCES Jenis_Penyelenggara(ID_Jenis),
    FOREIGN KEY (ID_Tingkatan) REFERENCES Tingkatan_Lomba(ID_Tingkatan)
);

-- PIVOT: Lomba - Kategori (One Lomba to Many Categories)
CREATE TABLE Lomba_Kategori (
    ID_LK INT AUTO_INCREMENT PRIMARY KEY,
    ID_Lomba INT,
    ID_Kategori INT,
    FOREIGN KEY (ID_Lomba) REFERENCES Lomba(ID_Lomba) ON DELETE CASCADE,
    FOREIGN KEY (ID_Kategori) REFERENCES Kategori_Lomba(ID_Kategori) ON DELETE CASCADE
);

CREATE TABLE Tim (
    ID_Tim INT AUTO_INCREMENT PRIMARY KEY,
    Nama_Tim VARCHAR(100) NOT NULL,
    Status_Pencarian ENUM('Terbuka', 'Tertutup') DEFAULT 'Terbuka',
    Deskripsi_Tim TEXT,
    Kebutuhan_Role TEXT DEFAULT NULL, -- Disimpan comma separated string simple
    ID_Lomba INT NOT NULL,
    ID_Kategori INT DEFAULT NULL, -- Kategori spesifik yg diikuti tim
    ID_Mahasiswa_Ketua INT NOT NULL,
    ID_Dosen_Pembimbing INT,
    ID_Peringkat INT,
    FOREIGN KEY (ID_Lomba) REFERENCES Lomba(ID_Lomba) ON DELETE CASCADE,
    FOREIGN KEY (ID_Kategori) REFERENCES Kategori_Lomba(ID_Kategori) ON DELETE SET NULL,
    FOREIGN KEY (ID_Mahasiswa_Ketua) REFERENCES Mahasiswa(ID_Mahasiswa),
    FOREIGN KEY (ID_Dosen_Pembimbing) REFERENCES Dosen_Pembimbing(ID_Dosen),
    FOREIGN KEY (ID_Peringkat) REFERENCES Peringkat_Juara(ID_Peringkat)
);

CREATE TABLE Keanggotaan_Tim (
    ID_Keanggotaan INT AUTO_INCREMENT PRIMARY KEY,
    ID_Tim INT NOT NULL,
    ID_Mahasiswa INT NOT NULL,
    Peran VARCHAR(100),
    Status VARCHAR(50) DEFAULT 'Diterima',
    FOREIGN KEY (ID_Tim) REFERENCES Tim(ID_Tim) ON DELETE CASCADE,
    FOREIGN KEY (ID_Mahasiswa) REFERENCES Mahasiswa(ID_Mahasiswa) ON DELETE CASCADE
);

CREATE TABLE Invitasi (
    ID_Invitasi INT AUTO_INCREMENT PRIMARY KEY,
    ID_Tim INT NOT NULL,
    ID_Pengirim INT NOT NULL, -- ID Ketua Tim
    ID_Penerima INT NOT NULL, -- ID Mahasiswa atau ID Dosen
    Tipe_Penerima ENUM('mahasiswa', 'dosen') NOT NULL,
    Status ENUM('Pending', 'Diterima', 'Ditolak') DEFAULT 'Pending',
    Tanggal_Kirim DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_Tim) REFERENCES Tim(ID_Tim) ON DELETE CASCADE
);

-- 1. Tambah kolom Nama Penyelenggara & Foto Lomba
ALTER TABLE Lomba ADD COLUMN Nama_Penyelenggara VARCHAR(150) AFTER ID_Jenis_Penyelenggara;
ALTER TABLE Lomba ADD COLUMN Foto_Lomba VARCHAR(255) DEFAULT NULL AFTER Deskripsi;

-- 2. Pastikan tabel Admin punya kolom Email (jika belum) untuk fitur invite
ALTER TABLE Admin ADD COLUMN Email VARCHAR(100) UNIQUE AFTER Nama_Lengkap;

ALTER TABLE Mahasiswa ADD COLUMN Need_Reset TINYINT(1) DEFAULT 0;
ALTER TABLE Dosen_Pembimbing ADD COLUMN Need_Reset TINYINT(1) DEFAULT 0;

-- ================= SEEDING (DATA DUMMY) =================
-- NOTE: Password default untuk semua user adalah 'password'
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

-- 1. FAKULTAS
INSERT INTO Fakultas (Nama_Fakultas) VALUES 
('Ilmu Komputer dan Matematika'), ('Ekonomi dan Bisnis'), ('Teknik'), ('Hukum'), 
('Bahasa dan Seni'), ('Ilmu Pendidikan'), ('Keolahragaan'), ('Ilmu Sosial');

-- 2. PRODI
INSERT INTO Prodi (Nama_Prodi, ID_Fakultas) VALUES 
('Teknik Informatika', 1), ('Sistem Informasi', 1), ('Ilmu Komputer', 1), ('DKV', 5),
('Manajemen', 2), ('Akuntansi', 2), ('Ekonomi Pembangunan', 2),
('Teknik Sipil', 3), ('Teknik Mesin', 3), ('Teknik Elektro', 3),
('Ilmu Hukum', 4), ('Pendidikan Guru SD', 6);

-- 3. KATEGORI LOMBA
INSERT INTO Kategori_Lomba (Nama_Kategori) VALUES 
('Capture The Flag (CTF)'), ('UI/UX Design'), ('Business Plan'), ('Competitive Programming'), 
('Data Mining'), ('Poster Ilmiah'), ('Video Kreatif'), ('Debat Bahasa Inggris'), ('Karya Tulis Ilmiah'), ('Robotik');

-- 4. JENIS PENYELENGGARA
INSERT INTO Jenis_Penyelenggara (Nama_Jenis, Bobot_Poin) VALUES 
('Universitas', 1.0), ('Pemerintah (Kemdikbud/Puspresnas)', 2.0), ('Perusahaan Multinasional', 1.5), 
('Komunitas Nasional', 1.2), ('Organisasi Internasional', 2.5);

-- 5. TINGKATAN LOMBA
INSERT INTO Tingkatan_Lomba (Nama_Tingkatan, Poin_Dasar) VALUES 
('Universitas', 50), ('Regional/Provinsi', 75), ('Nasional', 150), ('Internasional', 300);

-- 6. PERINGKAT JUARA
INSERT INTO Peringkat_Juara (Nama_Peringkat, Multiplier_Poin) VALUES 
('Juara 1', 1.0), ('Juara 2', 0.8), ('Juara 3', 0.6), 
('Juara Harapan 1', 0.4), ('Juara Harapan 2', 0.3), ('Finalis', 0.2), ('Peserta', 0.1);

-- 7. SKILL (TOOLS)
INSERT INTO Skill (Nama_Skill) VALUES 
('Python'), ('Figma'), ('VS Code'), ('ReactJS'), ('Laravel'), ('MySQL'), 
('Canva'), ('Trello'), ('Flutter'), ('Adobe Illustrator'), ('SPSS'), ('Public Speaking');

-- 8. ROLE (PROFESI TIM)
INSERT INTO Role_Tim (Nama_Role) VALUES 
('Frontend Developer'), ('Backend Developer'), ('UI/UX Designer'), ('Data Scientist'), 
('Project Manager'), ('Pitch Deck Maker'), ('Researcher'), ('Video Editor'), ('Hardware Engineer'),
('Public Speaker');

-- 9. ADMIN
INSERT INTO Admin (Username, Password_Hash, Nama_Lengkap, Level) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator', 'superadmin'),
('staff_kemahasiswaan', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Staff Kemahasiswaan', 'admin');

-- 10. DOSEN
INSERT INTO Dosen_Pembimbing (NIDN, Nama_Dosen, Email, Password_Hash, Bio, ID_Prodi, Is_Verified, Is_Admin, Tempat_Lahir, Tanggal_Lahir, No_HP) VALUES
('0601017501', 'Dr. Santoso, M.Kom', 'santoso@mail.unnes.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Expert AI & Data Mining.', 1, 1, 1, 'Semarang', '1975-01-01', '081234567890'),
('0601017502', 'Prof. Siti Aminah, S.H.', 'siti.aminah@mail.unnes.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hukum Perdata dan Bisnis.', 4, 1, 0, 'Solo', '1970-05-20', '081234567891'),
('0601017503', 'Budi Santoso, S.E., M.Si.', 'budi.se@mail.unnes.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manajemen Keuangan & Startups.', 5, 1, 0, 'Yogyakarta', '1980-08-17', '081234567892'),
('0601017504', 'Rina Wati, M.Cs.', 'rina@mail.unnes.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mobile Development & IoT.', 1, 1, 0, 'Bandung', '1985-12-12', '081234567893'),
('0601017505', 'Dr. Eng. Agus, S.T.', 'agus.tek@mail.unnes.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Teknik Sipil & Konstruksi.', 8, 1, 0, 'Surabaya', '1978-03-30', '081234567894');

-- 11. MAHASISWA (10 Data)
INSERT INTO Mahasiswa (NIM, Nama_Mahasiswa, Email, Password_Hash, Total_Poin, ID_Prodi, Bio, Is_Verified, Tempat_Lahir, Tanggal_Lahir) VALUES 
('A11.2023.001', 'Andi Saputra', 'andi@students.unnes.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 150, 1, 'Fullstack Dev enthusiast.', 1, 'Semarang', '2003-01-01'),
('A11.2023.002', 'Bunga Citra', 'bunga@students.unnes.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 50, 2, 'Suka desain UI/UX dan art.', 1, 'Jepara', '2003-02-14'),
('E11.2023.003', 'Chandra Wijaya', 'chandra@students.unnes.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 5, 'Business enthusiast, looking for tech team.', 1, 'Kudus', '2002-05-20'),
('H11.2023.004', 'Dewi Sartika', 'dewi@students.unnes.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 300, 11, 'Debater and Legal Drafter.', 1, 'Jakarta', '2003-11-10'),
('A11.2023.005', 'Eko Prasetyo', 'eko@students.unnes.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 1, 'Cyber Security noob.', 1, 'Tegal', '2004-01-01'),
('A11.2023.006', 'Fajar Nugraha', 'fajar@students.unnes.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 100, 1, 'Backend specialist (Laravel/Go).', 1, 'Brebes', '2002-08-17'),
('B11.2023.007', 'Gita Gutawa', 'gita@students.unnes.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 6, 'Akuntansi dan Finance.', 1, 'Magelang', '2003-04-21'),
('C11.2023.008', 'Hadi Sucipto', 'hadi@students.unnes.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 20, 9, 'Anak Teknik Mesin suka robot.', 1, 'Solo', '2002-12-12'),
('A11.2023.009', 'Indah Permata', 'indah@students.unnes.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 4, 'DKV - Illustrator handal.', 1, 'Semarang', '2003-09-09'),
('A11.2023.010', 'Joko Anwar', 'joko@students.unnes.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 1, 'Mobile Dev Flutter.', 1, 'Pati', '2003-07-07');

-- 12. PIVOT SKILL & ROLE (Sampling)
INSERT INTO Mahasiswa_Skill (ID_Mahasiswa, ID_Skill) VALUES 
(1, 1), (1, 4), (1, 5), -- Andi: Python, React, Laravel
(2, 2), (2, 7), (2, 10), -- Bunga: Figma, Canva, Illustrator
(5, 1), (5, 6), -- Eko: Python, MySQL
(6, 5), (6, 6), -- Fajar: Laravel, MySQL
(9, 10), (9, 2), -- Indah: Illustrator, Figma
(10, 9); -- Joko: Flutter

INSERT INTO Mahasiswa_Role (ID_Mahasiswa, ID_Role) VALUES
(1, 1), (1, 2), -- Andi: Frontend/Backend
(2, 3), -- Bunga: UI/UX
(3, 5), (3, 6), -- Chandra: PM, Pitch Deck
(4, 7),          -- Hanya masukkan ID 7 (Researcher)
(6, 2), -- Fajar: Backend
(9, 3); -- Indah: UI/UX

-- 13. LOMBA (Kompetisi)
INSERT INTO Lomba (Nama_Lomba, Deskripsi, Lokasi, Link_Web, Tanggal_Mulai, Tanggal_Selesai, ID_Jenis_Penyelenggara, ID_Tingkatan) VALUES 
('Gemastik 2025', 'Pagelaran Mahasiswa Nasional bidang TIK.', 'Universitas Brawijaya', 'https://gemastik.kemdikbud.go.id', '2025-06-01', '2025-10-01', 2, 3),
('PKM-KC 2025', 'Program Kreativitas Mahasiswa Karsa Cipta.', 'Daring', 'https://simbelmawa.kemdikbud.go.id', '2025-03-01', '2025-08-01', 2, 3),
('Lomba UI/UX Himsisfo', 'Lomba desain aplikasi mobile tingkat nasional.', 'Semarang', 'https://himsisfo.unnes.ac.id', '2025-01-15', '2025-02-28', 1, 1),
('Business Plan Competition 2025', 'Lomba ide bisnis kreatif mahasiswa.', 'Jakarta', 'https://bpc.id', '2025-05-10', '2025-06-10', 3, 3),
('Compfest 16', 'IT Competition terbesar by UI.', 'Depok', 'https://compfest.id', '2025-07-01', '2025-09-01', 1, 3),
('International Robot Contest', 'Kontes robot internasional di Jepang.', 'Tokyo', 'https://iro.org', '2025-11-01', '2025-11-05', 5, 4),
('Lomba Debat Konstitusi', 'Lomba debat hukum antar universitas.', 'Mahkamah Konstitusi', 'https://mkri.go.id', '2025-04-01', '2025-04-05', 2, 3);

-- 14. PIVOT LOMBA - KATEGORI
INSERT INTO Lomba_Kategori (ID_Lomba, ID_Kategori) VALUES
(1, 1), (1, 2), (1, 4), (1, 5), -- Gemastik (Multi kategori)
(2, 9), (2, 10), -- PKM
(3, 2), -- Himsisfo (UI/UX)
(4, 3), -- BPC (Bisnis)
(5, 1), (5, 4), -- Compfest
(6, 10), -- Robot
(7, 8); -- Debat

-- 15. TIM
INSERT INTO Tim (Nama_Tim, Status_Pencarian, ID_Lomba, ID_Kategori, ID_Mahasiswa_Ketua, Deskripsi_Tim, Kebutuhan_Role) VALUES 
('Garuda Code', 'Terbuka', 1, 4, 1, 'Mencari frontend dev yang jago ReactJS untuk Gemastik.', 'Frontend Developer, UI/UX Designer'),
('Creative Minds', 'Terbuka', 3, 2, 2, 'Tim fokus desain inovatif, butuh riset user.', 'Researcher'),
('Bisnis Berkah', 'Tertutup', 4, 3, 3, 'Tim sudah full, target juara 1.', NULL),
('RoboSquad', 'Terbuka', 6, 10, 8, 'Butuh programmer IoT/Arduino.', 'Backend Developer, Hardware Engineer'),
('Legal Eagles', 'Terbuka', 7, 8, 4, 'Mencari partner debat yang kritis.', 'Public Speaker, Researcher');

-- 16. KEANGGOTAAN TIM
INSERT INTO Keanggotaan_Tim (ID_Tim, ID_Mahasiswa, Peran, Status) VALUES 
(1, 1, 'Ketua & Backend', 'Diterima'), -- Andi di Garuda Code
(1, 6, 'Backend Dev', 'Diterima'), -- Fajar di Garuda Code
(2, 2, 'Ketua & Designer', 'Diterima'), -- Bunga di Creative Minds
(2, 9, 'Illustrator', 'Diterima'), -- Indah di Creative Minds
(3, 3, 'Ketua & Hustler', 'Diterima'), -- Chandra di Bisnis Berkah
(3, 7, 'Hipster', 'Diterima'), -- Gita di Bisnis Berkah
(4, 8, 'Ketua & Mekanik', 'Diterima'), -- Hadi di RoboSquad
(5, 4, 'Ketua & Speaker 1', 'Diterima'); -- Dewi di Legal Eagles

-- 17. INVITASI (Testing Fitur Baru)
-- Skenario 1: Andi (Ketua Garuda Code) mengundang Joko (Mobile Dev) -> Pending
INSERT INTO Invitasi (ID_Tim, ID_Pengirim, ID_Penerima, Tipe_Penerima, Status) VALUES 
(1, 1, 10, 'mahasiswa', 'Pending');

-- Skenario 2: Bunga (Ketua Creative Minds) mengundang Eko -> Pending
INSERT INTO Invitasi (ID_Tim, ID_Pengirim, ID_Penerima, Tipe_Penerima, Status) VALUES 
(2, 2, 5, 'mahasiswa', 'Pending');

-- Skenario 3: Hadi (RoboSquad) mengundang Dosen Dr. Santoso -> Pending
INSERT INTO Invitasi (ID_Tim, ID_Pengirim, ID_Penerima, Tipe_Penerima, Status) VALUES 
(4, 8, 1, 'dosen', 'Pending');