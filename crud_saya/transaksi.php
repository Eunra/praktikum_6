<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

// Handle transaksi operations
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $tanggal_transaksi = $_POST['tanggal_transaksi'] ?? date('Y-m-d');
        $barang_ids = $_POST['barang_id'] ?? [];
        $jumlahs = $_POST['jumlah'] ?? [];
        $jumlah_bayar = $_POST['jumlah_bayar'] ?? 0;
        
        try {
            $db->beginTransaction();
            
            // Generate kode transaksi
            $kode_transaksi = generateKodeTransaksi();
            
            // Calculate total harga
            $total_harga = 0;
            $detail_transaksi = [];
            
            for ($i = 0; $i < count($barang_ids); $i++) {
                if (!empty($barang_ids[$i]) && !empty($jumlahs[$i])) {
                    $barang_id = $barang_ids[$i];
                    $jumlah = $jumlahs[$i];
                    
                    // Get barang info
                    $query = "SELECT harga_jual FROM barang WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$barang_id]);
                    $barang = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($barang) {
                        $harga_satuan = $barang['harga_jual'];
                        $subtotal = $harga_satuan * $jumlah;
                        $total_harga += $subtotal;
                        
                        $detail_transaksi[] = [
                            'barang_id' => $barang_id,
                            'jumlah' => $jumlah,
                            'harga_satuan' => $harga_satuan,
                            'subtotal' => $subtotal
                        ];
                    }
                }
            }
            
            $kembalian = $jumlah_bayar - $total_harga;
            
            if ($kembalian < 0) {
                throw new Exception('Jumlah bayar tidak mencukupi!');
            }
            
            // Insert transaksi
            $query = "INSERT INTO transaksi (kode_transaksi, tanggal_transaksi, total_harga, jumlah_bayar, kembalian, user_id) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$kode_transaksi, $tanggal_transaksi, $total_harga, $jumlah_bayar, $kembalian, $_SESSION['user_id']]);
            
            $transaksi_id = $db->lastInsertId();
            
            // Insert detail transaksi dan update stok
            foreach ($detail_transaksi as $detail) {
                // Insert detail transaksi
                $query = "INSERT INTO detail_transaksi (transaksi_id, barang_id, jumlah, harga_satuan, subtotal) 
                          VALUES (?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$transaksi_id, $detail['barang_id'], $detail['jumlah'], $detail['harga_satuan'], $detail['subtotal']]);
                
                // Update stok
                $query = "UPDATE stok_barang SET stok_keluar = stok_keluar + ?, stok_saat_ini = stok_saat_ini - ? 
                          WHERE barang_id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$detail['jumlah'], $detail['jumlah'], $detail['barang_id']]);
            }
            
            $db->commit();
            setFlashMessage('success', 'Transaksi berhasil dibuat! Kode: ' . $kode_transaksi);
        } catch (Exception $e) {
            $db->rollBack();
            setFlashMessage('error', 'Gagal membuat transaksi: ' . $e->getMessage());
        }
    }
    
    header('Location: transaksi.php');
    exit();
}

// Get transaksi data
$query = "SELECT t.*, u.nama_lengkap FROM transaksi t 
          JOIN users u ON t.user_id = u.id 
          ORDER BY t.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$transaksi_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get barang data for dropdown
$query = "SELECT b.*, s.stok_saat_ini FROM barang b 
          LEFT JOIN stok_barang s ON b.id = s.barang_id 
          WHERE s.stok_saat_ini > 0
          ORDER BY b.nama_barang";
$stmt = $db->prepare($query);
$stmt->execute();
$barang_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi - Sistem CRUD</title>
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
                        <i class="bi bi-cart me-2"></i>
                        Transaksi
                    </h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#transaksiModal">
                        <i class="bi bi-plus-circle me-2"></i>
                        Buat Transaksi
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
                                        <th>Kode Transaksi</th>
                                        <th>Tanggal</th>
                                        <th>Total Harga</th>
                                        <th>Jumlah Bayar</th>
                                        <th>Kembalian</th>
                                        <th>Kasir</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($transaksi_list)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">Belum ada transaksi</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($transaksi_list as $transaksi): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($transaksi['kode_transaksi']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($transaksi['tanggal_transaksi'])); ?></td>
                                                <td><?php echo formatRupiah($transaksi['total_harga']); ?></td>
                                                <td><?php echo formatRupiah($transaksi['jumlah_bayar']); ?></td>
                                                <td><?php echo formatRupiah($transaksi['kembalian']); ?></td>
                                                <td><?php echo htmlspecialchars($transaksi['nama_lengkap']); ?></td>
                                                <td>
                                                    <a href="transaksi_detail.php?id=<?php echo $transaksi['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
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
    
    <!-- Modal Transaksi -->
    <div class="modal fade" id="transaksiModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Buat Transaksi Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="transaksiForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="tanggal_transaksi" class="form-label">Tanggal Transaksi</label>
                                <input type="date" class="form-control" id="tanggal_transaksi" name="tanggal_transaksi" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6>Detail Barang</h6>
                                <div id="barangContainer">
                                    <div class="row mb-2 barang-row">
                                        <div class="col-md-4">
                                            <select class="form-select barang-select" name="barang_id[]" required>
                                                <option value="">Pilih Barang</option>
                                                <?php foreach ($barang_list as $barang): ?>
                                                    <option value="<?php echo $barang['id']; ?>" 
                                                            data-harga="<?php echo $barang['harga_jual']; ?>"
                                                            data-stok="<?php echo $barang['stok_saat_ini']; ?>">
                                                        <?php echo htmlspecialchars($barang['nama_barang'] . ' - Stok: ' . $barang['stok_saat_ini']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" class="form-control jumlah-input" name="jumlah[]" 
                                                   min="1" placeholder="Jumlah" required>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="text" class="form-control harga-input" readonly placeholder="Harga">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="text" class="form-control subtotal-input" readonly placeholder="Subtotal">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-danger btn-sm remove-row">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="addBarang">
                                    <i class="bi bi-plus-circle me-1"></i>
                                    Tambah Barang
                                </button>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="total_harga" class="form-label">Total Harga</label>
                                <input type="text" class="form-control" id="total_harga" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="jumlah_bayar" class="form-label">Jumlah Bayar</label>
                                <input type="number" class="form-control" id="jumlah_bayar" name="jumlah_bayar" 
                                       min="0" step="100" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="kembalian" class="form-label">Kembalian</label>
                                <input type="text" class="form-control" id="kembalian" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Transaksi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let totalHarga = 0;
        
        // Add barang row
        document.getElementById('addBarang').addEventListener('click', function() {
            const container = document.getElementById('barangContainer');
            const newRow = container.querySelector('.barang-row').cloneNode(true);
            
            // Clear values
            newRow.querySelector('.barang-select').value = '';
            newRow.querySelector('.jumlah-input').value = '';
            newRow.querySelector('.harga-input').value = '';
            newRow.querySelector('.subtotal-input').value = '';
            
            container.appendChild(newRow);
            attachRowEvents(newRow);
        });
        
        // Remove barang row
        function attachRowEvents(row) {
            row.querySelector('.remove-row').addEventListener('click', function() {
                if (document.querySelectorAll('.barang-row').length > 1) {
                    row.remove();
                    calculateTotal();
                }
            });
            
            // Barang select change
            row.querySelector('.barang-select').addEventListener('change', function() {
                const harga = this.options[this.selectedIndex].getAttribute('data-harga');
                const stok = this.options[this.selectedIndex].getAttribute('data-stok');
                row.querySelector('.harga-input').value = harga ? formatRupiah(harga) : '';
                row.querySelector('.jumlah-input').max = stok || 0;
                calculateSubtotal(row);
            });
            
            // Jumlah input change
            row.querySelector('.jumlah-input').addEventListener('input', function() {
                calculateSubtotal(row);
            });
        }
        
        function calculateSubtotal(row) {
            const harga = row.querySelector('.barang-select').options[row.querySelector('.barang-select').selectedIndex].getAttribute('data-harga');
            const jumlah = row.querySelector('.jumlah-input').value;
            
            if (harga && jumlah) {
                const subtotal = harga * jumlah;
                row.querySelector('.subtotal-input').value = formatRupiah(subtotal);
                calculateTotal();
            } else {
                row.querySelector('.subtotal-input').value = '';
                calculateTotal();
            }
        }
        
        function calculateTotal() {
            totalHarga = 0;
            document.querySelectorAll('.subtotal-input').forEach(function(input) {
                const value = input.value.replace(/[^\d]/g, '');
                if (value) {
                    totalHarga += parseInt(value);
                }
            });
            
            document.getElementById('total_harga').value = formatRupiah(totalHarga);
            calculateKembalian();
        }
        
        function calculateKembalian() {
            const jumlahBayar = document.getElementById('jumlah_bayar').value;
            const kembalian = jumlahBayar - totalHarga;
            document.getElementById('kembalian').value = formatRupiah(kembalian);
        }
        
        document.getElementById('jumlah_bayar').addEventListener('input', calculateKembalian);
        
        function formatRupiah(angka) {
            return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
        
        // Attach events to initial row
        document.querySelectorAll('.barang-row').forEach(attachRowEvents);
        
        // Reset form when modal is closed
        document.getElementById('transaksiModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('transaksiForm').reset();
            document.getElementById('total_harga').value = '';
            document.getElementById('kembalian').value = '';
            
            // Reset to single row
            const rows = document.querySelectorAll('.barang-row');
            for (let i = 1; i < rows.length; i++) {
                rows[i].remove();
            }
        });
    </script>
</body>
</html>
