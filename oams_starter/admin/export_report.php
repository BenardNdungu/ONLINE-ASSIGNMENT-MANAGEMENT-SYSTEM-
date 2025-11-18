<?php
// export_report.php
// Handles PDF & CSV report exports for Admin (Students, Teachers, Submissions, All)

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';
require_role('admin');

// Get report type and format
$type   = $_GET['type'] ?? '';
$format = $_GET['format'] ?? 'pdf';

$allowedTypes = ['student', 'teacher', 'submissions', 'all'];
$allowedFormats = ['pdf', 'csv'];

if (!in_array($type, $allowedTypes) || !in_array($format, $allowedFormats)) {
    http_response_code(400);
    exit("Invalid report type or format.");
}

// ----------------------
// Fetch data
// ----------------------
switch ($type) {
    case 'student':
        $query = "
            SELECT u.id AS student_id, u.name AS student_name, u.email,
                   a.title AS assignment_title, a.due_date,
                   s.submitted_at, s.score, s.feedback
            FROM users u
            LEFT JOIN submissions s ON s.student_id = u.id
            LEFT JOIN assignments a ON a.id = s.assignment_id
            WHERE u.role = 'student'
            ORDER BY u.name ASC, a.due_date ASC
        ";
        break;

    case 'teacher':
        $query = "
            SELECT u.id AS teacher_id, u.name AS teacher_name, u.email,
                   COUNT(a.id) AS assignments_created,
                   SUM(CASE WHEN s.id IS NOT NULL THEN 1 ELSE 0 END) AS total_submissions
            FROM users u
            LEFT JOIN assignments a ON a.teacher_id = u.id
            LEFT JOIN submissions s ON s.assignment_id = a.id
            WHERE u.role = 'teacher'
            GROUP BY u.id
            ORDER BY u.name ASC
        ";
        break;

    case 'submissions':
        $query = "
            SELECT s.id AS submission_id, u.name AS student_name,
                   a.title AS assignment_title, a.due_date,
                   s.submitted_at, s.score, s.feedback, s.file_path
            FROM submissions s
            JOIN users u ON u.id = s.student_id
            JOIN assignments a ON a.id = s.assignment_id
            ORDER BY s.submitted_at DESC
        ";
        break;

    case 'all':
        // Multiple sections combined
        $queries = [
            'STUDENT REPORT' => "
                SELECT u.id AS student_id, u.name AS student_name, u.email,
                       a.title AS assignment_title, a.due_date,
                       s.submitted_at, s.score, s.feedback
                FROM users u
                LEFT JOIN submissions s ON s.student_id = u.id
                LEFT JOIN assignments a ON a.id = s.assignment_id
                WHERE u.role = 'student'
                ORDER BY u.name ASC
            ",
            'TEACHER REPORT' => "
                SELECT u.id AS teacher_id, u.name AS teacher_name, u.email,
                       COUNT(a.id) AS assignments_created,
                       SUM(CASE WHEN s.id IS NOT NULL THEN 1 ELSE 0 END) AS total_submissions
                FROM users u
                LEFT JOIN assignments a ON a.teacher_id = u.id
                LEFT JOIN submissions s ON s.assignment_id = a.id
                WHERE u.role = 'teacher'
                GROUP BY u.id
                ORDER BY u.name ASC
            ",
            'SUBMISSION REPORT' => "
                SELECT s.id AS submission_id, u.name AS student_name,
                       a.title AS assignment_title, a.due_date,
                       s.submitted_at, s.score, s.feedback
                FROM submissions s
                JOIN users u ON u.id = s.student_id
                JOIN assignments a ON a.id = s.assignment_id
                ORDER BY s.submitted_at DESC
            "
        ];
        break;

    default:
        $query = "";
}

// ----------------------
// CSV Export
// ----------------------
if ($format === 'csv' && $type !== 'all') {
    $stmt = $pdo->query($query);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = "{$type}_report_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"$filename\"");
    $output = fopen('php://output', 'w');

    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    } else {
        fputcsv($output, ["No data available"]);
    }

    fclose($output);
    exit;
}

// ----------------------
// PDF Export (TCPDF)
// ----------------------

// Include TCPDF (manual or Composer)
$tcpdf_included = false;
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    $tcpdf_included = class_exists('TCPDF');
}
if (!$tcpdf_included && file_exists(__DIR__ . '/../tcpdf/tcpdf.php')) {
    require_once __DIR__ . '/../tcpdf/tcpdf.php';
    $tcpdf_included = class_exists('TCPDF');
}
if (!$tcpdf_included) {
    exit("TCPDF not found. Please install it via Composer or place it in /tcpdf/.");
}

// Initialize PDF
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('OAMS');
$pdf->SetAuthor('Admin');
$pdf->SetTitle(ucfirst($type) . ' Report');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

// Title
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'TASKNEST - Online Assignment Management System', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 8, strtoupper($type) . " REPORT", 0, 1, 'C');
$pdf->Ln(5);

// ----------------------
// Handle ALL REPORT
// ----------------------
$pdf->SetFont('helvetica', '', 9);

if ($type === 'all') {
    foreach ($queries as $sectionTitle => $query) {
        $pdf->AddPage();
        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->Cell(0, 10, $sectionTitle, 0, 1, 'C');
        $pdf->Ln(3);
        $pdf->SetFont('helvetica', '', 9);

        $stmt = $pdo->query($query);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($data)) {
            $pdf->Cell(0, 10, 'No records found.', 0, 1, 'C');
            continue;
        }

        $html = '<style>
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #aaa; padding: 5px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
        </style>';

        $html .= '<table><tr>';
        foreach (array_keys($data[0]) as $col) {
            $html .= '<th>' . htmlspecialchars(strtoupper(str_replace('_', ' ', $col))) . '</th>';
        }
        $html .= '</tr>';

        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . htmlspecialchars($cell ?? '') . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
        $pdf->writeHTML($html, true, false, true, false, '');
    }
} else {
    // ----------------------
    // Single Report PDF
    // ----------------------
    $stmt = $pdo->query($query);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($data)) {
        $pdf->Cell(0, 10, 'No records found.', 0, 1, 'C');
    } else {
        $html = '<style>
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #aaa; padding: 5px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
        </style>';

        $html .= '<table><tr>';
        foreach (array_keys($data[0]) as $col) {
            $html .= '<th>' . htmlspecialchars(strtoupper(str_replace('_', ' ', $col))) . '</th>';
        }
        $html .= '</tr>';

        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . htmlspecialchars($cell ?? '') . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
        $pdf->writeHTML($html, true, false, true, false, '');
    }
}

// Footer note
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'R');

// Output
$filename = "{$type}_report_" . date('Ymd_His') . ".pdf";
$pdf->Output($filename, 'I'); // Open in browser (use 'D' for auto-download)
exit;
?>
