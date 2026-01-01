<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sahamku');

function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
    }
    
    return $conn;
}

function executeQuery($sql, $params = []) {
    $conn = getDBConnection();
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        return ['success' => false, 'error' => $conn->error];
    }
    
    if (!empty($params)) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) $types .= 'i';
            elseif (is_float($param)) $types .= 'd';
            else $types .= 's';
        }
        $stmt->bind_param($types, ...$params);
    }
    
    $result = $stmt->execute();
    
    if (!$result) {
        return ['success' => false, 'error' => $stmt->error];
    }
    
    $data = $stmt->get_result();
    $stmt->close();
    
    return ['success' => true, 'data' => $data, 'insert_id' => $conn->insert_id];
}

function fetchAll($sql, $params = []) {
    $result = executeQuery($sql, $params);
    if (!$result['success']) return [];
    
    $rows = [];
    if ($result['data']) {
        while ($row = $result['data']->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function fetchOne($sql, $params = []) {
    $result = executeQuery($sql, $params);
    if (!$result['success'] || !$result['data']) return null;
    
    return $result['data']->fetch_assoc();
}
?>
