<?php
require_once __DIR__ . '/src/helpers.php';

echo "<h2>Database Structure Check</h2>";

try {
    $pdo = getPDO();
    
    // Check if admins table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'admins'");
    $adminsTableExists = $stmt->rowCount() > 0;
    
    echo "<h3>1. Admins Table: " . ($adminsTableExists ? "✅ EXISTS" : "❌ MISSING") . "</h3>";
    
    if ($adminsTableExists) {
        // Check admins table structure
        $stmt = $pdo->query("DESCRIBE admins");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Admins Table Columns:</h4><ul>";
        $hasRole = false;
        $hasIsActive = false;
        $hasCreatedBy = false;
        
        foreach ($columns as $column) {
            echo "<li>" . $column['Field'] . " (" . $column['Type'] . ")</li>";
            if ($column['Field'] === 'role') $hasRole = true;
            if ($column['Field'] === 'is_active') $hasIsActive = true;
            if ($column['Field'] === 'created_by') $hasCreatedBy = true;
        }
        echo "</ul>";
        
        echo "<h4>Required Columns Check:</h4>";
        echo "<ul>";
        echo "<li>role column: " . ($hasRole ? "✅ EXISTS" : "❌ MISSING") . "</li>";
        echo "<li>is_active column: " . ($hasIsActive ? "✅ EXISTS" : "❌ MISSING") . "</li>";
        echo "<li>created_by column: " . ($hasCreatedBy ? "✅ EXISTS" : "❌ MISSING") . "</li>";
        echo "</ul>";
        
        // Check existing admins
        $stmt = $pdo->query("SELECT * FROM admins");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Existing Admins (" . count($admins) . "):</h4>";
        if (count($admins) > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th>";
            if ($hasRole) echo "<th>Role</th>";
            if ($hasIsActive) echo "<th>Active</th>";
            echo "</tr>";
            
            foreach ($admins as $admin) {
                echo "<tr>";
                echo "<td>" . $admin['id'] . "</td>";
                echo "<td>" . htmlspecialchars($admin['name']) . "</td>";
                echo "<td>" . htmlspecialchars($admin['email']) . "</td>";
                if ($hasRole) echo "<td>" . ($admin['role'] ?? 'N/A') . "</td>";
                if ($hasIsActive) echo "<td>" . ($admin['is_active'] ? 'Yes' : 'No') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No admin accounts found.</p>";
        }
    }
    
    // Check employers table
    echo "<h3>2. Employers Table Approval Columns:</h3>";
    $stmt = $pdo->query("DESCRIBE employers");
    $employerColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasApprovalStatus = false;
    $hasApprovedAt = false;
    $hasRejectionReason = false;
    
    foreach ($employerColumns as $column) {
        if ($column['Field'] === 'approval_status') $hasApprovalStatus = true;
        if ($column['Field'] === 'approved_at') $hasApprovedAt = true;
        if ($column['Field'] === 'rejection_reason') $hasRejectionReason = true;
    }
    
    echo "<ul>";
    echo "<li>approval_status column: " . ($hasApprovalStatus ? "✅ EXISTS" : "❌ MISSING") . "</li>";
    echo "<li>approved_at column: " . ($hasApprovedAt ? "✅ EXISTS" : "❌ MISSING") . "</li>";
    echo "<li>rejection_reason column: " . ($hasRejectionReason ? "✅ EXISTS" : "❌ MISSING") . "</li>";
    echo "</ul>";
    
    // Migration status
    echo "<h3>3. Migration Status:</h3>";
    $needsMigration = !$hasRole || !$hasIsActive || !$hasApprovalStatus;
    
    if ($needsMigration) {
        echo "<div style='background: #ffebee; padding: 15px; border-left: 4px solid #f44336;'>";
        echo "<h4>⚠️ MIGRATION REQUIRED</h4>";
        echo "<p>Your database needs to be updated. Please run the migration script:</p>";
        echo "<ol>";
        echo "<li>Open phpMyAdmin or your MySQL client</li>";
        echo "<li>Select your database: <strong>job_recruitment1</strong></li>";
        echo "<li>Run the SQL from <strong>database_migration.sql</strong></li>";
        echo "</ol>";
        echo "</div>";
    } else {
        echo "<div style='background: #e8f5e8; padding: 15px; border-left: 4px solid #4caf50;'>";
        echo "<h4>✅ DATABASE UP TO DATE</h4>";
        echo "<p>Your database has all required columns for the admin system.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; border-left: 4px solid #f44336;'>";
    echo "<h4>❌ DATABASE ERROR</h4>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>