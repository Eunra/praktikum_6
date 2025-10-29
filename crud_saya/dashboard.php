<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

// Ambil data statistik
$stats = [];

// Total barang
$query = "SELECT COUNT(*) as total FROM barang";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_barang'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total transaksi hari ini
$query = "SELECT COUNT(*) as total FROM transaksi WHERE DATE(tanggal_transaksi) = CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['transaksi_hari_ini'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total penjualan hari ini
$query = "SELECT COALESCE(SUM(total_harga), 0) as total FROM transaksi WHERE DATE(tanggal_transaksi) = CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['penjualan_hari_ini'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Barang dengan stok rendah
$query = "SELECT COUNT(*) as total FROM stok_barang WHERE stok_saat_ini <= 5";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['stok_rendah'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Transaksi terbaru
$query = "SELECT t.*, u.nama_lengkap FROM transaksi t 
          JOIN users u ON t.user_id = u.id 
          ORDER BY t.created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$transaksi_terbaru = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashbord - EunraGlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </h2>
            </div>
        </div>
        
        <!-- Statistik Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Total Barang</h6>
                                <h3><?php echo $stats['total_barang']; ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-box-seam" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Transaksi Hari Ini</h6>
                                <h3><?php echo $stats['transaksi_hari_ini']; ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-cart-check" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Penjualan Hari Ini</h6>
                                <h3><?php echo formatRupiah($stats['penjualan_hari_ini']); ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-currency-dollar" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title">Stok Rendah</h6>
                                <h3><?php echo $stats['stok_rendah']; ?></h3>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-exclamation-triangle" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Transaksi Terbaru -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-clock-history me-2"></i>
                            Transaksi Terbaru
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Kode Transaksi</th>
                                        <th>Tanggal</th>
                                        <th>Total Harga</th>
                                        <th>Kasir</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($transaksi_terbaru)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Belum ada transaksi</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($transaksi_terbaru as $transaksi): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($transaksi['kode_transaksi']); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($transaksi['tanggal_transaksi'])); ?></td>
                                                <td><?php echo formatRupiah($transaksi['total_harga']); ?></td>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
