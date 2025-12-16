<?php
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/Models/Admin.php';
require_once __DIR__ . '/../../src/Controllers/AdminController.php';

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}
// Handle employer approval/rejection and admin management system
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if (isset($_POST['employer_id'])) {
            $employerId = (int)$_POST['employer_id'];

            if ($_POST['action'] === 'approve') {
                $result = AdminController::approveEmployer($employerId);
                $_SESSION['message'] = $result['msg'];
                $_SESSION['message_type'] = $result['ok'] ? 'success' : 'danger';
            } elseif ($_POST['action'] === 'reject') {
                $reason = $_POST['reason'] ?? '';
                $result = AdminController::rejectEmployer($employerId, $reason);
                $_SESSION['message'] = $result['msg'];
                $_SESSION['message_type'] = $result['ok'] ? 'success' : 'danger';
            }
        } elseif (isset($_POST['admin_id'])) {
            $adminId = (int)$_POST['admin_id'];

            if ($_POST['action'] === 'deactivate_admin') {
                $result = AdminController::deactivateAdmin($adminId);
                $_SESSION['message'] = $result['msg'];
                $_SESSION['message_type'] = $result['ok'] ? 'success' : 'danger';
            } elseif ($_POST['action'] === 'activate_admin') {
                $result = Admin::activateAdmin($adminId);
                $_SESSION['message'] = $result ? 'Admin activated successfully' : 'Failed to activate admin';
                $_SESSION['message_type'] = $result ? 'success' : 'danger';
            }
        } elseif ($_POST['action'] === 'create_admin') {
            $result = AdminController::createAdmin($_POST);
            $_SESSION['message'] = $result['msg'];
            $_SESSION['message_type'] = $result['ok'] ? 'success' : 'danger';
        }

        header('Location: dashboard.php');
        exit;
    }
}

$stats = Admin::getDashboardStats();
$pendingEmployers = Admin::getPendingEmployers();
$allEmployers = Admin::getAllEmployers();

// Check if current admin is super admin
$isSuperAdmin = isset($_SESSION['admin']['role']) && $_SESSION['admin']['role'] === 'super_admin';
// If role is not set, check if this is the first admin (backward compatibility)
if (!isset($_SESSION['admin']['role'])) {
    $isSuperAdmin = Admin::isSuperAdmin($_SESSION['admin']['id']);
}
$allAdmins = $isSuperAdmin ? Admin::getAllAdmins() : [];
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Admin Dashboard - Job Recruitment</title>
    <link rel="stylesheet" href="../css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-nav {
            background: #2c3e50;
            color: white;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }

        .admin-nav .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-nav h1 {
            margin: 0;
            font-size: 1.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: var(--shadow);
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .employer-table {
            background: white;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .table-header {
            background: #f8f9fa;
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .card-header h2 {
            margin: 0;
        }

        .table-row {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 2fr;
            gap: 1rem;
            align-items: center;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
        }
    </style>
</head>

<body>
    <!-- Admin Navigation -->
    <nav class="admin-nav">
        <div class="container">
            <h1><i class="fas fa-shield-alt"></i> Admin Dashboard</h1>
            <div>
                <span>Welcome, <?= htmlspecialchars($_SESSION['admin']['name']) ?>
                    <small>(<?= ucfirst(str_replace('_', ' ', $_SESSION['admin']['role'] ?? 'admin')) ?>)</small>
                </span>
                <a href="logout.php" class="btn btn-secondary btn-sm ml-3">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] ?>">
                <?= htmlspecialchars($_SESSION['message']) ?>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['pending_employers'] ?></div>
                <div class="stat-label">Pending Employers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['approved_employers'] ?></div>
                <div class="stat-label">Approved Employers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_jobs'] ?></div>
                <div class="stat-label">Total Jobs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_users'] ?></div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>

        <!-- Pending Employers Section -->
        <?php if (!empty($pendingEmployers)): ?>
            <div class="card mb-5">
                <div class="card-header">
                    <h2><i class="fas fa-clock"></i> Pending Employer Registrations</h2>
                </div>
                <div class="employer-table">
                    <div class="table-header">
                        <div class="table-row" style="font-weight: bold;">
                            <div>Company / Contact</div>
                            <div>Industry</div>
                            <div>Company Size</div>
                            <div>Registration Date</div>
                            <div>Actions</div>
                        </div>
                    </div>
                    <?php foreach ($pendingEmployers as $employer): ?>
                        <div class="table-row">
                            <div>
                                <strong><?= htmlspecialchars($employer['company_name']) ?></strong><br>
                                <small><?= htmlspecialchars($employer['user_name']) ?></small><br>
                                <small class="text-muted"><?= htmlspecialchars($employer['email']) ?></small>
                            </div>
                            <div><?= htmlspecialchars($employer['industry'] ?: 'Not specified') ?></div>
                            <div><?= htmlspecialchars($employer['company_size'] ?: 'Not specified') ?></div>
                            <div><?= date('M d, Y', strtotime($employer['created_at'])) ?></div>
                            <div class="action-buttons">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="employer_id" value="<?= $employer['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-success btn-sm"
                                        onclick="return confirm('Approve this employer registration?')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>
                                <button type="button" class="btn btn-danger btn-sm"
                                    onclick="showRejectModal(<?= $employer['id'] ?>)">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Admin Management Section (Super Admin Only) -->
        <?php if ($isSuperAdmin): ?>
            <div class="card mb-5">
                <div class="card-header">
                    <h2><i class="fas fa-user-shield"></i> Admin Management</h2>
                    <button class="btn btn-primary" onclick="showCreateAdminModal()">
                        <i class="fas fa-plus"></i> Create New Admin
                    </button>
                </div>
                <div class="employer-table">
                    <div class="table-header">
                        <div class="table-row" style="font-weight: bold;">
                            <div>Name / Email</div>
                            <div>Role</div>
                            <div>Status</div>
                            <div>Created Date</div>
                            <div>Actions</div>
                        </div>
                    </div>
                    <?php foreach ($allAdmins as $admin): ?>
                        <div class="table-row">
                            <div>
                                <strong><?= htmlspecialchars($admin['name']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($admin['email']) ?></small>
                            </div>
                            <div>
                                <span
                                    class="status-badge <?= $admin['role'] === 'super_admin' ? 'status-approved' : 'status-pending' ?>">
                                    <?= ucfirst(str_replace('_', ' ', $admin['role'])) ?>
                                </span>
                            </div>
                            <div>
                                <span class="status-badge <?= $admin['is_active'] ? 'status-approved' : 'status-rejected' ?>">
                                    <?= $admin['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </div>
                            <div><?= date('M d, Y', strtotime($admin['created_at'])) ?></div>
                            <div class="action-buttons">
                                <?php if ($admin['role'] !== 'super_admin' && $admin['id'] !== $_SESSION['admin']['id']): ?>
                                    <?php if ($admin['is_active']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="deactivate_admin">
                                            <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Deactivate this admin account?')">
                                                <i class="fas fa-ban"></i> Deactivate
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="activate_admin">
                                            <input type="hidden" name="admin_id" value="<?= $admin['id'] ?>">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-check"></i> Activate
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- All Employers Section -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-building"></i> All Employers</h2>
            </div>
            <div class="employer-table">
                <div class="table-header">
                    <div class="table-row" style="font-weight: bold;">
                        <div>Company / Contact</div>
                        <div>Industry</div>
                        <div>Status</div>
                        <div>Registration Date</div>
                        <div>Actions</div>
                    </div>
                </div>
                <?php foreach ($allEmployers as $employer): ?>
                    <div class="table-row">
                        <div>
                            <strong><?= htmlspecialchars($employer['company_name']) ?></strong><br>
                            <small><?= htmlspecialchars($employer['user_name']) ?></small><br>
                            <small class="text-muted"><?= htmlspecialchars($employer['email']) ?></small>
                        </div>
                        <div><?= htmlspecialchars($employer['industry'] ?: 'Not specified') ?></div>
                        <div>
                            <span class="status-badge status-<?= $employer['approval_status'] ?>">
                                <?= ucfirst($employer['approval_status']) ?>
                            </span>
                        </div>
                        <div><?= date('M d, Y', strtotime($employer['created_at'])) ?></div>
                        <div class="action-buttons">
                            <?php if ($employer['approval_status'] === 'pending'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="employer_id" value="<?= $employer['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>
                            <?php elseif ($employer['approval_status'] === 'rejected'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="employer_id" value="<?= $employer['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-redo"></i> Approve
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div
            style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 0.5rem; width: 90%; max-width: 500px;">
            <h3>Reject Employer Registration</h3>
            <form method="POST" id="rejectForm">
                <input type="hidden" name="employer_id" id="rejectEmployerId">
                <input type="hidden" name="action" value="reject">
                <div class="form-group">
                    <label for="reason">Reason for rejection (optional):</label>
                    <textarea name="reason" id="reason" class="input" rows="3"
                        placeholder="Enter reason for rejection..."></textarea>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="hideRejectModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Admin Modal (Super Admin Only) -->
    <?php if ($isSuperAdmin): ?>
        <div id="createAdminModal"
            style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
            <div
                style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 0.5rem; width: 90%; max-width: 500px;">
                <h3>Create New Admin Account</h3>
                <form method="POST" id="createAdminForm">
                    <input type="hidden" name="action" value="create_admin">
                    <div class="form-group">
                        <label for="admin_name">Full Name:</label>
                        <input type="text" name="name" id="admin_name" class="input" required>
                    </div>
                    <div class="form-group">
                        <label for="admin_email">Email Address:</label>
                        <input type="email" name="email" id="admin_email" class="input" required>
                    </div>
                    <div class="form-group">
                        <label for="admin_password">Password:</label>
                        <input type="password" name="password" id="admin_password" class="input" required>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                        <button type="button" class="btn btn-secondary" onclick="hideCreateAdminModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Admin</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function showRejectModal(employerId) {
            document.getElementById('rejectEmployerId').value = employerId;
            document.getElementById('rejectModal').style.display = 'block';
        }

        function hideRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
            document.getElementById('reason').value = '';
        }

        // Close modal when clicking outside
        document.getElementById('rejectModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideRejectModal();
            }
        });

        // Admin management functions
        function showCreateAdminModal() {
            document.getElementById('createAdminModal').style.display = 'block';
        }

        function hideCreateAdminModal() {
            document.getElementById('createAdminModal').style.display = 'none';
            document.getElementById('createAdminForm').reset();
        }

        // Close admin modal when clicking outside
        <?php if ($isSuperAdmin): ?>
            document.getElementById('createAdminModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    hideCreateAdminModal();
                }
            });
        <?php endif; ?>
    </script>
</body>

</html>