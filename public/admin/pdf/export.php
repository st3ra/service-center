<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../vendor/autoload.php'; // mPDF + jpgraph

function log_pdf_error($msg) {
    file_put_contents(__DIR__ . '/../../../tmp/pdf_errors.log', date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
}
function safe($v) {
    if (is_array($v)) return implode(', ', array_map('safe', $v));
    return htmlspecialchars((string)$v);
}
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    log_pdf_error("PHP Error: $errstr in $errfile:$errline");
});
set_exception_handler(function($e) {
    log_pdf_error("Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
});

// Универсальная функция генерации графика через jpgraph
function buildGraph($type, $data, $labels = [], $title = '', $width = null, $height = null) {
    // Размеры по умолчанию для каждого типа
    if ($type === 'pie') {
        $width = $width ?? 700;
        $height = $height ?? 500;
    } else {
        $width = $width ?? 750;
        $height = $height ?? 450;
    }
    // Проверка на достаточность данных
    $numericData = array_filter($data, 'is_numeric');
    if (count($numericData) < 2) {
        return '<div style="color:gray">Недостаточно данных для построения графика</div><br><br>';
    }
    $file = tempnam(sys_get_temp_dir(), 'graph') . '.png';
    try {
        if ($type === 'pie') {
            $graph = new Amenadiel\JpGraph\Graph\PieGraph($width, $height);
            $graph->SetShadow();
            $graph->SetMargin(80,80,80,80);
            $p1 = new Amenadiel\JpGraph\Plot\PiePlot($data);
            if ($labels) $p1->SetLegends($labels);
            $p1->SetCenter(0.5, 0.5);
            $p1->SetSize(0.4);
            $graph->Add($p1);
            if ($title) $graph->title->Set($title);
            $graph->Stroke($file);
        } elseif ($type === 'bar') {
            $graph = new Amenadiel\JpGraph\Graph\Graph($width, $height);
            $graph->SetScale('textlin');
            $graph->SetMargin(80,40,40,120);
            $bar = new Amenadiel\JpGraph\Plot\BarPlot($data);
            $bar->SetFillColor('orange');
            $graph->Add($bar);
            if ($labels) $graph->xaxis->SetTickLabels($labels);
            if ($title) $graph->title->Set($title);
            $graph->xaxis->SetLabelAngle(60);
            $graph->Stroke($file);
        } elseif ($type === 'line') {
            $graph = new Amenadiel\JpGraph\Graph\Graph($width, $height);
            $graph->SetScale('textlin');
            $graph->SetMargin(80,40,40,120);
            $line = new Amenadiel\JpGraph\Plot\LinePlot($data);
            $line->SetColor('blue');
            $line->SetWeight(2);
            $graph->Add($line);
            if ($labels) $graph->xaxis->SetTickLabels($labels);
            if ($title) $graph->title->Set($title);
            $graph->xaxis->SetLabelAngle(60);
            $graph->Stroke($file);
        }
        $imgData = base64_encode(file_get_contents($file));
        unlink($file);
        return '<img src="data:image/png;base64,' . $imgData . '" style="max-width:100%;"><br><br>';
    } catch (\Throwable $e) {
        if (file_exists($file)) unlink($file);
        return '<div style="color:red">Ошибка генерации графика: ' . htmlspecialchars($e->getMessage()) . '</div><br><br>';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $date_from = $data['date_from'] ?? '';
    $date_to = $data['date_to'] ?? '';
    $period_str = 'Период: ';
    if ($date_from && $date_to) {
        $period_str .= safe($date_from) . ' — ' . safe($date_to);
    } elseif ($date_from) {
        $period_str .= 'с ' . safe($date_from);
    } elseif ($date_to) {
        $period_str .= 'до ' . safe($date_to);
    } else {
        $period_str .= 'всё время';
    }
    try {
        $mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ . '/../../../tmp']);
        $mpdf->SetTitle('Комплексный аналитический отчёт');
        $style = '<style>body, table, ul, ol { font-family: DejaVu Sans, Arial, sans-serif; }</style>';
        // --- Титульная страница ---
        $html = '<h1 style="text-align:center;">Комплексный аналитический отчёт</h1>';
        $html .= '<p style="text-align:center;">' . $period_str . '</p>';
        $html .= '<hr>';
        $mpdf->WriteHTML($style . $html);
        // --- Заявки ---
        $mpdf->AddPage();
        $html = '<h2>Аналитика по заявкам</h2>';
        if (!empty($data['requests'])) {
            $r = $data['requests'];
            $html .= '<ul>';
            $html .= '<li><b>Всего заявок:</b> ' . safe($r['total'] ?? '-') . '</li>';
            $html .= '<li><b>За неделю:</b> ' . safe($r['requestsWeek'] ?? '-') . '</li>';
            $html .= '<li><b>За месяц:</b> ' . safe($r['requestsMonth'] ?? '-') . '</li>';
            $html .= '</ul>';
            // Pie: распределение по статусам
            if (!empty($r['statusDistribution'])) {
                $html .= buildGraph('pie', array_values($r['statusDistribution']), $r['pieLabels'] ?? [], 'Распределение по статусам', null, null);
            }
            // Line: динамика по дням
            if (!empty($r['dailyStats'])) {
                $html .= buildGraph('line', array_column($r['dailyStats'], 'count'), array_column($r['dailyStats'], 'date'), 'Динамика по дням', null, null);
            }
            // Топ-5 дней
            if (!empty($r['topDays'])) {
                $html .= '<b>Топ-5 дней по количеству заявок:</b><br><table border="1" cellpadding="5" style="border-collapse:collapse;"><tr><th>Дата</th><th>Заявок</th></tr>';
                foreach ($r['topDays'] as $row) {
                    $html .= '<tr><td>' . safe($row['date']) . '</td><td>' . safe($row['count']) . '</td></tr>';
                }
                $html .= '</table>';
            }
        }
        $mpdf->WriteHTML($style . $html);
        // --- Услуги ---
        $mpdf->AddPage();
        $html = '<h2>Аналитика по услугам</h2>';
        if (!empty($data['services'])) {
            $s = $data['services'];
            $html .= '<ul>';
            $html .= '<li><b>Выручка:</b> ' . safe($s['revenue'] ?? '-') . '</li>';
            $html .= '<li><b>Услуг без заявок:</b> ' . (isset($s['servicesNoRequests']) ? count($s['servicesNoRequests']) : '-') . '</li>';
            $html .= '</ul>';
            // Bar: топ-10 услуг
            if (!empty($s['barChart'])) {
                $html .= buildGraph('bar', array_column($s['barChart'], 'requests_count'), array_column($s['barChart'], 'name'), 'Топ-10 услуг по заявкам', null, null);
            }
            // Pie: доля выручки по категориям
            if (!empty($s['pieChart'])) {
                $html .= buildGraph('pie', array_column($s['pieChart'], 'revenue'), array_column($s['pieChart'], 'category'), 'Доля выручки по категориям', null, null);
            }
            // Топ-5 услуг
            if (!empty($s['topServices'])) {
                $html .= '<b>Топ-5 услуг:</b><ul>';
                foreach ($s['topServices'] as $row) {
                    $html .= '<li>' . safe($row['name']) . ' (' . safe($row['count']) . ' заявок)</li>';
                }
                $html .= '</ul>';
            }
            // Средняя цена по категориям
            if (!empty($s['avgPrices'])) {
                $html .= '<b>Средняя цена по категориям:</b><ul>';
                foreach ($s['avgPrices'] as $row) {
                    $html .= '<li>' . safe($row['category']) . ': ' . safe($row['avg_price']) . '₽</li>';
                }
                $html .= '</ul>';
            }
            // Услуги без заявок (таблица)
            if (!empty($s['servicesNoRequests'])) {
                $html .= '<b>Услуги без заявок:</b><table border="1" cellpadding="5" style="border-collapse:collapse;"><tr><th>Название</th><th>Категория</th><th>Цена</th></tr>';
                foreach ($s['servicesNoRequests'] as $row) {
                    $html .= '<tr><td>' . safe($row['name']) . '</td><td>' . safe($row['category']) . '</td><td>' . safe($row['price']) . '</td></tr>';
                }
                $html .= '</table>';
            }
        }
        $mpdf->WriteHTML($style . $html);
        // --- Категории ---
        $mpdf->AddPage();
        $html = '<h2>Аналитика по категориям</h2>';
        if (!empty($data['categories'])) {
            $c = $data['categories'];
            // Bar: заявки по категориям
            if (!empty($c['barChart'])) {
                $html .= buildGraph('bar', array_column($c['barChart'], 'requests_count'), array_column($c['barChart'], 'name'), 'Заявки по категориям', null, null);
            }
            // Pie: распределение услуг по категориям
            if (!empty($c['pieChart'])) {
                $html .= buildGraph('pie', array_column($c['pieChart'], 'services_count'), array_column($c['pieChart'], 'name'), 'Распределение услуг по категориям', null, null);
            }
            $html .= '<b>Услуги по категориям:</b><ul>';
            if (!empty($c['servicesByCategory'])) {
                foreach ($c['servicesByCategory'] as $row) {
                    $html .= '<li>' . safe($row['name']) . ': ' . safe($row['services_count']) . '</li>';
                }
            }
            $html .= '</ul>';
            $html .= '<b>Заявки по категориям:</b><ul>';
            if (!empty($c['requestsByCategory'])) {
                foreach ($c['requestsByCategory'] as $row) {
                    $html .= '<li>' . safe($row['name']) . ': ' . safe($row['requests_count']) . '</li>';
                }
            }
            $html .= '</ul>';
            // Категории с малым количеством заявок
            if (!empty($c['fewRequests'])) {
                $html .= '<b>Категории с малым количеством заявок:</b><table border="1" cellpadding="5" style="border-collapse:collapse;"><tr><th>Категория</th><th>Услуг</th><th>Заявок</th></tr>';
                foreach ($c['fewRequests'] as $row) {
                    $html .= '<tr><td>' . safe($row['name']) . '</td><td>' . safe($row['services_count']) . '</td><td>' . safe($row['requests_count']) . '</td></tr>';
                }
                $html .= '</table>';
            }
        }
        $mpdf->WriteHTML($style . $html);
        // --- Пользователи ---
        $mpdf->AddPage();
        $html = '<h2>Аналитика по пользователям</h2>';
        if (!empty($data['users'])) {
            $u = $data['users'];
            // Bar: активность сотрудников (комментарии)
            if (!empty($u['topStaff'])) {
                $html .= buildGraph('bar', array_column($u['topStaff'], 'comments_count'), array_column($u['topStaff'], 'name'), 'Топ-5 сотрудников по комментариям', null, null);
            }
            $html .= '<ul>';
            $html .= '<li><b>Уникальных клиентов:</b> ' . safe($u['uniqueClients'] ?? '-') . '</li>';
            $html .= '<li><b>Активность сотрудников (комментарии):</b> ' . safe($u['staffComments'] ?? '-') . '</li>';
            $html .= '<li><b>Заявок обработано сотрудниками:</b> ' . safe($u['staffRequests'] ?? '-') . '</li>';
            $html .= '</ul>';
            // Топ-5 клиентов
            if (!empty($u['topClients'])) {
                $html .= '<b>Топ-5 клиентов:</b><ul>';
                foreach ($u['topClients'] as $row) {
                    $html .= '<li>' . safe($row['name']) . ' (' . safe($row['count']) . ' заявок)</li>';
                }
                $html .= '</ul>';
            }
            // Топ-5 сотрудников (по заявкам)
            if (!empty($u['topStaff'])) {
                $html .= '<b>Топ-5 сотрудников:</b><table border="1" cellpadding="5" style="border-collapse:collapse;"><tr><th>Сотрудник</th><th>Заявок</th><th>Комментариев</th></tr>';
                foreach ($u['topStaff'] as $row) {
                    $html .= '<tr><td>' . safe($row['name']) . '</td><td>' . safe($row['requests_handled']) . '</td><td>' . safe($row['comments_count']) . '</td></tr>';
                }
                $html .= '</table>';
            }
        }
        $mpdf->WriteHTML($style . $html);
        // --- Отзывы ---
        $mpdf->AddPage();
        $html = '<h2>Аналитика по отзывам</h2>';
        if (!empty($data['reviews'])) {
            $r = $data['reviews'];
            // Line: динамика отзывов по неделям
            if (!empty($r['weeklyStats'])) {
                $html .= buildGraph('line', array_column($r['weeklyStats'], 'count'), array_column($r['weeklyStats'], 'week'), 'Динамика отзывов по неделям', null, null);
            }
            $html .= '<ul>';
            $html .= '<li><b>Всего отзывов:</b> ' . safe($r['total'] ?? '-') . '</li>';
            $html .= '<li><b>За период:</b> ' . safe($r['countPeriod'] ?? '-') . '</li>';
            $html .= '<li><b>Средняя длина:</b> ' . safe($r['avgLength'] ?? '-') . '</li>';
            $html .= '</ul>';
            // Последние отзывы
            if (!empty($r['lastReviews'])) {
                $html .= '<b>Последние отзывы:</b><table border="1" cellpadding="5" style="border-collapse:collapse;"><tr><th>Автор</th><th>Текст</th><th>Дата</th></tr>';
                foreach ($r['lastReviews'] as $row) {
                    $html .= '<tr><td>' . safe($row['author']) . '</td><td>' . safe($row['text']) . '</td><td>' . safe($row['created_at']) . '</td></tr>';
                }
                $html .= '</table>';
            }
        }
        $mpdf->WriteHTML($style . $html);
        // --- Финансы ---
        $mpdf->AddPage();
        $html = '<h2>Финансовая аналитика</h2>';
        if (!empty($data['finance'])) {
            $f = $data['finance'];
            // Line: динамика выручки по месяцам
            if (!empty($f['monthlyRevenue'])) {
                $html .= buildGraph('line', array_column($f['monthlyRevenue'], 'revenue'), array_column($f['monthlyRevenue'], 'ym'), 'Динамика выручки по месяцам', null, null);
            }
            // Pie: доля выручки по категориям
            if (!empty($f['categoryRevenue'])) {
                $html .= buildGraph('pie', array_column($f['categoryRevenue'], 'revenue'), array_column($f['categoryRevenue'], 'category'), 'Доля выручки по категориям', null, null);
            }
            $html .= '<ul>';
            $html .= '<li><b>Общая выручка:</b> ' . safe($f['totalRevenue'] ?? '-') . '</li>';
            $html .= '<li><b>Средний чек:</b> ' . safe($f['avgCheck'] ?? '-') . '</li>';
            $html .= '</ul>';
        }
        $mpdf->WriteHTML($style . $html);
        // --- Сезонность и тренды ---
        $mpdf->AddPage();
        $html = '<h2>Сезонность и тренды</h2>';
        if (!empty($data['trends'])) {
            $t = $data['trends'];
            // Line: заявки по месяцам
            if (!empty($t['monthlyStats'])) {
                $html .= buildGraph('line', array_column($t['monthlyStats'], 'count'), array_column($t['monthlyStats'], 'ym'), 'Заявки по месяцам', null, null);
            }
            // Bar: топ-5 услуг по сезонам (например, зима)
            if (!empty($t['topServicesBySeason']['winter'])) {
                $barData = array_column($t['topServicesBySeason']['winter'], 'cnt');
                $barLabels = array_column($t['topServicesBySeason']['winter'], 'name');
                $html .= buildGraph('bar', $barData, $barLabels, 'Топ-5 услуг (зима)', null, null);
            }
            // Топ-3 месяца
            if (!empty($t['topMonths'])) {
                $html .= '<b>Топ-3 месяца по заявкам:</b><ul>';
                foreach ($t['topMonths'] as $row) {
                    $html .= '<li>' . safe($row['ym'] ?? '-') . ': ' . safe($row['count'] ?? '-') . '</li>';
                }
                $html .= '</ul>';
            }
            // Топ-5 услуг по сезонам (выводим только winter для примера)
            if (!empty($t['topServicesBySeason']['winter'])) {
                $html .= '<b>Топ-5 услуг (зима):</b><ul>';
                foreach ($t['topServicesBySeason']['winter'] as $row) {
                    $html .= '<li>' . safe($row['name']) . ' (' . safe($row['cnt']) . ' заявок)</li>';
                }
                $html .= '</ul>';
            }
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