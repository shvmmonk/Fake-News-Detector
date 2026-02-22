<?php
require_once 'includes/db.php';

$GROQ_API_KEY = "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";

$article_id = intval($_POST['article_id'] ?? 0);
if (!$article_id) { echo json_encode(['error' => 'No article selected']); exit; }

$stmt = $pdo->prepare("SELECT title, content FROM articles WHERE article_id = ?");
$stmt->execute([$article_id]);
$article = $stmt->fetch();
if (!$article) { echo json_encode(['error' => 'Article not found']); exit; }

$prompt = "You are a fake news detector. Analyze this news article and respond ONLY in JSON.
Title: " . $article['title'] . "
Content: " . $article['content'] . "
Respond ONLY in this exact JSON format with no extra text:
{\"verdict\": \"fake\", \"confidence\": 90, \"reason\": \"explanation here\"}
verdict must be one of: fake, real, misleading";

$data = json_encode([
    "model" => "llama-3.3-70b-versatile",
    "messages" => [
        ["role" => "user", "content" => $prompt]
    ],
    "temperature" => 0.1
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.groq.com/openai/v1/chat/completions");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $GROQ_API_KEY
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) { echo json_encode(['error' => 'Connection failed: ' . $curl_error]); exit; }

$result = json_decode($response, true);

if (isset($result['error'])) { echo json_encode(['error' => $result['error']['message']]); exit; }

$text = $result['choices'][0]['message']['content'] ?? '';
$text = preg_replace('/```json|```/', '', $text);
$text = trim($text);

$ai_result = json_decode($text, true);

if ($ai_result) {
    echo json_encode($ai_result);
} else {
    echo json_encode(['error' => 'Parse failed', 'raw' => $text]);
}
?>
