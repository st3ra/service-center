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
    $servicesByCategory = $data['servicesByCategory'] ?? [];
    $requestsByCategory = $data['requestsByCategory'] ?? [];
    $barImg = $data['barImg'] ?? '';
    $pieImg = $data['pieImg'] ?? '';
    $fewRequests = $data['fewRequests'] ?? [];
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
        $mpdf->SetTitle('Аналитика по категориям');
        $style = '<style>body, table, ul, ol { font-family: DejaVu Sans, Arial, sans-serif; }</style>';

        $html = '<h1 style="text-align:center;">Аналитика по категориям</h1>';
        $html .= '<p style="text-align:center;">' . $period_str . '</p>';
        $html .= '<hr>';
        $html .= '<h2>Услуг в категориях</h2>';
        $html .= '<ul>';
        if (count($servicesByCategory) === 0) {
            $html .= '<li>Нет данных</li>';
        } else {
            foreach ($servicesByCategory as $row) {
                $html .= '<li>' . htmlspecialchars($row['name']) . ': <b>' . htmlspecialchars($row['services_count']) . '</b></li>';
            }
        }
        $html .= '</ul>';
        $html .= '<h2>Заявок по категориям</h2>';
        $html .= '<ul>';
        if (count($requestsByCategory) === 0) {
            $html .= '<li>Нет данных</li>';
        } else {
            foreach ($requestsByCategory as $row) {
                $html .= '<li>' . htmlspecialchars($row['name']) . ': <b>' . htmlspecialchars($row['requests_count']) . '</b></li>';
            }
        }
        $html .= '</ul>';
        $html .= '<h2>Графики</h2>';
        if ($barImg) {
            $html .= '<div><b>Заявки по категориям:</b><br><img src="' . $barImg . '" style="max-width:600px;"></div>';
        }
        if ($pieImg) {
            $html .= '<div style="margin-top:20px;"><b>Распределение услуг по категориям:</b><br><img src="' . $pieImg . '" style="max-width:400px;"></div>';
        }
        $html .= '<h2 style="margin-top:30px;">Категории без услуг или с <5 заявок</h2>';
        $html .= '<table border="1" cellpadding="6" style="border-collapse:collapse; width:80%;"><thead><tr><th>Категория</th><th>Услуг</th><th>Заявок</th></tr></thead><tbody>';
        if (count($fewRequests) === 0) {
            $html .= '<tr><td colspan="3" style="text-align:center;">Нет данных</td></tr>';
        } else {
            foreach ($fewRequests as $row) {
                $html .= '<tr><td>' . htmlspecialchars($row['name']) . '</td><td>' . htmlspecialchars($row['services_count']) . '</td><td>' . htmlspecialchars($row['requests_count']) . '</td></tr>';
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