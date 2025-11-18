<?php
// export_teacher_report.php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';
require_role('teacher');
$user = current_user();

$type = $_GET['type'] ?? 'pdf';

// Fetch teacher’s student performance data
$stmt = $pdo->prepare("
  SELECT u.name AS student_name, a.title AS assignment_title, s.score, s.status
  FROM submissions s
  JOIN assignments a ON s.assignment_id = a.id
  JOIN users u ON s.student_id = u.id
  WHERE a.teacher_id = ?
  ORDER BY u.name ASC, a.title ASC
");
$stmt->execute([$user['id']]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================== EXPORT AS PDF ==================
if ($type === 'pdf') {
    require_once __DIR__ . '/../tcpdf/tcpdf.php';

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
    $pdf->SetCreator('TASKNEST');
    $pdf->SetAuthor('TASKNEST System');
    $pdf->SetTitle('Teacher Report');
    $pdf->SetHeaderData('', 0, 'TASKNEST - Teacher Report', 'Generated on: ' . date('Y-m-d'));
    $pdf->setHeaderFont(['helvetica', '', 10]);
    $pdf->setFooterFont(['helvetica', '', 8]);
    $pdf->SetMargins(15, 27, 15);
    $pdf->AddPage();

    $html = '<h2 style="text-align:center;">Student Performance Report</h2>';
    $html .= '<table border="1" cellpadding="5">
                <thead>
                  <tr style="background-color:#f2f2f2;">
                    <th><b>Student</b></th>
                    <th><b>Assignment</b></th>
                    <th><b>Score</b></th>
                    <th><b>Status</b></th>
                  </tr>
                </thead>
                <tbody>';

    if ($data) {
        foreach ($data as $row) {
            $html .= '<tr>
                        <td>' . htmlspecialchars($row['student_name']) . '</td>
                        <td>' . htmlspecialchars($row['assignment_title']) . '</td>
                        <td>' . ($row['score'] ?? 'N/A') . '</td>
                        <td>' . ucfirst(htmlspecialchars($row['status'])) . '</td>
                      </tr>';
        }
    } else {
        $html .= '<tr><td colspan="4" align="center">No records found</td></tr>';
    }

    $html .= '</tbody></table>';
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('teacher_report.pdf', 'I');
    exit;
}

// ================== EXPORT AS CSV ==================
if ($type === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="teacher_report.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Student', 'Assignment', 'Score', 'Status']);
    foreach ($data as $row) {
        fputcsv($output, [$row['student_name'], $row['assignment_title'], $row['score'] ?? 'N/A', $row['status']]);
    }
    fclose($output);
    exit;
}

// Default redirect if invalid type
header('Location: teacher_reports.php');
exit;
