-- BERSIHKAN TABEL LAMA
DROP TABLE IF EXISTS Keanggotaan_Tim;
DROP TABLE IF EXISTS Tim;
DROP TABLE IF EXISTS Lomba;
DROP TABLE IF EXISTS Mahasiswa_Keahlian;
DROP TABLE IF EXISTS Dosen_Keahlian;
DROP TABLE IF EXISTS Mahasiswa;
DROP TABLE IF EXISTS Dosen_Pembimbing;
DROP TABLE IF EXISTS Prodi;
DROP TABLE IF EXISTS Admin;
DROP TABLE IF EXISTS Kategori_Lomba;
DROP TABLE IF EXISTS Jenis_Penyelenggara;
DROP TABLE IF EXISTS Tingkatan_Lomba;
DROP TABLE IF EXISTS Peringkat_Juara; -- Baru
DROP TABLE IF EXISTS Keahlian;

-- ================= MASTER DATA =================
CREATE TABLE Prodi (
    ID_Prodi INT AUTO_INCREMENT PRIMARY KEY,
    Nama_Prodi VARCHAR(100) NOT NULL,
    Fakultas VARCHAR(100) NOT NULL
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

-- TABEL BARU: Faktor Peringkat Juara
CREATE TABLE Peringkat_Juara (
    ID_Peringkat INT AUTO_INCREMENT PRIMARY KEY,
    Nama_Peringkat VARCHAR(50) NOT NULL, -- Juara 1, 2, 3, Finalis, Peserta
    Multiplier_Poin FLOAT DEFAULT 0.0 -- 1.0 (100%), 0.5 (50%), dll
);

CREATE TABLE Keahlian (
    ID_Keahlian INT AUTO_INCREMENT PRIMARY KEY,
    Nama_Keahlian VARCHAR(100) NOT NULL
);

-- ================= USER DATA =================
CREATE TABLE Mahasiswa (
    ID_Mahasiswa INT AUTO_INCREMENT PRIMARY KEY,
    NIM VARCHAR(20) UNIQUE NOT NULL,
    Nama_Mahasiswa VARCHAR(150) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Password_Hash VARCHAR(255) NOT NULL,
    Tempat_Lahir VARCHAR(50),
    Tanggal_Lahir DATE,
    Bio TEXT,
    Total_Poin FLOAT DEFAULT 0, -- Ubah ke FLOAT untuk presisi
    ID_Prodi INT,
    FOREIGN KEY (ID_Prodi) REFERENCES Prodi(ID_Prodi) ON DELETE SET NULL
);

CREATE TABLE Dosen_Pembimbing (
    ID_Dosen INT AUTO_INCREMENT PRIMARY KEY,
    NIDN VARCHAR(20) UNIQUE NOT NULL,
    Nama_Dosen VARCHAR(150) NOT NULL,
    Email VARCHAR(100),
    Bio TEXT,
    ID_Prodi INT,
    FOREIGN KEY (ID_Prodi) REFERENCES Prodi(ID_Prodi) ON DELETE SET NULL
);

CREATE TABLE Admin (
    ID_Admin INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) UNIQUE NOT NULL,
    Password_Hash VARCHAR(255) NOT NULL,
    Nama_Lengkap VARCHAR(100)
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
    ID_Peringkat INT, -- Kolom Baru: Menyimpan hasil lomba tim ini
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

-- ================= SEEDING =================
INSERT INTO Admin (Username, Password_Hash, Nama_Lengkap) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator');

INSERT INTO Prodi (Nama_Prodi, Fakultas) VALUES ('TI', 'Ilmu Komputer'), ('DKV', 'Ilmu Komputer'), ('Manajemen', 'Ekonomi');
INSERT INTO Kategori_Lomba (Nama_Kategori) VALUES ('Capture The Flag'), ('UI/UX Design'), ('Business Plan'), ('Competitive Programming');
INSERT INTO Jenis_Penyelenggara (Nama_Jenis, Bobot_Poin) VALUES ('Universitas', 1.0), ('Pemerintah', 1.5), ('Komunitas', 1.2);
INSERT INTO Tingkatan_Lomba (Nama_Tingkatan, Poin_Dasar) VALUES ('Nasional', 100), ('Internasional', 200), ('Regional', 50);

-- SEEDING PERINGKAT JUARA (Logika Multiplier)
INSERT INTO Peringkat_Juara (Nama_Peringkat, Multiplier_Poin) VALUES 
('Juara 1', 1.0),       -- Dapat 100% Poin
('Juara 2', 0.75),      -- Dapat 75% Poin
('Juara 3', 0.50),      -- Dapat 50% Poin
('Harapan/Favorite', 0.25), -- Dapat 25% Poin
('Finalis', 0.10),      -- Dapat 10% Poin
('Peserta', 0.0);       -- Tidak dapat poin

INSERT INTO Keahlian (Nama_Keahlian) VALUES ('Python'), ('Figma'), ('Public Speaking'), ('ReactJS'), ('Cyber Security'), ('Data Analysis');

INSERT INTO Mahasiswa (NIM, Nama_Mahasiswa, Email, Password_Hash, Total_Poin, ID_Prodi, Bio) VALUES 
('A11.2023.001', 'Budi Hacker', 'budi@mhs.dinus.ac.id', 'hash', 0, 1, 'Saya suka keamanan jaringan dan CTF.'),
('A11.2023.002', 'Siti Desainer', 'siti@mhs.dinus.ac.id', 'hash', 0, 2, 'UI/UX Enthusiast. Figma expert.');

INSERT INTO Lomba (Nama_Lomba, Deskripsi, Tanggal_Mulai, Tanggal_Selesai, ID_Kategori, ID_Jenis_Penyelenggara, ID_Tingkatan) VALUES 
('Gemastik 2025', 'Lomba TIK Nasional terbesar.', CURDATE() + INTERVAL 10 DAY, CURDATE() + INTERVAL 20 DAY, 1, 2, 1);