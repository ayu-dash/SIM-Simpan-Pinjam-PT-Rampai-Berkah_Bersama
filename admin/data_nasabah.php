<?php
require_once '../config/database.php';
require_once '../includes/header.php';
cekLoginAdmin();

$pesan = "";
$tipe_pesan = "";

// --- LOGIKA UPDATE DATA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $id_nasabah = $_POST['id_nasabah'];
    $nama       = $_POST['nama_lengkap'];
    $jabatan    = $_POST['id_jabatan'];
    $username   = $_POST['username'];
    $status     = $_POST['status'];
    $password   = $_POST['password']; 

    try {
        if (!empty($password)) {
            $sql = "UPDATE nasabah SET id_jabatan=?, nama_lengkap=?, username=?, status=?, hashed_password=? WHERE id_nasabah=?";
            $params = [$jabatan, $nama, $username, $status, $password, $id_nasabah];
        } else {
            $sql = "UPDATE nasabah SET id_jabatan=?, nama_lengkap=?, username=?, status=? WHERE id_nasabah=?";
            $params = [$jabatan, $nama, $username, $status, $id_nasabah];
        }
        $pdo->prepare($sql)->execute($params);
        
        $pesan = "Data nasabah berhasil diperbarui!";
        $tipe_pesan = "success";
    } catch (Exception $e) {
        $pesan = "Gagal Update: " . $e->getMessage();
        $tipe_pesan = "error";
    }
}

// --- LOGIKA HAPUS DATA (CASCADE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id_nasabah = $_POST['id_nasabah'];

    try {
        $pdo->beginTransaction();

        // 1. Hapus Data Simpanan
        $pdo->prepare("DELETE FROM simpanan WHERE id_nasabah = ?")->execute([$id_nasabah]);

        // 2. Ambil ID Pinjaman terkait
        $stmt_pinjaman = $pdo->prepare("SELECT id_pinjaman FROM pinjaman WHERE id_nasabah = ?");
        $stmt_pinjaman->execute([$id_nasabah]);
        $pinjaman_ids = $stmt_pinjaman->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($pinjaman_ids)) {
            // Buat placeholder untuk IN clause
            $placeholders = implode(',', array_fill(0, count($pinjaman_ids), '?'));
            
            // 3. Hapus Angsuran & Status Pinjaman
            $pdo->prepare("DELETE FROM angsuran WHERE id_pinjaman IN ($placeholders)")->execute($pinjaman_ids);
            $pdo->prepare("DELETE FROM status_pinjaman WHERE id_pinjaman IN ($placeholders)")->execute($pinjaman_ids);
            
            // 4. Hapus Pinjaman
            $pdo->prepare("DELETE FROM pinjaman WHERE id_nasabah = ?")->execute([$id_nasabah]);
        }

        // 5. Hapus Nasabah
        $pdo->prepare("DELETE FROM nasabah WHERE id_nasabah = ?")->execute([$id_nasabah]);
        
        $pdo->commit();
        
        $pesan = "Data nasabah dan seluruh riwayat transaksi berhasil dihapus!";
        $tipe_pesan = "success";
    } catch (Exception $e) {
        $pdo->rollBack();
        $pesan = "Gagal Hapus: " . $e->getMessage();
        $tipe_pesan = "error";
    }
}

// --- AMBIL DATA ---
$data_nasabah = $pdo->query("SELECT nasabah.*, jabatan.nama_jabatan FROM nasabah LEFT JOIN jabatan ON nasabah.id_jabatan = jabatan.id_jabatan ORDER BY nasabah.id_nasabah ASC")->fetchAll();
$list_jabatan = $pdo->query("SELECT * FROM jabatan")->fetchAll();
?>

<?php if (!empty($pesan)): ?>
    <div style="padding:15px; margin-bottom:20px; border-radius:5px; text-align:center; font-weight:bold; 
         background-color: <?= $tipe_pesan == 'success' ? '#d4edda' : '#f8d7da' ?>; 
         color: <?= $tipe_pesan == 'success' ? '#155724' : '#721c24' ?>;">
        <?= $pesan; ?>
    </div>
<?php endif; ?>

<div class="dashboard-content">
    
    <div class="dashboard-welcome">
        <h2>👥 Data Nasabah</h2>
        <span>Kelola data nasabah, edit informasi, atau reset password</span>
    </div>

    <div class="widget-box">
        <div class="widget-header">
            <span>Daftar Nasabah Terdaftar</span>
            <button onclick="window.print()" class="btn-admin-action" style="background:#17a2b8; padding: 8px 15px; font-size: 12px;">🖨️ Cetak</button>
        </div>
        
        <div class="widget-body">
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Jabatan</th>
                            <th>Nama Lengkap</th>
                            <th>Username</th>
                            <th>Status</th>
                            <th style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data_nasabah as $row): ?>
                        <tr>
                            <td><?= $row['id_nasabah']; ?></td>
                            <td><?= htmlspecialchars($row['nama_jabatan']); ?></td>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar-small"><?= strtoupper(substr($row['nama_lengkap'],0,2)) ?></div>
                                    <div class="font-bold"><?= htmlspecialchars($row['nama_lengkap']); ?></div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($row['username']); ?></td>
                            <td>
                                <span class="status-badge <?= $row['status']=='AKTIF'?'badge-success':'badge-danger' ?>">
                                    <?= $row['status']; ?>
                                </span>
                            </td>
                            <td style="text-align:center;">
                                <div style="display:flex; justify-content:center; gap:5px;">
                                    <button type="button" class="btn-edit"
                                        data-id="<?= $row['id_nasabah']; ?>"
                                        data-nama="<?= htmlspecialchars($row['nama_lengkap']); ?>"
                                        data-user="<?= htmlspecialchars($row['username']); ?>"
                                        data-jabatan="<?= $row['id_jabatan']; ?>"
                                        data-status="<?= $row['status']; ?>"
                                        onclick="openEditNasabah(this)">
                                        ✏️
                                    </button>
                                    
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id_nasabah" value="<?= $row['id_nasabah']; ?>">
                                        <button type="submit" class="btn-delete" 
                                            style="background:#dc3545; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer;"
                                            onclick="return confirm('Apakah Anda yakin ingin menghapus nasabah \'<?= htmlspecialchars($row['nama_lengkap']); ?>\'? Data yang dihapus tidak dapat dikembalikan.')">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="editModal" class="modal-overlay">
    <div class="modal-box">
        
        <div class="modal-header">
            <span>✏️ Edit Data Nasabah</span>
            <span class="close-btn" onclick="closeModal('editModal')">&times;</span>
        </div>
        
        <form method="POST" action="">
            <div class="modal-body">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id_nasabah" id="modal_id">

                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="modal_nama" required>
                </div>

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" id="modal_user" required>
                </div>

                <div class="form-group">
                    <label>Jabatan</label>
                    <select name="id_jabatan" id="modal_jabatan">
                        <?php foreach($list_jabatan as $jab): ?>
                            <option value="<?= $jab['id_jabatan']; ?>"><?= $jab['nama_jabatan']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status Akun</label>
                    <select name="status" id="modal_status">
                        <option value="AKTIF">AKTIF</option>
                        <option value="SUSPEND">SUSPEND</option>
                    </select>
                </div>

                <div class="form-group" style="border-top:1px dashed #ccc; padding-top:10px; margin-top:15px;">
                    <label>Password Baru</label>
                    <input type="password" name="password" id="modal_pass" placeholder="Ketik password baru...">
                    <span class="form-note">⚠️ Biarkan kosong jika tidak ingin mengganti password.</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Batal</button>
                <button type="submit" class="btn-save">Simpan Perubahan</button>
            </div>
        </form>
    </div> 
</div>

<?php require_once '../includes/footer.php'; ?>