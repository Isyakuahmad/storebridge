<?php
$uploadDir = getenv('UPLOAD_DIR') ?: dirname(__DIR__) . '/uploads';
$uploadUrl = rtrim(getenv('UPLOAD_URL') ?: '/uploads', '/');

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
