<?php
// export_student_report.php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/db.php';
require_role('student');

$user = current_user();
$studentId = $user['id'];

// Fetch student's submission and performance
$stmt = $pdo->prepare("
    SELECT a.title, a.due_date, s.submitted_at, s.score, s.feedback
    FROM assignments a
    LEFT JOIN submissions s ON s.assignment_id = a.id AND s.student_id = ?
    ORDER BY a.due_date ASC
");
$stmt->execute([$studentId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Load TCPDF
require_once __DIR__ . '/../tcpdf/tcpdf.php';

// Initialize TCPDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('OAMS');
$pdf->SetAuthor($user['name']);
$pdf->SetTitle('Student Performance Report');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

// Title
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'TASKNEST - Student Performance Report', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 8, 'Name: ' . htmlspecialchars($user['name']), 0, 1);
$pdf->Cell(0, 8, 'Email: ' . htmlspecialchars($user['email']), 0, 1);
$pdf->Ln(5);

// Table
$pdf->SetFont('helvetica', '', 9);
if (empty($rows)) {
    $pdf->Cell(0, 10, 'No submissions found.', 0, 1, 'C');
} else {
    $html = '<style>
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #aaa; padding: 5px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
             </style>';
    $html .= '<table><tr><th>Assignment</th><th>Due Date</th><th>Submitted</th><th>Score</th><th>Feedback</th></tr>';
    foreach ($rows as $r) {
        $html .= '<tr>
                    <td>' . htmlspecialchars($r['title']) . '</td>
                    <td>' . htmlspecialchars($r['due_date']) . '</td>
                    <td>' . htmlspecialchars($r['submitted_at'] ?? 'Not Submitted') . '</td>
                    <td>' . htmlspecialchars($r['score'] ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($r['feedback'] ?? 'No Feedback') . '</td>
                  </tr>';
    }
    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, '');
}

$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 10, 'Generated on ' . date('Y-m-d H:i:s'), 0, 1, 'R');

// Output PDF in browser
$pdf->Output('student_report_' . date('Ymd_His') . '.pdf', 'I');
exit;
?>
