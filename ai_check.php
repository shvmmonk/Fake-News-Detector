<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'includes/db.php';

$GEMINI_API_KEY = "AIzaSyDiHXGevHBPnD2cg1GunY9fSdtxdXPJq74";

$article_id = intval($_POST['article_id'] ?? 0);
if (!$article_id) { echo json_encode(['error' => 'No article selected']); exit; }

$stmt = $pdo->prepare("SELECT title, content FROM articles WHERE article_id = ?");
$stmt->execute([$article_id]);
$article = $stmt->fetch();
if (!$article) { echo json_encode(['error' => 'Article not found']); exit; }

$prompt = "You are a fake news detector. Analyze this news article.
Title: " . $article['title'] . "
Content: " . $article['content'] . "
Respond ONLY in this JSON format:
{\"verdict\": \"fake\", \"confidence\": 90, \"reason\": \"explanation\"}
verdict must be: fake, real, or misleading";

$data = json_encode([
    "contents" => [["parts" => [["text" => $prompt]]]],
    "generationConfig" => ["temperature" => 0.1]
]);

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $GEMINI_API_KEY;

$options = [
    "http" => [
        "method"  => "POST",
        "header"  => "Content-Type: application/json\r\n",
        "content" => $data,
        "ignore_errors" => true
    ],
    "ssl" => [
        "verify_peer" => false,
        "verify_peer_name" => false
    ]
];

$context  = stream_context_create($options);
$response = file_get_contents($url, false, $context);

if (!$response) { echo json_encode(['error' => 'API call failed']); exit; }

$result = json_decode($response, true);

if (isset($result['error'])) { echo json_encode(['error' => $result['error']['message']]); exit; }

$text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
$text = preg_replace('/```json|```/', '', $text);
$text = trim($text);

$ai_result = json_decode($text, true);

if ($ai_result) {
    echo json_encode($ai_result);
} else {
    echo json_encode(['error' => 'Parse failed', 'raw' => $text]);
}
?>