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
    $uniqueClients = $data['uniqueClients'] ?? '';
    $topClients = $data['topClients'] ?? [];
    $staffComments = $data['staffComments'] ?? '';
    $staffRequests = $data['staffRequests'] ?? '';
    $topClientsTable = $data['topClientsTable'] ?? [];
    $topStaff = $data['topStaff'] ?? [];
    $staffBarImg = $data['staffBarImg'] ?? '';
    $staffRequestsBarImg = $data['staffRequestsBarImg'] ?? '';
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
        $mpdf->SetTitle('Аналитика по активности пользователей');
        $style = '<style>body, table, ul, ol { font-family: DejaVu Sans, Arial, sans-serif; }</style>';

        $html = '<h1 style="text-align:center;">Аналитика по активности пользователей</h1>';
        $html .= '<p style="text-align:center;">' . $period_str . '</p>';
        $html .= '<hr>';
        $html .= '<h2>Ключевые метрики</h2>';
        $html .= '<ul>';
        $html .= '<li><b>Уникальных клиентов:</b> ' . htmlspecialchars($uniqueClients) . '</li>';
        $html .= '<li><b>Активность сотрудников (комментарии):</b> ' . htmlspecialchars($staffComments) . '</li>';
        $html .= '<li><b>Заявок обработано сотрудниками:</b> ' . htmlspecialchars($staffRequests) . '</li>';
        $html .= '</ul>';
        $html .= '<h2>Топ-5 клиентов</h2>';
        $html .= '<ol>';
        if (count($topClients) === 0) {
            $html .= '<li>Нет данных</li>';
        } else {
            foreach ($topClients as $row) {
                $html .= '<li>' . htmlspecialchars($row['name']) . ' <span style="color:#888;">(' . htmlspecialchars($row['count']) . ')</span></li>';
            }
        }
        $html .= '</ol>';
        $html .= '<h2>Таблица топ-5 клиентов</h2>';
        $html .= '<table border="1" cellpadding="6" style="border-collapse:collapse; width:80%;"><thead><tr><th>Клиент</th><th>Заявок</th><th>Сумма</th></tr></thead><tbody>';
        if (count($topClientsTable) === 0) {
            $html .= '<tr><td colspan="3" style="text-align:center;">Нет данных</td></tr>';
        } else {
            foreach ($topClientsTable as $row) {
                $html .= '<tr><td>' . htmlspecialchars($row['name']) . '</td><td>' . htmlspecialchars($row['requests_count']) . '</td><td>' . htmlspecialchars($row['total_sum']) . '</td></tr>';
            }
        }
        $html .= '</tbody></table>';
        $html .= '<h2>Топ-5 сотрудников</h2>';
        $html .= '<table border="1" cellpadding="6" style="border-collapse:collapse; width:80%;"><thead><tr><th>Сотрудник</th><th>Заявок</th><th>Комментариев</th></tr></thead><tbody>';
        if (count($topStaff) === 0) {
            $html .= '<tr><td colspan="3" style="text-align:center;">Нет данных</td></tr>';
        } else {
            foreach ($topStaff as $row) {
                $html .= '<tr><td>' . htmlspecialchars($row['name']) . '</td><td>' . htmlspecialchars($row['requests_handled']) . '</td><td>' . htmlspecialchars($row['comments_count']) . '</td></tr>';
            }
        }
        $html .= '</tbody></table>';
        $html .= '<h2>Графики</h2>';
        if ($staffBarImg) {
            $html .= '<div><b>Комментарии по сотрудникам:</b><br><img src="' . $staffBarImg . '" style="max-width:600px;"></div>';
        }
        if ($staffRequestsBarImg) {
            $html .= '<div style="margin-top:20px;"><b>Заявки по сотрудникам:</b><br><img src="' . $staffRequestsBarImg . '" style="max-width:600px;"></div>';
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