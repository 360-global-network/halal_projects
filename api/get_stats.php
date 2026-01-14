<?php
// api/get_stats.php - OPTIMIZED VERSION
require_once '../config.php';
require_once '../project_manager.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $projectManager = new ProjectManager();
    
    // Get all stats in one query
    $stats = $projectManager->getAllStats();
    
    // Format total investment
    $formattedInvestment = formatInvestment($stats['total_investment']);
    
    echo json_encode([
        'success' => true,
        'stats' => array_merge($stats, [
            'formatted_investment' => $formattedInvestment
        ])
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_stats.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading statistics'
    ]);
}

function formatInvestment($amount) {
    if ($amount >= 1000000000) {
        return '₦' . number_format($amount / 1000000000, 1) . 'B';
    } elseif ($amount >= 1000000) {
        return '₦' . number_format($amount / 1000000, 1) . 'M';
    } elseif ($amount >= 1000) {
        return '₦' . number_format($amount / 1000, 1) . 'K';
    } else {
        return '₦' . number_format($amount, 2);
    }
}
?>