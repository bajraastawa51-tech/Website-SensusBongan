<?php
if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(404);
    exit;
}

$month = filter_var($_GET['month'] ?? null, FILTER_VALIDATE_INT);
$year = filter_var($_GET['year'] ?? null, FILTER_VALIDATE_INT);
if (!$month || $month < 1 || $month > 12 || !$year || $year < 2000 || $year > 2100) {
    http_response_code(400);
    exit('Periode laporan tidak valid.');
}

$statement = $pdo->prepare('SELECT * FROM monthly_reports WHERE report_year=? AND report_month=?');
$statement->execute([$year, $month]);
$report = $statement->fetch();
if (!$report) {
    http_response_code(404);
    exit('Laporan untuk periode tersebut belum tersedia.');
}
if ($user['role'] === 'kepala_desa' && $report['status'] !== 'approved') {
    http_response_code(403);
    exit('Laporan harus disetujui sebelum dapat diunduh.');
}

$monthName = month_name($month) . ' ' . $year;
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="census-report-' . $year . '-' . sprintf('%02d', $month) . '.csv"');
$output = fopen('php://output', 'wb');
if ($output === false) {
    throw new RuntimeException('Tidak dapat membuat berkas laporan.');
}
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, [__('app.footer'), $monthName]);
fputcsv($output, [current_lang() === 'en' ? 'Metric' : 'Data', current_lang() === 'en' ? 'Count' : 'Jumlah']);
foreach ([
    __('reports.lbl_active_pop') => 'total_population',
    __('dashboard.male') => 'male_count',
    __('dashboard.female') => 'female_count',
    __('event.birth') => 'births',
    __('event.death') => 'deaths',
    __('reports.lbl_move_ins') => 'move_ins',
    __('reports.lbl_move_outs') => 'move_outs',
] as $label => $column) {
    fputcsv($output, [$label, $report[$column]]);
}
fclose($output);
