<?php
// This is a utility script to check and fix your database tables
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

require_once "../connect/db.php";

echo "<h2>Database Table Check</h2>";

try {
    // Check trxbalance table
    echo "<h3>Checking trxbalance table...</h3>";
    $stmt = $pdo->query("DESCRIBE trxbalance");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if balance column is the right type
    $balance_column = null;
    foreach ($columns as $column) {
        if ($column['Field'] === 'balance') {
            $balance_column = $column;
            break;
        }
    }
    
    if ($balance_column) {
        echo "<p><strong>Balance column found:</strong> " . $balance_column['Type'] . "</p>";
        
        // Check if it's decimal type
        if (strpos($balance_column['Type'], 'decimal') === false && strpos($balance_column['Type'], 'float') === false && strpos($balance_column['Type'], 'double') === false) {
            echo "<p style='color: red;'><strong>WARNING:</strong> Balance column should be DECIMAL, FLOAT, or DOUBLE type for proper decimal handling!</p>";
            echo "<p>Current type: " . $balance_column['Type'] . "</p>";
            echo "<p>Recommended: DECIMAL(20,6)</p>";
        } else {
            echo "<p style='color: green;'><strong>✓</strong> Balance column type is appropriate for decimal values</p>";
        }
    } else {
        echo "<p style='color: red;'><strong>ERROR:</strong> Balance column not found!</p>";
    }
    
    // Check current user's balance
    echo "<h3>Your Current Balance:</h3>";
    $stmt = $pdo->prepare("SELECT balance FROM trxbalance WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $balance = $stmt->fetchColumn();
    
    if ($balance !== false) {
        echo "<p><strong>Balance:</strong> " . number_format($balance, 6) . " TRX</p>";
        echo "<p><strong>Raw value:</strong> " . $balance . "</p>";
    } else {
        echo "<p style='color: red;'><strong>ERROR:</strong> No balance record found for your user ID</p>";
    }
    
    // Check withdrawal_requests table
    echo "<h3>Checking withdrawal_requests table...</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'withdrawal_requests'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'><strong>✓</strong> withdrawal_requests table exists</p>";
        
        $stmt = $pdo->query("DESCRIBE withdrawal_requests");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'><strong>!</strong> withdrawal_requests table does not exist (will be created automatically)</p>";
    }
    
    // Check trxhistory table
    echo "<h3>Checking trxhistory table...</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'trxhistory'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'><strong>✓</strong> trxhistory table exists</p>";
        
        $stmt = $pdo->query("DESCRIBE trxhistory");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'><strong>!</strong> trxhistory table does not exist (will be created automatically)</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>ERROR:</strong> " . $e->getMessage() . "</p>";
}

echo "<br><a href='withdraw.php'>← Back to Withdraw</a>";
?>