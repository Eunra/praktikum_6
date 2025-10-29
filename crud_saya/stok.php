<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Handle stok operations
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_stok') {
        $barang_id = $_POST['barang_id'] ?? 0;
        $stok_masuk = $_POST['stok_masuk'] ?? 0;
        $keterangan = $_POST['keterangan'] ?? '';
        
        try {
            $db->beginTransaction();
            
            // Update stok
            $query = "UPDATE stok_barang SET 
                      stok_masuk = stok_masuk + ?, 
                      stok_saat_ini = stok_saat_ini + ? 
                      WHERE barang_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$stok_masuk, $stok_masuk, $barang_id]);
            
            $db->commit();
            setFlashMessage('success', 'Stok berhasil diperbarui!');
        } catch (Exception $e) {
            $db->rollBack();
            setFlashMessage('error', 'Gagal memperbarui stok: ' . $e->getMessage());
        }
    }
    
    header('Location: stok.php');
    exit();
}

// Get stok data
$query = "SELECT b.*, s.* FROM barang b 
          LEFT JOIN stok_barang s ON b.id = s.barang_id 
          ORDER BY b.nama_barang";
$stmt = $db->prepare($query);
$stmt->execute();
$stok_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Barang - Sistem CRUD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">
                    <i class="bi bi-boxes me-2"></i>
                    Stok Barang
                </h2>
                
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
                                        <th>Stok Awal</th>
                                        <th>Stok Masuk</th>
                                        <th>Stok Keluar</th>
                                        <th>Stok Saat Ini</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($stok_list)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">Belum ada data stok</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($stok_list as $stok): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($stok['kode_barang']); ?></td>
                                                <td><?php echo htmlspecialchars($stok['nama_barang']); ?></td>
                                                <td><?php echo $stok['stok_awal']; ?></td>
                                                <td><?php echo $stok['stok_masuk']; ?></td>
                                                <td><?php echo $stok['stok_keluar']; ?></td>
                                                <td>
                                                    <span class="badge <?php echo $stok['stok_saat_ini'] <= 5 ? 'bg-danger' : ($stok['stok_saat_ini'] <= 10 ? 'bg-warning' : 'bg-success'); ?>">
                                                        <?php echo $stok['stok_saat_ini']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($stok['stok_saat_ini'] <= 5): ?>
                                                        <span class="text-danger">
                                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                                            Stok Rendah
                                                        </span>
                                                    <?php elseif ($stok['stok_saat_ini'] <= 10): ?>
                                                        <span class="text-warning">
                                                            <i class="bi bi-exclamation-circle me-1"></i>
                                                            Stok Sedang
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-success">
                                                            <i class="bi bi-check-circle me-1"></i>
                                                            Stok Aman
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            onclick="updateStok(<?php echo htmlspecialchars(json_encode($stok)); ?>)">
                                                        <i class="bi bi-plus-circle"></i>
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
    
    <!-- Modal Update Stok -->
    <div class="modal fade" id="stokModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Stok Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="stokForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_stok">
                        <input type="hidden" name="barang_id" id="barang_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Barang</label>
                            <input type="text" class="form-control" id="nama_barang" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Stok Saat Ini</label>
                            <input type="text" class="form-control" id="stok_saat_ini" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="stok_masuk" class="form-label">Stok Masuk</label>
                            <input type="number" class="form-control" id="stok_masuk" name="stok_masuk" 
                                   min="1" required placeholder="Masukkan jumlah stok yang akan ditambahkan">
                        </div>
                        
                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="3" 
                                      placeholder="Contoh: Restock dari supplier, retur barang, dll"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update Stok</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStok(stok) {
            document.getElementById('barang_id').value = stok.id;
            document.getElementById('nama_barang').value = stok.nama_barang;
            document.getElementById('stok_saat_ini').value = stok.stok_saat_ini;
            document.getElementById('stok_masuk').value = '';
            document.getElementById('keterangan').value = '';
            
            new bootstrap.Modal(document.getElementById('stokModal')).show();
        }
        
        // Reset form when modal is closed
        document.getElementById('stokModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('stokForm').reset();
        });
    </script>
</body>
</html>
