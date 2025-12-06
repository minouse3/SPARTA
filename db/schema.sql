-- NONAKTIFKAN FOREIGN KEY CHECKS AGAR BISA DROP TABEL DENGAN AMAN
SET FOREIGN_KEY_CHECKS = 0;

-- BERSIHKAN TABEL LAMA (RESET TOTAL)
DROP TABLE IF EXISTS Keanggotaan_Tim;
DROP TABLE IF EXISTS Tim;
DROP TABLE IF EXISTS Lomba;
DROP TABLE IF EXISTS Mahasiswa_Keahlian;
DROP TABLE IF EXISTS Dosen_Keahlian;
DROP TABLE IF EXISTS Mahasiswa;
DROP TABLE IF EXISTS Dosen_Pembimbing;
DROP TABLE IF EXISTS Prodi;
DROP TABLE IF EXISTS Fakultas;
DROP TABLE IF EXISTS Admin;
DROP TABLE IF EXISTS Kategori_Lomba;
DROP TABLE IF EXISTS Jenis_Penyelenggara;
DROP TABLE IF EXISTS Tingkatan_Lomba;
DROP TABLE IF EXISTS Peringkat_Juara;
DROP TABLE IF EXISTS Keahlian;

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

CREATE TABLE Keahlian (
    ID_Keahlian INT AUTO_INCREMENT PRIMARY KEY,
    Nama_Keahlian VARCHAR(100) NOT NULL
);

-- ================= USER DATA =================

-- 1. ADMIN (Dengan Level & Foto)
CREATE TABLE Admin (
    ID_Admin INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) UNIQUE NOT NULL,
    Password_Hash VARCHAR(255) NOT NULL,
    Nama_Lengkap VARCHAR(100),
    Level ENUM('superadmin', 'admin') NOT NULL DEFAULT 'admin',
    Foto_Profil VARCHAR(255) DEFAULT NULL -- Fitur Foto Profil
);

-- 2. DOSEN (Dengan Password & Foto)
CREATE TABLE Dosen_Pembimbing (
    ID_Dosen INT AUTO_INCREMENT PRIMARY KEY,
    NIDN VARCHAR(20) UNIQUE NOT NULL,
    Nama_Dosen VARCHAR(150) NOT NULL,
    Email VARCHAR(100) UNIQUE,
    Bio TEXT,
    ID_Prodi INT,
    Password_Hash VARCHAR(255) NOT NULL,
    Foto_Profil VARCHAR(255) DEFAULT NULL, -- Fitur Foto Profil
    FOREIGN KEY (ID_Prodi) REFERENCES Prodi(ID_Prodi) ON DELETE SET NULL
);

-- 3. MAHASISWA (Dengan Foto)
CREATE TABLE Mahasiswa (
    ID_Mahasiswa INT AUTO_INCREMENT PRIMARY KEY,
    NIM VARCHAR(20) UNIQUE NOT NULL,
    Nama_Mahasiswa VARCHAR(150) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Password_Hash VARCHAR(255) NOT NULL,
    Tempat_Lahir VARCHAR(50),
    Tanggal_Lahir DATE,
    Bio TEXT,
    Total_Poin FLOAT DEFAULT 0,
    ID_Prodi INT,
    Foto_Profil VARCHAR(255) DEFAULT NULL, -- Fitur Foto Profil
    FOREIGN KEY (ID_Prodi) REFERENCES Prodi(ID_Prodi) ON DELETE SET NULL
);

CREATE TABLE Mahasiswa_Keahlian (
    ID_Mhs_Keahlian INT AUTO_INCREMENT PRIMARY KEY,
    ID_Mahasiswa INT,
    ID_Keahlian INT,
    FOREIGN KEY (ID_Mahasiswa) REFERENCES Mahasiswa(ID_Mahasiswa) ON DELETE CASCADE,
    FOREIGN KEY (ID_Keahlian) REFERENCES Keahlian(ID_Keahlian) ON DELETE CASCADE
);

CREATE TABLE Dosen_Keahlian (
    ID_Dsn_Keahlian INT AUTO_INCREMENT PRIMARY KEY,
    ID_Dosen INT,
    ID_Keahlian INT,
    FOREIGN KEY (ID_Dosen) REFERENCES Dosen_Pembimbing(ID_Dosen) ON DELETE CASCADE,
    FOREIGN KEY (ID_Keahlian) REFERENCES Keahlian(ID_Keahlian) ON DELETE CASCADE
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
    ID_Kategori INT,
    ID_Jenis_Penyelenggara INT,
    ID_Tingkatan INT,
    FOREIGN KEY (ID_Kategori) REFERENCES Kategori_Lomba(ID_Kategori),
    FOREIGN KEY (ID_Jenis_Penyelenggara) REFERENCES Jenis_Penyelenggara(ID_Jenis),
    FOREIGN KEY (ID_Tingkatan) REFERENCES Tingkatan_Lomba(ID_Tingkatan)
);

CREATE TABLE Tim (
    ID_Tim INT AUTO_INCREMENT PRIMARY KEY,
    Nama_Tim VARCHAR(100) NOT NULL,
    Status_Pencarian ENUM('Terbuka', 'Tertutup') DEFAULT 'Terbuka',
    Deskripsi_Tim TEXT,
    ID_Lomba INT NOT NULL,
    ID_Mahasiswa_Ketua INT NOT NULL,
    ID_Dosen_Pembimbing INT,
    ID_Peringkat INT,
    FOREIGN KEY (ID_Lomba) REFERENCES Lomba(ID_Lomba) ON DELETE CASCADE,
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

-- ================= SEEDING (DATA AWAL) =================

-- 1. Super Admin (Username: superadmin, Pass: password)
INSERT INTO Admin (Username, Password_Hash, Nama_Lengkap, Level) VALUES 
('superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator', 'superadmin');

-- 2. Fakultas & Prodi
INSERT INTO Fakultas (Nama_Fakultas) VALUES ('Ilmu Komputer'), ('Ekonomi');
INSERT INTO Prodi (Nama_Prodi, ID_Fakultas) VALUES ('TI', 1), ('DKV', 1), ('Manajemen', 2);

-- 3. Master Data Lomba
INSERT INTO Kategori_Lomba (Nama_Kategori) VALUES ('Capture The Flag'), ('UI/UX Design'), ('Business Plan'), ('Competitive Programming');
INSERT INTO Jenis_Penyelenggara (Nama_Jenis, Bobot_Poin) VALUES ('Universitas', 1.0), ('Pemerintah', 1.5), ('Komunitas', 1.2);
INSERT INTO Tingkatan_Lomba (Nama_Tingkatan, Poin_Dasar) VALUES ('Nasional', 100), ('Internasional', 200), ('Regional', 50);

-- 4. Peringkat Juara
INSERT INTO Peringkat_Juara (Nama_Peringkat, Multiplier_Poin) VALUES 
('Juara 1', 1.0), ('Juara 2', 0.75), ('Juara 3', 0.50), ('Harapan/Favorite', 0.25), ('Finalis', 0.10), ('Peserta', 0.0);

-- 5. Keahlian
INSERT INTO Keahlian (Nama_Keahlian) VALUES ('Python'), ('Figma'), ('Public Speaking'), ('ReactJS'), ('Cyber Security'), ('Data Analysis');

-- 6. Dosen (Email UNNES, Pass: password)
INSERT INTO Dosen_Pembimbing (NIDN, Nama_Dosen, Email, Password_Hash, Bio, ID_Prodi) VALUES
('0601017501', 'Dr. Santoso, M.Kom', 'santoso@mail.unnes.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Expert in AI', 1);

-- 7. Mahasiswa (Email UNNES, Pass: password)
INSERT INTO Mahasiswa (NIM, Nama_Mahasiswa, Email, Password_Hash, Total_Poin, ID_Prodi, Bio) VALUES 
('A11.2023.001', 'Budi Hacker', 'budi@students.unnes.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 1, 'Cyber Security Enthusiast'),
('A11.2023.002', 'Siti Desainer', 'siti@students.unnes.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 2, 'UI/UX Expert');

-- 8. Lomba Contoh
INSERT INTO Lomba (Nama_Lomba, Deskripsi, Tanggal_Mulai, Tanggal_Selesai, ID_Kategori, ID_Jenis_Penyelenggara, ID_Tingkatan) VALUES 
('Gemastik 2025', 'Lomba TIK Nasional terbesar.', CURDATE() + INTERVAL 10 DAY, CURDATE() + INTERVAL 20 DAY, 1, 2, 1);