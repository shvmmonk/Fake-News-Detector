<?php
require_once 'includes/db.php';

$NEWS_API_KEY = "f1ce9a13b90144c792da61a2d86fb45f";

$topics = ['fake news', 'misinformation', 'health', 'politics', 'technology'];
$added = 0;

foreach ($topics as $topic) {
    $url = "https://newsapi.org/v2/everything?q=" . urlencode($topic) . "&language=en&pageSize=5&apiKey=" . $NEWS_API_KEY;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if (!isset($data['articles'])) continue;

    foreach ($data['articles'] as $article) {
        $title   = $article['title']       ?? '';
        $content = $article['description'] ?? '';
        $author  = $article['author']      ?? 'Unknown';
        $url_art = $article['url']         ?? '';
        $date    = $article['publishedAt'] ?? date('Y-m-d H:i:s');

        if (!$title || !$content) continue;

        // Duplicate check
        $check = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE title = ?");
        $check->execute([$title]);
        if ($check->fetchColumn() > 0) continue;

        $stmt = $pdo->prepare("
            INSERT INTO articles (title, content, author, url, source_id, category_id, submitted_by, published_at)
            VALUES (?, ?, ?, ?, 1, 1, 1, ?)
        ");
        $stmt->execute([$title, $content, $author, $url_art, date('Y-m-d H:i:s', strtotime($date))]);
        $added++;
    }
}

echo "<h2 style='font-family:monospace;padding:20px;'>✅ $added new articles added to database!</h2>";
?>
