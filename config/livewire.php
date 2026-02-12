<?php

$config = require base_path('vendor/livewire/livewire/config/livewire.php');

$maxUploadKilobytes = (int) env('LIVEWIRE_UPLOAD_MAX_KB', 1012000);

if ($maxUploadKilobytes <= 0) {
    $maxUploadKilobytes = 1012000;
}

$maxUploadMinutes = (int) env('LIVEWIRE_UPLOAD_MAX_MINUTES', 30);

if ($maxUploadMinutes <= 0) {
    $maxUploadMinutes = 30;
}

$config['temporary_file_upload']['rules'] = ['required', 'file', 'max:'.$maxUploadKilobytes];
$config['temporary_file_upload']['max_upload_time'] = $maxUploadMinutes;
$config['temporary_file_upload']['middleware'] = 'throttle:120,1';

return $config;
