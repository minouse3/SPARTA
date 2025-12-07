<?php
// FILE: Halaman Cari Dosen (Public - Premium UI)

// Load Data Master untuk Filter
$fakultasList = $pdo->query("SELECT * FROM Fakultas ORDER BY Nama_Fakultas ASC")->fetchAll();
$prodiList = $pdo->query("SELECT * FROM Prodi ORDER BY Nama_Prodi ASC")->fetchAll();
$skillList = $pdo->query("SELECT * FROM Skill ORDER BY Nama_Skill ASC")->fetchAll();
$roleList = $pdo->query("SELECT * FROM Role_Tim ORDER BY Nama_Role ASC")->fetchAll();
?>

<style>
    /* Card Container */
    .dosen-card-wrapper {
        border: none;
        border-radius: 20px; /* Sudut lebih bulat */
        background: #fff;
        overflow: hidden;
        height: 100%;
        box-shadow: 0 10px 30px rgba(0,0,0,0.03); /* Bayangan sangat halus */
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        position: relative;
        display: flex;
        flex-direction: column;
    }
    
    .dosen-card-wrapper:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.08);
    }

    /* Banner Gradient */
    .card-banner-top {
        height: 90px;
        background: linear-gradient(45deg, #4facfe 0%, #00f2fe 100%); /* Fresh Blue Gradient */
        position: relative;
    }
    
    /* Avatar Styling */
    .dosen-avatar-box {
        margin-top: -50px;
        margin-bottom: 10px;
        position: relative;
        display: flex;
        justify-content: center;
    }
    
    .dosen-avatar-img, .dosen-initial {
        width: 100px; height: 100px;
        border-radius: 50%;
        border: 5px solid #fff;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        object-fit: cover;
        background: #fff;
    }
    
    .dosen-initial {
        display: flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #f6f9fc, #eef2f7);
        color: #555;
        font-weight: 800;
        font-size: 2.2rem;
    }

    /* Content Area */
    .info-box {
        background-color: #f8f9fa;
        border-radius: 12px;
        padding: 15px;
        margin: 15px 0;
    }

    .tag-scroll-area {
        max-height: 80px;
        overflow-y: auto;
        /* Hide scrollbar for Chrome/Safari/Opera */
        -ms-overflow-style: none;  /* IE and Edge */
        scrollbar-width: none;  /* Firefox */
    }
    .tag-scroll-area::-webkit-scrollbar { display: none; }

    /* Custom Badges */
    .badge-skill { background-color: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
    .badge-role { background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2); }

    /* Filter Card */
    .filter-card {
        background: white; border-radius: 16px; border: 1px solid #f0f0f0;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark" style="font-family: 'Roboto Slab', serif;">Cari Dosen</h3>
        <p class="text-muted mb-0">Temukan mentor akademik yang tepat untuk tim Anda.</p>
    </div>
</div>

<div class="filter-card p-4 mb-5">
    <form id="filterDosen" class="row g-3">
        <div class="col-md-4">
            <label class="small fw-bold text-muted mb-1">Pencarian</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                <input type="text" id="qDosen" class="form-control border-start-0 ps-0" placeholder="Nama atau NIDN...">
            </div>
        </div>
        
        <div class="col-md-2">
            <label class="small fw-bold text-muted mb-1">Fakultas</label>
            <select id="fakDosen" class="form-select">
                <option value="">Semua</option>
                <?php foreach($fakultasList as $f): ?>
                    <option value="<?= $f['ID_Fakultas'] ?>"><?= htmlspecialchars($f['Nama_Fakultas']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="small fw-bold text-muted mb-1">Prodi</label>
            <select id="prodiDosen" class="form-select">
                <option value="">Semua</option>
                <?php foreach($prodiList as $p): ?>
                    <option value="<?= $p['ID_Prodi'] ?>"><?= htmlspecialchars($p['Nama_Prodi']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="small fw-bold text-muted mb-1">Skill</label>
            <select id="skillDosen" class="form-select">
                <option value="">Semua</option>
                <?php foreach($skillList as $s): ?>
                    <option value="<?= $s['ID_Skill'] ?>"><?= htmlspecialchars($s['Nama_Skill']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="small fw-bold text-muted mb-1">Role</label>
            <select id="roleDosen" class="form-select">
                <option value="">Semua</option>
                <?php foreach($roleList as $r): ?>
                    <option value="<?= $r['ID_Role'] ?>"><?= htmlspecialchars($r['Nama_Role']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<div class="row g-4 pb-5" id="dosenContainer">
    <div class="col-12 py-5 text-center text-muted">
        <span class="spinner-border spinner-border-sm text-primary mb-2" role="status"></span>
        <p>Memuat data dosen...</p>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    loadDosenPublic();
    const inputs = document.querySelectorAll('#filterDosen input, #filterDosen select');
    inputs.forEach(input => { 
        input.addEventListener('input', debounce(loadDosenPublic, 300)); 
    });
});

function loadDosenPublic() {
    const q = document.getElementById('qDosen').value;
    const fak = document.getElementById('fakDosen').value;
    const prodi = document.getElementById('prodiDosen').value;
    const skill = document.getElementById('skillDosen').value;
    const role = document.getElementById('roleDosen').value;
    
    const container = document.getElementById('dosenContainer');
    container.style.opacity = '0.6';

    const url = `fetch_data.php?page=cari_dosen&q=${encodeURIComponent(q)}&fakultas=${fak}&prodi=${prodi}&skill=${skill}&role=${role}`;

    fetch(url)
        .then(response => response.text())
        .then(html => { 
            container.innerHTML = html; 
            container.style.opacity = '1';
        })
        .catch(err => {
            console.error(err);
            container.innerHTML = '<div class="col-12 text-center text-danger py-5">Gagal memuat data.</div>';
            container.style.opacity = '1';
        });
}

function debounce(func, wait) { 
    let timeout; 
    return function(...args) { 
        clearTimeout(timeout); 
        timeout = setTimeout(() => func(...args), wait); 
    }; 
}
</script>