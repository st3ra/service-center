<?php
   require_once __DIR__ . '/../vendor/autoload.php';
   $mpdf = new \Mpdf\Mpdf([
       'tempDir' => __DIR__ . '/../tmp'
   ]);
   $mpdf->WriteHTML('<h1>mPDF работает!</h1>');
   $mpdf->Output();