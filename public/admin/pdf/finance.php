<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../vendor/autoload.php'; // mPDF

function log_pdf_error($msg) {
    file_put_contents(__DIR__ . '/../../../tmp/pdf_errors.log', date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
}
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    log_pdf_error("PHP Error: $errstr in $errfile:$errline");
});
set_exception_handler(function($e) {
    log_pdf_error("Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
});

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        http_response_code(400);
        echo 'Некорректные данные';
        exit;
    }
    $totalRevenue = $data['totalRevenue'] ?? '';
    $avgCheck = $data['avgCheck'] ?? '';
    $lineImg = $data['lineImg'] ?? '';
    $pieImg = $data['pieImg'] ?? '';
    $date_from = $data['date_from'] ?? '';
    $date_to = $data['date_to'] ?? '';
    $period_str = 'Период: ';
    if ($date_from && $date_to) {
        $period_str .= htmlspecialchars($date_from) . ' — ' . htmlspecialchars($date_to);
    } elseif ($date_from) {
        $period_str .= 'с ' . htmlspecialchars($date_from);
    } elseif ($date_to) {
        $period_str .= 'до ' . htmlspecialchars($date_to);
    } else {
        $period_str .= 'всё время';
    }

    try {
        $mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ . '/../../../tmp']);
        $mpdf->SetTitle('Финансовая аналитика');
        $style = '<style>body, table, ul, ol { font-family: DejaVu Sans, Arial, sans-serif; }</style>';

        $html = '<h1 style="text-align:center;">Финансовая аналитика</h1>';
        $html .= '<p style="text-align:center;">' . $period_str . '</p>';
        $html .= '<hr>';
        $html .= '<h2>Ключевые метрики</h2>';
        $html .= '<ul>';
        $html .= '<li><b>Общая выручка:</b> ' . htmlspecialchars($totalRevenue) . '</li>';
        $html .= '<li><b>Средний чек:</b> ' . htmlspecialchars($avgCheck) . '</li>';
        $html .= '</ul>';
        $html .= '<h2>Графики</h2>';
        if ($lineImg) {
            $html .= '<div><b>Выручка по месяцам:</b><br><img src="' . $lineImg . '" style="max-width:600px;"></div>';
        }
        if ($pieImg) {
            $html .= '<div style="margin-top:20px;"><b>Доля выручки по категориям:</b><br><img src="' . $pieImg . '" style="max-width:400px;"></div>';
        }

        $mpdf->WriteHTML($style . $html);
        $mpdf->Output();
        exit;
    } catch (\Throwable $e) {
        log_pdf_error('mPDF error: ' . $e->getMessage());
        http_response_code(500);
        echo 'Ошибка генерации PDF. Подробнее см. в tmp/pdf_errors.log';
        exit;
    }
}
// Если не POST — тестовый PDF для ручной проверки
$mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ . '/../../../tmp']);
$mpdf->WriteHTML('<h1>mPDF работает!</h1>');
$mpdf->Output();
exit; 