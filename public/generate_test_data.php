<?php
// Настройки
$workersCount = 100;
$clientsCount = 500;
$minRequestsPerClient = 15;
$maxRequestsPerClient = 30;
$minCommentsPerRequest = 1;
$maxCommentsPerRequest = 3;
$startDate = strtotime('2024-05-22');
$endDate = strtotime('2025-05-22');
$passwordHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

// ID услуг (предполагается, что они уже есть в БД, всего 50)
$serviceIds = range(1, 50);

$file = fopen('test_data.sql', 'w');
if (!$file) {
    die("Не удалось создать файл test_data.sql\n");
}

// Устанавливаем кодировку
fwrite($file, "SET NAMES 'utf8mb4';\n\n");

fwrite($file, "-- Работники\n");
for ($i = 0; $i < $workersCount; $i++) {
    $id = $i + 2; // id работников: 2..101
    $name = "Работник $id";
    $phone = "9000000" . str_pad($id, 4, "0", STR_PAD_LEFT);
    $email = "worker$id@example.com";
    fwrite($file, "INSERT INTO users (id, name, phone, email, password, role) VALUES ($id, '$name', '$phone', '$email', '$passwordHash', 'worker');\n");
}

fwrite($file, "\n-- Клиенты\n");
for ($i = 0; $i < $clientsCount; $i++) {
    $id = $i + 2 + $workersCount; // id клиентов: 102..601
    $name = "Клиент $id";
    $phone = "9110000" . str_pad($id, 4, "0", STR_PAD_LEFT);
    $email = "client$id@example.com";
    fwrite($file, "INSERT INTO users (id, name, phone, email, password, role) VALUES ($id, '$name', '$phone', '$email', '$passwordHash', 'client');\n");
}

$requests = [];
$requestId = 1;
$now = strtotime('2025-05-22');
$twoMonthsAgo = strtotime('-2 months', $now);
$oneWeekAgo = strtotime('-1 week', $now);

fwrite($file, "\n-- Заявки\n");
for ($i = 0; $i < $clientsCount; $i++) {
    $clientId = $i + 2 + $workersCount; // id клиента
    $clientName = "Клиент $clientId";
    $clientPhone = "9110000" . str_pad($clientId, 4, "0", STR_PAD_LEFT);
    $clientEmail = "client$clientId@example.com";
    $requestsCount = rand($minRequestsPerClient, $maxRequestsPerClient);
    for ($j = 0; $j < $requestsCount; $j++) {
        $serviceId = $serviceIds[array_rand($serviceIds)];
        $desc = "Описание проблемы №$requestId";
        // Генерируем дату заявки
        $createdAtTs = rand($startDate, $endDate);
        $createdAt = date('Y-m-d H:i:s', $createdAtTs);

        // Логика статусов для реалистичной аналитики
        if ($createdAtTs >= $oneWeekAgo) {
            // Последняя неделя: 60% new, 30% in_progress, 10% completed
            $r = rand(1, 100);
            if ($r <= 60) $status = "new";
            elseif ($r <= 90) $status = "in_progress";
            else $status = "completed";
        } elseif ($createdAtTs >= $twoMonthsAgo) {
            // Последние 2 месяца: 20% new, 40% in_progress, 40% completed
            $r = rand(1, 100);
            if ($r <= 20) $status = "new";
            elseif ($r <= 60) $status = "in_progress";
            else $status = "completed";
        } else {
            // Всё остальное: 100% completed
            $status = "completed";
        }

        fwrite($file, "INSERT INTO requests (user_id, name, phone, email, service_id, description, status, created_at) VALUES ($clientId, '$clientName', '$clientPhone', '$clientEmail', $serviceId, '$desc', '$status', '$createdAt');\n");
        $requests[] = $requestId;
        $requestId++;
    }
}

// Комментарии к заявкам
fwrite($file, "\n-- Комментарии\n");
foreach ($requests as $reqId) {
    $commentsCount = rand($minCommentsPerRequest, $maxCommentsPerRequest);
    for ($k = 0; $k < $commentsCount; $k++) {
        $workerId = rand(2, 1 + $workersCount); // id работников: 2..101
        $commentText = "Комментарий работника $workerId к заявке $reqId";
        $createdAt = date('Y-m-d H:i:s', rand($startDate, $endDate));
        fwrite($file, "INSERT INTO request_comments (request_id, user_id, comment, created_at) VALUES ($reqId, $workerId, '$commentText', '$createdAt');\n");
    }
}

fclose($file);
echo "Файл test_data.sql успешно создан!\n";
?>
