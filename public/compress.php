<?php
ini_set('memory_limit', '5120M'); // Увеличиваем лимит памяти
// Папка с изображениями
$dir = __DIR__ . '/images/services';
// Максимальная ширина (px)
$maxWidth = 1200;
// Качество JPG (0-100)
$jpgQuality = 80;

function compress_images($dir, $maxWidth, $jpgQuality) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    $files = [];
    foreach ($rii as $file) {
        if ($file->isDir()) continue;
        $ext = strtolower($file->getExtension());
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $files[] = $file->getPathname();
        }
    }
    $results = [];
    foreach ($files as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $msg = "<b>Обработка:</b> $file ... ";
        if ($ext === 'jpg' || $ext === 'jpeg') {
            $img = @imagecreatefromjpeg($file);
            if (!$img) { $results[] = $msg . '<span style="color:red">Ошибка чтения JPG</span>'; continue; }
            $width = imagesx($img);
            $height = imagesy($img);
            if ($width > $maxWidth) {
                $newHeight = intval($height * $maxWidth / $width);
                $resized = imagecreatetruecolor($maxWidth, $newHeight);
                imagecopyresampled($resized, $img, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
                imagedestroy($img);
                $img = $resized;
            }
            imagejpeg($img, $file, $jpgQuality);
            imagedestroy($img);
            $results[] = $msg . '<span style="color:green">OK</span>';
        } elseif ($ext === 'png') {
            $img = @imagecreatefrompng($file);
            if (!$img) { $results[] = $msg . '<span style="color:red">Ошибка чтения PNG</span>'; continue; }
            $width = imagesx($img);
            $height = imagesy($img);
            if ($width > $maxWidth) {
                $newHeight = intval($height * $maxWidth / $width);
                $resized = imagecreatetruecolor($maxWidth, $newHeight);
                // Сохраняем прозрачность
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                imagecopyresampled($resized, $img, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
                imagedestroy($img);
                $img = $resized;
            }
            imagepng($img, $file, 6);
            imagedestroy($img);
            $results[] = $msg . '<span style="color:green">OK</span>';
        }
    }
    return $results;
}

?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Сжатие изображений</title>
    <style>body{font-family:sans-serif;max-width:700px;margin:2em auto;} .ok{color:green;} .err{color:red;} pre{background:#f8f8f8;padding:1em;}</style>
</head>
<body>
<h1>Сжатие изображений в /images/services</h1>
<form method="post">
    <button type="submit">Сжать все изображения</button>
</form>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo '<h2>Результат:</h2><pre>';
    $results = compress_images($dir, $maxWidth, $jpgQuality);
    foreach ($results as $line) {
        echo $line . "\n";
    }
    echo '</pre>';
}
?>
</body>
</html>
