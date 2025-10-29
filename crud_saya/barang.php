<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

// Handle CRUD operations
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $kode_barang = $_POST['kode_barang'] ?? '';
        $nama_barang = $_POST['nama_barang'] ?? '';
        $harga_beli = $_POST['harga_beli'] ?? 0;
        $harga_jual = $_POST['harga_jual'] ?? 0;
        $kategori = $_POST['kategori'] ?? '';
        $deskripsi = $_POST['deskripsi'] ?? '';
        
        try {
            $query = "INSERT INTO barang (kode_barang, nama_barang, harga_beli, harga_jual, kategori, deskripsi) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$kode_barang, $nama_barang, $harga_beli, $harga_jual, $kategori, $deskripsi]);
            
            // Insert stok awal
            $barang_id = $db->lastInsertId();
            $query = "INSERT INTO stok_barang (barang_id, stok_awal, stok_saat_ini) VALUES (?, 0, 0)";
            $stmt = $db->prepare($query);
            $stmt->execute([$barang_id]);
            
            setFlashMessage('success', 'Barang berhasil ditambahkan!');
        } catch (Exception $e) {
            setFlashMessage('error', 'Gagal menambahkan barang: ' . $e->getMessage());
        }
    } elseif ($action === 'update') {
        $id = $_POST['id'] ?? 0;
        $kode_barang = $_POST['kode_barang'] ?? '';
        $nama_barang = $_POST['nama_barang'] ?? '';
        $harga_beli = $_POST['harga_beli'] ?? 0;
        $harga_jual = $_POST['harga_jual'] ?? 0;
        $kategori = $_POST['kategori'] ?? '';
        $deskripsi = $_POST['deskripsi'] ?? '';
        
        try {
            $query = "UPDATE barang SET kode_barang=?, nama_barang=?, harga_beli=?, harga_jual=?, kategori=?, deskripsi=? 
                      WHERE id=?";
            $stmt = $db->prepare($query);
            $stmt->execute([$kode_barang, $nama_barang, $harga_beli, $harga_jual, $kategori, $deskripsi, $id]);
            
            setFlashMessage('success', 'Barang berhasil diperbarui!');
        } catch (Exception $e) {
            setFlashMessage('error', 'Gagal memperbarui barang: ' . $e->getMessage());
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        
        try {
            $query = "DELETE FROM barang WHERE id=?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            
            setFlashMessage('success', 'Barang berhasil dihapus!');
        } catch (Exception $e) {
            setFlashMessage('error', 'Gagal menghapus barang: ' . $e->getMessage());
        }
    }
    
    header('Location: barang.php');
    exit();
}

// Get barang data
$query = "SELECT b.*, s.stok_saat_ini FROM barang b 
          LEFT JOIN stok_barang s ON b.id = s.barang_id 
          ORDER BY b.nama_barang";
$stmt = $db->prepare($query);
$stmt->execute();
$barang_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get barang for edit
$barang_edit = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $query = "SELECT * FROM barang WHERE id=?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $barang_edit = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Barang - Sistem CRUD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class="bi bi-box-seam me-2"></i>
                        Data Barang
                    </h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#barangModal">
                        <i class="bi bi-plus-circle me-2"></i>
                        Tambah Barang
                    </button>
                </div>
                
                <?php if ($message = getFlashMessage('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($message = getFlashMessage('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Kode Barang</th>
                                        <th>Nama Barang</th>
                                        <th>Harga Beli</th>
                                        <th>Harga Jual</th>
                                        <th>Kategori</th>
                                        <th>Stok</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($barang_list)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">Belum ada data barang</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($barang_list as $barang): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($barang['kode_barang']); ?></td>
                                                <td><?php echo htmlspecialchars($barang['nama_barang']); ?></td>
                                                <td><?php echo formatRupiah($barang['harga_beli']); ?></td>
                                                <td><?php echo formatRupiah($barang['harga_jual']); ?></td>
                                                <td><?php echo htmlspecialchars($barang['kategori']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $barang['stok_saat_ini'] <= 5 ? 'bg-danger' : 'bg-success'; ?>">
                                                        <?php echo $barang['stok_saat_ini']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="editBarang(<?php echo htmlspecialchars(json_encode($barang)); ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteBarang(<?php echo $barang['id']; ?>, '<?php echo htmlspecialchars($barang['nama_barang']); ?>')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Barang -->
    <div class="modal fade" id="barangModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="barangModalTitle">Tambah Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="barangForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="action" value="create">
                        <input type="hidden" name="id" id="id">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="kode_barang" class="form-label">Kode Barang</label>
                                <input type="text" class="form-control" id="kode_barang" name="kode_barang" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="nama_barang" class="form-label">Nama Barang</label>
                                <input type="text" class="form-control" id="nama_barang" name="nama_barang" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="harga_beli" class="form-label">Harga Beli</label>
                                <input type="number" class="form-control" id="harga_beli" name="harga_beli" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="harga_jual" class="form-label">Harga Jual</label>
                                <input type="number" class="form-control" id="harga_jual" name="harga_jual" min="0" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="kategori" class="form-label">Kategori</label>
                            <input type="text" class="form-control" id="kategori" name="kategori">
                        </div>
                        
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Delete -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus barang <strong id="deleteBarangName"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteId">
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editBarang(barang) {
            document.getElementById('barangModalTitle').textContent = 'Edit Barang';
            document.getElementById('action').value = 'update';
            document.getElementById('id').value = barang.id;
            document.getElementById('kode_barang').value = barang.kode_barang;
            document.getElementById('nama_barang').value = barang.nama_barang;
            document.getElementById('harga_beli').value = barang.harga_beli;
            document.getElementById('harga_jual').value = barang.harga_jual;
            document.getElementById('kategori').value = barang.kategori;
            document.getElementById('deskripsi').value = barang.deskripsi;
            
            new bootstrap.Modal(document.getElementById('barangModal')).show();
        }
        
        function deleteBarang(id, nama) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteBarangName').textContent = nama;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        // Reset form when modal is closed
        document.getElementById('barangModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('barangForm').reset();
            document.getElementById('action').value = 'create';
            document.getElementById('barangModalTitle').textContent = 'Tambah Barang';
        });
    </script>
</body>
</html>
