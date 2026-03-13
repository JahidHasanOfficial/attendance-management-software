<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

checkRole(['Super Admin', 'HR']);

$active_page = 'security_logs';
$page_title = 'Security Logs';

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch Logs
$stmt = $pdo->prepare("
    SELECT l.*, u.name, u.employee_id 
    FROM security_logs l 
    JOIN users u ON l.user_id = u.id 
    ORDER BY l.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

// Total for pagination
$total = $pdo->query("SELECT COUNT(*) FROM security_logs")->fetchColumn();
$total_pages = ceil($total / $limit);

require_once '../includes/header_dashboard.php';
?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0 text-danger">
                <i class="bi bi-shield-lock-fill me-2"></i> Security Incident Logs
            </h5>
            <span class="badge bg-danger rounded-pill"><?php echo $total; ?> Total Incidents</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="bg-light">
                    <tr>
                        <th>Date & Time</th>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>IP Address</th>
                        <th>Accuracy</th>
                        <th>Details</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No security incidents recorded.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="small fw-semibold"><?php echo date('d M Y, h:i A', strtotime($log['created_at'])); ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($log['name']); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($log['employee_id']); ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $log['attempt_type'] == 'Fake GPS' ? 'danger' : 'warning text-dark'; ?> px-3 py-2">
                                        <?php echo $log['attempt_type']; ?>
                                    </span>
                                </td>
                                <td class="small"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                <td>
                                    <span class="<?php echo $log['accuracy'] <= 1 ? 'text-danger fw-bold' : ''; ?>">
                                        <?php echo round($log['accuracy'], 2); ?>m
                                    </span>
                                </td>
                                <td class="small text-muted" style="max-width: 200px;"><?php echo htmlspecialchars($log['details']); ?></td>
                                <td>
                                    <a href="https://www.google.com/maps?q=<?php echo $log['latitude']; ?>,<?php echo $log['longitude']; ?>" target="_blank" class="btn btn-sm btn-outline-primary shadow-sm" title="View Location">
                                        <i class="bi bi-geo-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link shadow-sm mx-1" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer_dashboard.php'; ?>
