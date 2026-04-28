<?php
session_start();

include 'verify.php';
include 'db_connection.php';

    $sql = "SELECT id,username  FROM  users";
    $result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libraread</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="whole-dashboard">
        
        <div class="left-bar">
            <div class="navbar">
                <div class="brand">
                    <div class="brand-icon">
                        <i class="fa-solid fa-book-open"></i>
                    </div>
                    <h1>Libraread</h1>
                    <span class="brand-sub">LIBRARY</span>
                </div>
                <div class="menu">
                    <ul>
                        <li><a href="#" class="active" data-page="dashboard"><i class="fa-solid fa-grid-2"></i> Dashboard</a></li>
                        <li><a href="#" data-page="catalog"><i class="fa-solid fa-book-bookmark"></i> Catalog</a></li>
                        <li><a href="#" data-page="books"><i class="fa-solid fa-books"></i> Books</a></li>
                        <li><a href="#" data-page="users"><i class="fa-solid fa-users"></i> Users</a></li>
                    </ul>
                </div>
                <div class="logout">
                    <a href="#"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
                </div>
            </div>
        </div>

        <div class="main-content">

            <div class="topbar">
                <div class="topbar-user">
                    <div class="user-avatar"><i class="fa-solid fa-user"></i></div>
                    <div class="user-info">
                        <span class="user-name">Admin User</span>
                        <span class="user-role">Admin</span>
                    </div>
                </div>
                <div class="topbar-right">
                    <div class="topbar-time" id="topbar-time"></div>
                    <button class="settings-btn"><i class="fa-solid fa-gear"></i></button>
                </div>
            </div>

            <div class="page active" id="page-dashboard">
                <div class="dashboard-grid">

                    <div class="dash-chart-area">
                        <canvas id="borrowChart" width="320" height="320"></canvas>
                        <div class="chart-legend">
                            <span class="legend-dot dark"></span> Total Borrowed Books
                            <span class="legend-dot mid"></span> Total Returned Books
                        </div>
                    </div>

                    <div class="dash-stats">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fa-solid fa-user"></i></div>
                            <div class="stat-divider"></div>
                            <div class="stat-info">
                                <span class="stat-number" id="stat-users">0000</span>
                                <span class="stat-label">Total User Base</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fa-solid fa-book-open-reader"></i></div>
                            <div class="stat-divider"></div>
                            <div class="stat-info">
                                <span class="stat-number" id="stat-books">0000</span>
                                <span class="stat-label">Total Book Count</span>
                            </div>
                        </div>
                    </div>

                    <div class="dash-panel">
                        <h3 class="panel-title">Overdue Borrowers</h3>
                        <div class="panel-list" id="overdue-list">
                            <div class="empty-state"><i class="fa-solid fa-circle-check"></i><p>No overdue borrowers</p></div>
                        </div>
                    </div>

                    <div class="dash-admins">
                        <h3 class="panel-title">Libraread Admins</h3>
                        <div class="panel-list" id="admin-list">
                            <div class="empty-state"><i class="fa-solid fa-user-shield"></i><p>No admins found</p></div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="page" id="page-catalog">
                <div class="page-header">
                    <div class="tab-group">
                        <button class="tab active" data-tab="borrowed">Borrowed Books</button>
                        <button class="tab" data-tab="overdue">Overdue Borrowers</button>
                    </div>
                    <div class="search-bar">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Search by ID" id="catalog-search">
                    </div>
                </div>

                <div class="tab-content active" id="tab-borrowed">
                    <div class="table-wrapper" id="borrowed-table-wrapper">
                        <div class="empty-table-state">
                            <i class="fa-solid fa-book-open"></i>
                            <p>No borrowed books records yet.</p>
                            <span>Data will appear here once connected to the database.</span>
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="tab-overdue">
                    <div class="table-wrapper" id="overdue-table-wrapper">
                        <div class="empty-table-state">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <p>No overdue borrowers yet.</p>
                            <span>Data will appear here once connected to the database.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="page" id="page-books">
                <div class="page-header">
                    <div class="search-bar">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Search books..." id="books-search">
                    </div>
                    <button class="add-btn"><i class="fa-solid fa-plus"></i> Add Book</button>
                </div>
                <div class="table-wrapper" id="books-table-wrapper">
                    <div class="empty-table-state">
                        <i class="fa-solid fa-books"></i>
                        <p>No books in the catalog yet.</p>
                        <span>Data will appear here once connected to the database.</span>
                    </div>
                </div>
            </div>

            <div class="page" id="page-users">
                <div class="page-header">
                    <div class="search-bar">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Search users..." id="users-search">
                    </div>
                    <button class="add-btn"><i class="fa-solid fa-plus"></i> Add User</button>
                </div>
                <div class="table-wrapper" id="users-table-wrapper">
                    <div class="empty-table-state">
                        <i class="fa-solid fa-users"></i>
                        <p>No users registered yet.</p>
                        <span>Data will appear here once connected to the database.</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script src="app.js"></script>
</body>
</html>