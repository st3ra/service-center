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
    $metrics = $data['metrics'] ?? [];
    $pieImg = $data['pieImg'] ?? '';
    $lineImg = $data['lineImg'] ?? '';
    $topDays = $data['topDays'] ?? [];
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
        $mpdf->SetTitle('Аналитика по заявкам');

        $period = date('d.m.Y');
        $html = '<h1 style="text-align:center;">Аналитика по заявкам</h1>';
        $html .= '<p style="text-align:center;">' . $period_str . '</p>';
        $html .= '<hr>';
        $html .= '<h2>Ключевые метрики</h2>';
        $html .= '<ul>';
        $html .= '<li><b>Всего заявок:</b> ' . htmlspecialchars($metrics['total'] ?? '-') . '</li>';
        $html .= '<li><b>Заявок за неделю:</b> ' . htmlspecialchars($metrics['week'] ?? '-') . '</li>';
        $html .= '<li><b>Заявок за месяц:</b> ' . htmlspecialchars($metrics['month'] ?? '-') . '</li>';
        $html .= '</ul>';
        $html .= '<h2>Графики</h2>';
        if ($pieImg) {
            $html .= '<div><b>Распределение по статусам:</b><br><img src="' . $pieImg . '" style="max-width:400px;"></div>';
        }
        if ($lineImg) {
            $html .= '<div style="margin-top:20px;"><b>Динамика по дням:</b><br><img src="' . $lineImg . '" style="max-width:500px;"></div>';
        }
        $html .= '<h2 style="margin-top:30px;">Топ-5 дней по количеству заявок</h2>';
        $html .= '<table border="1" cellpadding="6" style="border-collapse:collapse; width:60%;"><thead><tr><th>Дата</th><th>Количество</th></tr></thead><tbody>';
        if (count($topDays) === 0) {
            $html .= '<tr><td colspan="2" style="text-align:center;">Нет данных</td></tr>';
        } else {
            foreach ($topDays as $row) {
                $html .= '<tr><td>' . htmlspecialchars($row['date']) . '</td><td>' . htmlspecialchars($row['count']) . '</td></tr>';
            }
        }
        $html .= '</tbody></table>';

        $mpdf->WriteHTML($html);
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