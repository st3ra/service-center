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

function safe($v) {
    if (is_array($v)) return implode(', ', array_map('safe', $v));
    return htmlspecialchars((string)$v);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        http_response_code(400);
        echo 'Некорректные данные';
        exit;
    }
    $revenue = $data['revenue'] ?? '';
    $servicesNoRequests = $data['servicesNoRequests'] ?? '';
    $topServices = $data['topServices'] ?? [];
    $avgPrices = $data['avgPrices'] ?? [];
    $barImg = $data['barImg'] ?? '';
    $pieImg = $data['pieImg'] ?? '';
    $date_from = $data['date_from'] ?? '';
    $date_to = $data['date_to'] ?? '';
    $selectedCategories = $data['selectedCategories'] ?? [];
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
        $mpdf->SetTitle('Аналитика по услугам');

        $style = '<style>body, table, ul, ol { font-family: DejaVu Sans, Arial, sans-serif; }</style>';

        $html = '<h1 style="text-align:center;">Аналитика по услугам</h1>';
        $html .= '<p style="text-align:center;">' . $period_str;
        if (is_array($selectedCategories) && count($selectedCategories) > 0) {
            $html .= '<br>Категории: ' . safe(implode(', ', $selectedCategories));
        }
        $html .= '</p>';
        $html .= '<hr>';
        $html .= '<h2>Ключевые метрики</h2>';
        $html .= '<ul>';
        $html .= '<li><b>Выручка (завершённые заявки):</b> ' . safe($revenue) . '₽</li>';
        $html .= '<li><b>Услуг без заявок:</b> ' . (is_array($servicesNoRequests) ? count($servicesNoRequests) : safe($servicesNoRequests)) . '</li>';
        $html .= '</ul>';
        $html .= '<h2>Топ-5 популярных услуг</h2>';
        $html .= '<ol>';
        if (count($topServices) === 0) {
            $html .= '<li>Нет данных</li>';
        } else {
            foreach ($topServices as $row) {
                $html .= '<li>' . safe($row['name']) . ' <span style="color:#888;">(' . safe($row['count']) . ')</span></li>';
            }
        }
        $html .= '</ol>';
        $html .= '<h2>Средняя цена по категориям</h2>';
        $html .= '<ul>';
        if (count($avgPrices) === 0) {
            $html .= '<li>Нет данных</li>';
        } else {
            foreach ($avgPrices as $row) {
                $html .= '<li>' . safe($row['category']) . ': <b>' . safe($row['avg_price']) . '₽</b></li>';
            }
        }
        $html .= '</ul>';
        $html .= '<h2>Графики</h2>';
        if ($barImg) {
            $html .= '<div><b>Топ-10 услуг по заявкам:</b><br><img src="' . safe($barImg) . '" style="max-width:600px;"></div>';
        }
        if ($pieImg) {
            $html .= '<div style="margin-top:20px;"><b>Доля выручки по категориям:</b><br><img src="' . safe($pieImg) . '" style="max-width:400px;"></div>';
        }
        $html .= '<h2>Услуги без заявок</h2>';
        if (is_array($servicesNoRequests) && !empty($servicesNoRequests)) {
            $html .= '<table border="1" cellpadding="6" style="border-collapse:collapse; width:90%;"><thead><tr><th>Название</th><th>Категория</th><th>Цена</th></tr></thead><tbody>';
            foreach ($servicesNoRequests as $row) {
                $html .= '<tr><td>' . safe($row['name']) . '</td><td>' . safe($row['category']) . '</td><td>' . safe($row['price']) . '</td></tr>';
            }
            $html .= '</tbody></table>';
        } else {
            $html .= '<p>Нет услуг без заявок за выбранный период.</p>';
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