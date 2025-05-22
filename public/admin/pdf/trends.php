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
    $topMonths = $data['topMonths'] ?? [];
    $topServices = $data['topServices'] ?? [];
    $lineImg = $data['lineImg'] ?? '';
    $barImg = $data['barImg'] ?? '';
    $date_from = $data['date_from'] ?? '';
    $date_to = $data['date_to'] ?? '';
    $selectedSeason = $data['selectedSeason'] ?? '';
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
        $mpdf->SetTitle('Сезонность и тренды');
        $style = '<style>body, table, ul, ol { font-family: DejaVu Sans, Arial, sans-serif; }</style>';

        $html = '<h1 style="text-align:center;">Сезонность и тренды</h1>';
        $html .= '<p style="text-align:center;">' . $period_str . '</p>';
        $html .= '<p style="text-align:center;"><b>Выбранный сезон:</b> ' . htmlspecialchars($selectedSeason) . '</p>';
        $html .= '<hr>';
        $html .= '<h2>Топ-3 месяца по заявкам</h2>';
        $html .= '<ul>';
        if (count($topMonths) === 0) {
            $html .= '<li>Нет данных</li>';
        } else {
            foreach ($topMonths as $m) {
                $html .= '<li>' . htmlspecialchars($m) . '</li>';
            }
        }
        $html .= '</ul>';
        $html .= '<h2>Топ-5 услуг по сезону</h2>';
        $html .= '<ul>';
        if (count($topServices) === 0) {
            $html .= '<li>Нет данных</li>';
        } else {
            foreach ($topServices as $s) {
                $html .= '<li>' . htmlspecialchars($s) . '</li>';
            }
        }
        $html .= '</ul>';
        $html .= '<h2>Графики</h2>';
        if ($lineImg) {
            $html .= '<div><b>Заявки по месяцам:</b><br><img src="' . $lineImg . '" style="max-width:600px;"></div>';
        }
        if ($barImg) {
            $html .= '<div style="margin-top:20px;"><b>Топ-5 услуг по сезону:</b><br><img src="' . $barImg . '" style="max-width:600px;"></div>';
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