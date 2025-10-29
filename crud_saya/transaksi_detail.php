<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

$transaksi_id = $_GET['id'] ?? 0;

if (!$transaksi_id) {
    header('Location: transaksi.php');
    exit();
}

// Get transaksi data
$query = "SELECT t.*, u.nama_lengkap FROM transaksi t 
          JOIN users u ON t.user_id = u.id 
          WHERE t.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$transaksi_id]);
$transaksi = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaksi) {
    header('Location: transaksi.php');
    exit();
}

// Get detail transaksi
$query = "SELECT dt.*, b.nama_barang, b.kode_barang FROM detail_transaksi dt 
          JOIN barang b ON dt.barang_id = b.id 
          WHERE dt.transaksi_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$transaksi_id]);
$detail_transaksi = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Transaksi - Sistem CRUD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            .container { max-width: none !important; }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                    <h2>
                        <i class="bi bi-receipt me-2"></i>
                        Detail Transaksi
                    </h2>
                    <div>
                        <button onclick="window.print()" class="btn btn-outline-primary me-2">
                            <i class="bi bi-printer me-1"></i>
                            Cetak
                        </button>
                        <a href="transaksi.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-1"></i>
                            Kembali
                        </a>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Struk Transaksi</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>Informasi Transaksi</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Kode Transaksi:</strong></td>
                                        <td><?php echo htmlspecialchars($transaksi['kode_transaksi']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tanggal:</strong></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($transaksi['tanggal_transaksi'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Kasir:</strong></td>
                                        <td><?php echo htmlspecialchars($transaksi['nama_lengkap']); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <h6>Detail Barang</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Kode Barang</th>
                                        <th>Nama Barang</th>
                                        <th>Jumlah</th>
                                        <th>Harga Satuan</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($detail_transaksi as $detail): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($detail['kode_barang']); ?></td>
                                            <td><?php echo htmlspecialchars($detail['nama_barang']); ?></td>
                                            <td><?php echo $detail['jumlah']; ?></td>
                                            <td><?php echo formatRupiah($detail['harga_satuan']); ?></td>
                                            <td><?php echo formatRupiah($detail['subtotal']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="4" class="text-end">Total Harga:</th>
                                        <th><?php echo formatRupiah($transaksi['total_harga']); ?></th>
                                    </tr>
                                    <tr>
                                        <th colspan="4" class="text-end">Jumlah Bayar:</th>
                                        <th><?php echo formatRupiah($transaksi['jumlah_bayar']); ?></th>
                                    </tr>
                                    <tr>
                                        <th colspan="4" class="text-end">Kembalian:</th>
                                        <th><?php echo formatRupiah($transaksi['kembalian']); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <div class="text-center mt-4">
                            <p class="text-muted">
                                Terima kasih telah berbelanja!<br>
                                <small>Tanggal cetak: <?php echo date('d/m/Y H:i'); ?></small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
