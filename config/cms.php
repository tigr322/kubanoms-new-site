<?php

$maxFileUploadKilobytes = (int) env('CMS_FILE_UPLOAD_MAX_KB', 1012000);

if ($maxFileUploadKilobytes <= 0) {
    $maxFileUploadKilobytes = 1012000;
}

return [
    'file_upload_max_kb' => $maxFileUploadKilobytes,
];
