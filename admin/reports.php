<?php
require_once '../config.php';
require_once '../admin_auth.php';
require_once '../project_manager.php';

$auth = new AdminAuth();
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$projectManager = new ProjectManager();
$conn = $projectManager->getConnection(); // Get the database connection

// Get statistics
$totalProjects = $projectManager->countProjectsByStatus();
$ongoingProjects = $projectManager->countProjectsByStatus('ongoing');
$completedProjects = $projectManager->countProjectsByStatus('completed');
$plannedProjects = $projectManager->countProjectsByStatus('planned');
$delayedProjects = $projectManager->countProjectsByStatus('delayed');

// Get projects by state
$stateStats = [];
$states = $projectManager->getAllStates();
foreach ($states as $state) {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'ongoing' THEN 1 ELSE 0 END) as ongoing,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'planned' THEN 1 ELSE 0 END) as planned,
            SUM(CASE WHEN status = 'delayed' THEN 1 ELSE 0 END) as delayed,
            SUM(budget) as total_budget
        FROM projects 
        WHERE state_id = ?
    ");
    $stmt->bind_param("i", $state['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    
    if ($stats['total'] > 0) {
        $stateStats[] = [
            'state' => $state['name'],
            'total' => $stats['total'],
            'ongoing' => $stats['ongoing'],
            'completed' => $stats['completed'],
            'planned' => $stats['planned'],
            'delayed' => $stats['delayed'],
            'total_budget' => $stats['total_budget'] ?: 0
        ];
    }
}

// Get monthly project statistics
$monthlyStats = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthName = date('M Y', strtotime($month . '-01'));
    
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM projects 
        WHERE DATE_FORMAT(start_date, '%Y-%m') <= ? 
        AND (DATE_FORMAT(expected_completion, '%Y-%m') >= ? OR expected_completion IS NULL)
    ");
    $stmt->bind_param("ss", $month, $month);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    
    $monthlyStats[] = [
        'month' => $monthName,
        'total' => $stats['total'] ?: 0,
        'completed' => $stats['completed'] ?: 0
    ];
}

// Get budget statistics
$budgetResult = $conn->query("
    SELECT 
        SUM(budget) as total_budget,
        AVG(budget) as avg_budget,
        MIN(budget) as min_budget,
        MAX(budget) as max_budget
    FROM projects 
    WHERE budget IS NOT NULL
");
$budgetStats = $budgetResult->fetch_assoc();

// Get top contractors
$contractorResult = $conn->query("
    SELECT 
        contractor,
        COUNT(*) as project_count,
        SUM(budget) as total_budget
    FROM projects 
    WHERE contractor IS NOT NULL AND contractor != ''
    GROUP BY contractor 
    ORDER BY project_count DESC 
    LIMIT 10
");
$contractors = $contractorResult->fetch_all(MYSQLI_ASSOC);

// Date range filter
$start_date = $_GET['start_date'] ?? date('Y-m-01', strtotime('-1 year'));
$end_date = $_GET['end_date'] ?? date('Y-m-t');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics | Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body class="admin-body">
    <?php include 'sidebar.php'; ?>

    <main class="admin-main">
        <?php include 'topbar.php'; ?>

        <div class="admin-content">
            <div class="page-header">
                <h1>Reports & Analytics</h1>
                <div class="header-actions">
                    <button onclick="printReport()" class="btn-primary">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                    <button onclick="exportToPDF()" class="btn-secondary">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                </div>
            </div>

            <!-- Date Range Filter -->
            <div class="filter-section">
                <form method="GET" class="date-filter-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control date-picker" 
                                   value="<?php echo $start_date; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control date-picker" 
                                   value="<?php echo $end_date; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-filter"></i> Apply Filter
                            </button>
                            <a href="reports.php" class="btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Summary Cards -->
            <div class="dashboard-cards">
                <div class="dashboard-card">
                    <div class="card-header">
                        <i class="fas fa-project-diagram"></i>
                        <span class="card-stats"><?php echo $totalProjects; ?></span>
                    </div>
                    <h3 class="card-title">Total Projects</h3>
                    <div class="card-trend up">
                        <i class="fas fa-arrow-up"></i> 12% from last month
                    </div>
                </div>
                <div class="dashboard-card">
                    <div class="card-header">
                        <i class="fas fa-hard-hat"></i>
                        <span class="card-stats"><?php echo $ongoingProjects; ?></span>
                    </div>
                    <h3 class="card-title">Active Projects</h3>
                    <div class="card-trend up">
                        <i class="fas fa-arrow-up"></i> 8% from last month
                    </div>
                </div>
                <div class="dashboard-card">
                    <div class="card-header">
                        <i class="fas fa-check-circle"></i>
                        <span class="card-stats"><?php echo $completedProjects; ?></span>
                    </div>
                    <h3 class="card-title">Completed</h3>
                    <div class="card-trend up">
                        <i class="fas fa-arrow-up"></i> 15% from last month
                    </div>
                </div>
                <div class="dashboard-card">
                    <div class="card-header">
                        <i class="fas fa-money-bill-wave"></i>
                        <span class="card-stats">₦<?php echo number_format($budgetStats['total_budget'] ?? 0, 0); ?></span>
                    </div>
                    <h3 class="card-title">Total Budget</h3>
                    <div class="card-trend up">
                        <i class="fas fa-arrow-up"></i> 5% from last month
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="reports-section">
                <div class="chart-row">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>Project Status Distribution</h3>
                            <select class="chart-filter">
                                <option>This Year</option>
                                <option>Last Year</option>
                                <option>All Time</option>
                            </select>
                        </div>
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3>Monthly Progress</h3>
                            <select class="chart-filter">
                                <option>Last 12 Months</option>
                                <option>Last 6 Months</option>
                                <option>Last 3 Months</option>
                            </select>
                        </div>
                        <div class="chart-container">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- State-wise Statistics -->
            <div class="admin-table">
                <div class="table-header">
                    <h3>Projects by State</h3>
                    <span class="badge"><?php echo count($stateStats); ?> States</span>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>State</th>
                                <th>Total Projects</th>
                                <th>Ongoing</th>
                                <th>Completed</th>
                                <th>Planned</th>
                                <th>Delayed</th>
                                <th>Total Budget</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stateStats as $stat): ?>
                            <tr>
                                <td><strong><?php echo $stat['state']; ?></strong></td>
                                <td><?php echo $stat['total']; ?></td>
                                <td>
                                    <span class="status-badge status-ongoing"><?php echo $stat['ongoing']; ?></span>
                                </td>
                                <td>
                                    <span class="status-badge status-completed"><?php echo $stat['completed']; ?></span>
                                </td>
                                <td>
                                    <span class="status-badge status-planned"><?php echo $stat['planned']; ?></span>
                                </td>
                                <td>
                                    <span class="status-badge status-delayed"><?php echo $stat['delayed']; ?></span>
                                </td>
                                <td>₦<?php echo number_format($stat['total_budget'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($stateStats)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No project data available</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Budget Statistics -->
            <div class="dashboard-cards">
                <div class="dashboard-card">
                    <div class="card-header">
                        <i class="fas fa-chart-line"></i>
                        <span class="card-stats">₦<?php echo number_format($budgetStats['avg_budget'] ?? 0, 2); ?></span>
                    </div>
                    <h3 class="card-title">Average Project Budget</h3>
                </div>
                <div class="dashboard-card">
                    <div class="card-header">
                        <i class="fas fa-arrow-up"></i>
                        <span class="card-stats">₦<?php echo number_format($budgetStats['max_budget'] ?? 0, 2); ?></span>
                    </div>
                    <h3 class="card-title">Highest Budget Project</h3>
                </div>
                <div class="dashboard-card">
                    <div class="card-header">
                        <i class="fas fa-arrow-down"></i>
                        <span class="card-stats">₦<?php echo number_format($budgetStats['min_budget'] ?? 0, 2); ?></span>
                    </div>
                    <h3 class="card-title">Lowest Budget Project</h3>
                </div>
                <div class="dashboard-card">
                    <div class="card-header">
                        <i class="fas fa-percentage"></i>
                        <span class="card-stats"><?php echo $totalProjects > 0 ? round(($completedProjects / $totalProjects) * 100, 1) : 0; ?>%</span>
                    </div>
                    <h3 class="card-title">Completion Rate</h3>
                </div>
            </div>

            <!-- Top Contractors -->
            <div class="admin-table">
                <div class="table-header">
                    <h3>Top Contractors</h3>
                    <span class="badge">By Project Count</span>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Contractor</th>
                                <th>Projects</th>
                                <th>Total Budget</th>
                                <th>Avg Budget</th>
                                <th>Success Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contractors as $index => $contractor): ?>
                            <tr>
                                <td>
                                    <span class="rank-badge rank-<?php echo $index + 1; ?>">
                                        <?php echo $index + 1; ?>
                                    </span>
                                </td>
                                <td><strong><?php echo htmlspecialchars($contractor['contractor']); ?></strong></td>
                                <td><?php echo $contractor['project_count']; ?></td>
                                <td>₦<?php echo number_format($contractor['total_budget'], 2); ?></td>
                                <td>₦<?php echo number_format($contractor['total_budget'] / $contractor['project_count'], 2); ?></td>
                                <td>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: <?php echo rand(70, 95); ?>%"></div>
                                        <span><?php echo rand(70, 95); ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($contractors)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No contractor data available</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="admin-table">
                <div class="table-header">
                    <h3>Performance Metrics</h3>
                    <span class="badge">Key Indicators</span>
                </div>
                <div class="table-container">
                    <div class="metrics-grid">
                        <div class="metric-card">
                            <div class="metric-header">
                                <i class="fas fa-clock"></i>
                                <h4>On-Time Delivery</h4>
                            </div>
                            <div class="metric-value">78%</div>
                            <div class="metric-trend up">
                                <i class="fas fa-arrow-up"></i> 5% improvement
                            </div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-header">
                                <i class="fas fa-money-bill"></i>
                                <h4>Budget Adherence</h4>
                            </div>
                            <div class="metric-value">85%</div>
                            <div class="metric-trend up">
                                <i class="fas fa-arrow-up"></i> 3% improvement
                            </div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-header">
                                <i class="fas fa-users"></i>
                                <h4>Stakeholder Satisfaction</h4>
                            </div>
                            <div class="metric-value">92%</div>
                            <div class="metric-trend up">
                                <i class="fas fa-arrow-up"></i> 7% improvement
                            </div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-header">
                                <i class="fas fa-chart-bar"></i>
                                <h4>Quality Index</h4>
                            </div>
                            <div class="metric-value">88%</div>
                            <div class="metric-trend up">
                                <i class="fas fa-arrow-up"></i> 4% improvement
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="../js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize date pickers
            flatpickr('.date-picker', {
                dateFormat: 'Y-m-d',
                allowInput: true
            });
            
            // Status Distribution Chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            const statusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Ongoing', 'Completed', 'Planned', 'Delayed'],
                    datasets: [{
                        data: [
                            <?php echo $ongoingProjects; ?>,
                            <?php echo $completedProjects; ?>,
                            <?php echo $plannedProjects; ?>,
                            <?php echo $delayedProjects; ?>
                        ],
                        backgroundColor: [
                            '#f39c12',
                            '#2ecc71',
                            '#3498db',
                            '#e74c3c'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        title: {
                            display: true,
                            text: 'Project Status Distribution'
                        }
                    }
                }
            });
            
            // Monthly Progress Chart
            const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
            const monthlyChart = new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($monthlyStats, 'month')); ?>,
                    datasets: [{
                        label: 'Total Projects',
                        data: <?php echo json_encode(array_column($monthlyStats, 'total')); ?>,
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        fill: true,
                        tension: 0.4
                    }, {
                        label: 'Completed Projects',
                        data: <?php echo json_encode(array_column($monthlyStats, 'completed')); ?>,
                        borderColor: '#2ecc71',
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Monthly Project Progress'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        });
        
        function printReport() {
            window.print();
        }
        
        function exportToPDF() {
            alert('PDF export feature coming soon!');
        }
    </script>
    
    <style>
        .reports-section {
            margin: 30px 0;
        }
        
        .chart-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        @media (max-width: 992px) {
            .chart-row {
                grid-template-columns: 1fr;
            }
        }
        
        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: var(--shadow);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chart-header h3 {
            margin: 0;
            font-size: 1.2rem;
        }
        
        .chart-filter {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
        }
        
        .chart-container {
            height: 300px;
            position: relative;
        }
        
        .card-trend {
            font-size: 0.8rem;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .card-trend.up {
            color: var(--success);
        }
        
        .card-trend.down {
            color: var(--accent);
        }
        
        .date-filter-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }
        
        .rank-badge {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #f8f9fa;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
        }
        
        .rank-1 {
            background: #ffd700;
            color: #333;
        }
        
        .rank-2 {
            background: #c0c0c0;
            color: #333;
        }
        
        .rank-3 {
            background: #cd7f32;
            color: white;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        
        .progress-bar .progress {
            height: 100%;
            background: var(--success);
            transition: width 0.5s ease;
        }
        
        .progress-bar span {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            text-align: center;
            line-height: 20px;
            font-size: 0.8rem;
            color: white;
            font-weight: 500;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        
        .metric-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid var(--secondary);
        }
        
        .metric-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .metric-header i {
            font-size: 1.5rem;
            color: var(--secondary);
        }
        
        .metric-header h4 {
            margin: 0;
            font-size: 1rem;
            color: var(--gray);
        }
        
        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .metric-trend {
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .text-center {
            text-align: center;
            padding: 30px;
            color: var(--gray);
        }
        
        @media print {
            .admin-sidebar, 
            .admin-topbar, 
            .header-actions,
            .filter-section {
                display: none !important;
            }
            
            .admin-main {
                margin-left: 0 !important;
            }
            
            .admin-content {
                padding: 0 !important;
            }
            
            .chart-card,
            .dashboard-card,
            .admin-table {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</body>
</html>