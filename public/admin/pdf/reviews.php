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
    $total = $data['total'] ?? '';
    $countPeriod = $data['countPeriod'] ?? '';
    $avgLength = $data['avgLength'] ?? '';
    $lineImg = $data['lineImg'] ?? '';
    $lastReviews = $data['lastReviews'] ?? [];
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
        $mpdf->SetTitle('Аналитика по отзывам');
        $style = '<style>body, table, ul, ol { font-family: DejaVu Sans, Arial, sans-serif; }</style>';

        $html = '<h1 style="text-align:center;">Аналитика по отзывам</h1>';
        $html .= '<p style="text-align:center;">' . $period_str . '</p>';
        $html .= '<hr>';
        $html .= '<h2>Ключевые метрики</h2>';
        $html .= '<ul>';
        $html .= '<li><b>Всего отзывов:</b> ' . htmlspecialchars($total) . '</li>';
        $html .= '<li><b>За выбранный период:</b> ' . htmlspecialchars($countPeriod) . '</li>';
        $html .= '<li><b>Средняя длина отзыва:</b> ' . htmlspecialchars($avgLength) . ' символов</li>';
        $html .= '</ul>';
        $html .= '<h2>График по неделям</h2>';
        if ($lineImg) {
            $html .= '<div><img src="' . $lineImg . '" style="max-width:600px;"></div>';
        }
        $html .= '<h2 style="margin-top:30px;">Последние 5 отзывов</h2>';
        $html .= '<table border="1" cellpadding="6" style="border-collapse:collapse; width:90%;"><thead><tr><th>Автор</th><th>Текст</th><th>Дата</th></tr></thead><tbody>';
        if (count($lastReviews) === 0) {
            $html .= '<tr><td colspan="3" style="text-align:center;">Нет данных</td></tr>';
        } else {
            foreach ($lastReviews as $row) {
                $html .= '<tr><td>' . htmlspecialchars($row['author']) . '</td><td>' . htmlspecialchars($row['text']) . '</td><td>' . htmlspecialchars($row['created_at']) . '</td></tr>';
            }
        }
        $html .= '</tbody></table>';

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