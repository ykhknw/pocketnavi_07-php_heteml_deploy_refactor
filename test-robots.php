<?php
/**
 * Robots.txt テスト・検証エンドポイント
 */

require_once 'src/Utils/RobotsTxtValidator.php';

// robots.txtファイルの読み込み
$robotsFile = 'robots.txt';
if (!file_exists($robotsFile)) {
    http_response_code(404);
    echo "Robots.txt file not found";
    exit;
}

$robotsContent = file_get_contents($robotsFile);

// 検証実行
$validation = RobotsTxtValidator::validateRobotsTxt($robotsContent);
$analysis = RobotsTxtValidator::analyzeRobotsTxt($robotsContent);
$recommendations = RobotsTxtValidator::checkRecommendations($robotsContent);

// JSON形式で結果を出力
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'validation' => $validation,
    'analysis' => $analysis,
    'recommendations' => $recommendations,
    'file_size' => strlen($robotsContent),
    'line_count' => substr_count($robotsContent, "\n") + 1
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
