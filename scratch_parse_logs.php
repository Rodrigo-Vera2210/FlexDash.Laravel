<?php

$logFile = 'C:\\Users\\RodrigoVera\\.gemini\\antigravity-ide\\brain\\5c6ae7fc-ce66-46fc-ac47-c8aa77b960e4\\.system_generated\\logs\\transcript.jsonl';

if (!file_exists($logFile)) {
    echo "Log file not found!\n";
    exit(1);
}

$handle = fopen($logFile, 'r');
while (($line = fgets($handle)) !== false) {
    $data = json_decode($line, true);
    if ($data && isset($data['step_index']) && $data['step_index'] >= 1544 && $data['step_index'] <= 1548) {
        echo "Step: " . $data['step_index'] . " | Type: " . $data['type'] . " | Status: " . $data['status'] . "\n";
        echo "JSON: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        echo "--------------------------------------------------\n";
    }
}
fclose($handle);
