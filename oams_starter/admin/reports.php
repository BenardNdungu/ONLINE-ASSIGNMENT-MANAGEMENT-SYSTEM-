<?php
// reports.php - Reports & Analytics
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';
require_role('admin');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reports & Analytics - TASKNEST</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* General Body Styles - Consistent with previous versions */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background-color: #f4f7f6;
            /* Original light background */
            color: #333;
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }

        /* --- Sidebar --- (Fully consistent with the original dark theme) */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: #2c3e50;
            /* Dark Slate Blue */
            color: #ecf0f1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .sidebar h2 {
            /* Reverting to the original bolder font and clear spacing */
            font-family: 'Arial Black', Gadget, sans-serif;
            padding: 20px;
            margin: 0;
            text-align: center;
            background: #34495e;
            /* Wet Asphalt */
            letter-spacing: 2px;
            font-size: 1.2rem;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar ul li {
            border-bottom: 1px solid #34495e;
        }

        .sidebar ul li a {
            display: flex;
            align-items: center;
            /* Consistent padding and transition for hover effect */
            padding: 15px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: background 0.3s, padding-left 0.3s;
        }

        /* Applying the vibrant blue highlight and movement to active/hover states */
        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background: #3498db;
            /* Peter River Blue */
            padding-left: 25px;
        }

        .sidebar ul li a i {
            margin-right: 15px;
            /* Consistent original spacing */
            width: 20px;
            text-align: center;
        }

        /* --- Main Content --- */
        .main {
            margin-left: 250px;
            padding: 30px;
            /* Using the larger, consistent padding */
            flex: 1;
        }

        .main h1 {
            color: #2c3e50;
            margin-top: 0;
            /* Adding consistent padding/border for a section-like title */
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .main p {
            margin-bottom: 20px;
            color: #555;
        }

        /* --- Section Cards (Consistent with Dashboard sections) --- */
        .section {
            /* Increased margin-bottom for better separation */
            margin-bottom: 30px;
            background: #fff;
            /* Consistent section padding */
            padding: 25px 30px;
            border-radius: 8px;
            /* Consistent section shadow */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .section h2 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 1.5rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .section h2 i {
            margin-right: 10px;
            color: #3498db;
            /* Use accent color for icons */
        }

        .section p {
            margin: 10px 0 20px;
            /* Increased margin below description */
            color: #555;
        }

        /* --- Buttons/Links (Primary Blue Accent) --- */
        .btn {
            display: inline-block;
            margin-right: 15px;
            padding: 10px 18px;
            /* Consistent button padding */
            border-radius: 4px;
            /* Consistent button radius */
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9em;
            transition: background-color 0.3s, opacity 0.3s;
        }

        /* Primary Button Style - Use Peter River Blue for all reports */
        .btn.pdf,
        .btn.csv {
            background: #3498db;
            /* Peter River Blue */
            color: #fff;
        }

        .btn:hover {
            background: #2980b9;
            /* Darker Blue on hover */
            opacity: 1;
            /* Override the previous opacity hover */
        }

        /* --- Responsive --- (Consistent with Admin Dashboard) */
        @media (max-width: 768px) {

            /* Collapse Sidebar */
            .sidebar {
                width: 60px;
            }

            .sidebar h2,
            .sidebar ul li a span {
                display: none;
            }

            .sidebar ul li a {
                justify-content: center;
                padding: 15px 5px;
            }

            .sidebar ul li a i {
                margin-right: 0;
            }

            .main {
                margin-left: 60px;
                padding: 20px;
            }

            .section {
                padding: 20px;
            }

            .btn {
                display: block;
                width: 100%;
                margin: 10px 0;
                text-align: center;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>TASKNEST Admin</h2>
        <ul>
            <li><a href="admin_dashboard.php"><i class="fa fa-chart-line"></i><span> Dashboard</span></a></li>
            <li><a href="users.php"><i class="fa fa-users"></i><span> Manage Users</span></a></li>
            <li><a href="assignment.php"><i class="fa fa-tasks"></i><span> Manage Assignments</span></a></li>
            <li><a href="reports.php" class="active"><i class="fa fa-file-alt"></i><span> Reports & Analytics</span></a></li>
            <li><a href="../announcements.php"><i class="fa fa-bullhorn"></i><span> Announcements</span></a></li>
            <li><a href="settings.php"><i class="fa fa-cogs"></i><span> Settings & Security</span></a></li>
            <li><a href="../logout.php"><i class="fa fa-sign-out-alt"></i><span> Logout</span></a></li>
        </ul>
    </div>

    <!-- Main -->
    <div class="main">
        <h1><i class="fa fa-file-alt"></i> Reports & Analytics</h1>
        <p>Generate and download reports for student performance, teacher activity, and assignment submissions.</p>

        <!-- Student Performance Report -->
        <div class="section">
            <h2><i class="fa fa-user-graduate"></i> Student Performance</h2>
            <p>Export student grades and progress data for analysis.</p>
            <a href="export_report.php?type=student&format=pdf" target="_blank" class="btn pdf"><i class="fa fa-file-pdf"></i> Download PDF</a>
            <a href="export_report.php?type=student&format=csv" target="_blank" class="btn csv"><i class="fa fa-file-csv"></i> Download CSV</a>
        </div>

        <!-- Teacher Activity Report -->
        <div class="section">
            <h2><i class="fa fa-chalkboard-teacher"></i> Teacher Activity</h2>
            <p>Export teacher assignment creation, grading, and login activity.</p>
            <a href="export_report.php?type=teacher&format=pdf" target="_blank" class="btn pdf"><i class="fa fa-file-pdf"></i> Download PDF</a>
            <a href="export_report.php?type=teacher&format=csv" target="_blank" class="btn csv"><i class="fa fa-file-csv"></i> Download CSV</a>
        </div>

        <!-- Assignment Submissions Report -->
        <div class="section">
            <h2><i class="fa fa-file"></i> Assignment Submissions</h2>
            <p>Export submission history and pending/graded statistics.</p>
            <a href="export_report.php?type=submissions&format=pdf" target="_blank" class="btn pdf"><i class="fa fa-file-pdf"></i> Download PDF</a>
            <a href="export_report.php?type=submissions&format=csv" target="_blank" class="btn csv"><i class="fa fa-file-csv"></i> Download CSV</a>
        </div>
    </div>

</body>

</html>