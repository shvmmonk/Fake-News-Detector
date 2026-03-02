<?php
/**
 * image_proxy.php — Real Image Fetcher for FakeGuard
 *
 * HOW IT WORKS:
 *   1. Stored image_url in DB (from NewsAPI scraper)  → fastest
 *   2. Scrape og:image from the article's real URL    → most accurate
 *   3. Unsplash keyword search                        → decent fallback
 *   4. Category SVG placeholder                       → never breaks
 *
 * Usage: image_proxy.php?id=ARTICLE_ID&q=KEYWORDS&w=800&h=500
 */

$id = intval($_GET['id'] ?? 0);
$q  = trim($_GET['q']    ?? 'news');
$w  = intval($_GET['w']  ?? 800);
$h  = intval($_GET['h']  ?? 500);

$w = min(max($w, 100), 1200);
$h = min(max($h, 100), 800);
$q = preg_replace('/[^a-zA-Z0-9 ,]/', ' ', $q);
$q = trim(preg_replace('/\s+/', ' ', $q));

// ── Cache ─────────────────────────────────────────────────────
$cacheDir = __DIR__ . '/img_cache/';
if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);

$cacheKey  = 'art_' . $id . '_' . md5($q . $w . $h);
$cacheFile = $cacheDir . $cacheKey;
$metaFile  = $cacheDir . $cacheKey . '.meta';

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 86400 * 7) {
    $mime = file_exists($metaFile) ? file_get_contents($metaFile) : 'image/jpeg';
    header('Content-Type: ' . trim($mime));
    header('Cache-Control: public, max-age=604800');
    header('X-FG-Cache: HIT');
    readfile($cacheFile);
    exit;
}

// ── Helper: curl fetch ────────────────────────────────────────
function fg_fetch(string $url, int $timeout = 12, bool $html = false): array {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; FakeGuardBot/2.0; +https://fakeguard.local)',
        CURLOPT_HTTPHEADER     => $html
            ? ['Accept: text/html,application/xhtml+xml','Accept-Language: en-US,en']
            : ['Accept: image/webp,image/jpeg,image/png,image/*'],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $mime = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    return ['body' => $body ?: '', 'code' => (int)$code, 'mime' => (string)$mime];
}

// ── Helper: parse og:image from HTML ─────────────────────────
function fg_og_image(string $html): string {
    // og:image (both attribute orders)
    if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/', $html, $m)) return $m[1];
    if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/', $html, $m)) return $m[1];
    // twitter:image
    if (preg_match('/<meta[^>]+name=["\']twitter:image(?::src)?["\'][^>]+content=["\']([^"\']+)["\']/', $html, $m)) return $m[1];
    if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']twitter:image/', $html, $m)) return $m[1];
    // first large <img> in article body (skip logos/icons)
    if (preg_match_all('/<img[^>]+src=["\']([^"\']{20,})["\'][^>]*/i', $html, $ms)) {
        foreach ($ms[1] as $src) {
            $sl = strtolower($src);
            if (preg_match('/\.(jpg|jpeg|png|webp)/i', $sl)
                && !str_contains($sl, 'logo') && !str_contains($sl, 'icon')
                && !str_contains($sl, 'avatar') && !str_contains($sl, 'sprite')) {
                return $src;
            }
        }
    }
    return '';
}

// ── Helper: resolve relative image URL ───────────────────────
function fg_resolve(string $imgUrl, string $pageUrl): string {
    if (str_starts_with($imgUrl, 'http')) return $imgUrl;
    if (str_starts_with($imgUrl, '//'))   return 'https:' . $imgUrl;
    if (str_starts_with($imgUrl, '/')) {
        $p = parse_url($pageUrl);
        return ($p['scheme'] ?? 'https') . '://' . ($p['host'] ?? '') . $imgUrl;
    }
    return $imgUrl;
}

// ── Helper: validate downloaded image ────────────────────────
function fg_valid_image(string $data, string $mime): bool {
    return strlen($data) > 3000 && str_starts_with($mime, 'image/');
}

// ─────────────────────────────────────────────────────────────
// STEP 1 — DB lookup: stored image_url + article URL
// ─────────────────────────────────────────────────────────────
$articleUrl   = '';
$storedImgUrl = '';

if ($id > 0) {
    try {
        require_once __DIR__ . '/includes/db.php';
        $stmt = $pdo->prepare("SELECT url, image_url FROM articles WHERE article_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $articleUrl   = $row['url']       ?? '';
            $storedImgUrl = $row['image_url'] ?? '';
        }
    } catch (Exception $e) {}
}

$imageData = null;
$mimeType  = 'image/jpeg';

// ─────────────────────────────────────────────────────────────
// STEP 2 — Use stored image_url (from NewsAPI urlToImage)
// ─────────────────────────────────────────────────────────────
if (!$imageData && !empty($storedImgUrl) && filter_var($storedImgUrl, FILTER_VALIDATE_URL)) {
    $r = fg_fetch($storedImgUrl, 10);
    if (fg_valid_image($r['body'], $r['mime'])) {
        $imageData = $r['body'];
        $mimeType  = explode(';', $r['mime'])[0];
    }
}

// ─────────────────────────────────────────────────────────────
// STEP 3 — Scrape og:image from the real article page
// ─────────────────────────────────────────────────────────────
if (!$imageData && !empty($articleUrl) && filter_var($articleUrl, FILTER_VALIDATE_URL)) {
    $page = fg_fetch($articleUrl, 12, true);
    if ($page['code'] === 200 && strlen($page['body']) > 500) {
        $ogUrl = fg_og_image($page['body']);
        if ($ogUrl) {
            $ogUrl = fg_resolve($ogUrl, $articleUrl);
            $r = fg_fetch($ogUrl, 10);
            if (fg_valid_image($r['body'], $r['mime'])) {
                $imageData = $r['body'];
                $mimeType  = explode(';', $r['mime'])[0];
                // Cache it back to DB to skip scraping next time
                if ($id > 0 && isset($pdo)) {
                    try {
                        $pdo->prepare("UPDATE articles SET image_url=? WHERE article_id=? AND (image_url IS NULL OR image_url='')")
                            ->execute([$ogUrl, $id]);
                    } catch (Exception $e) {}
                }
            }
        }
    }
}

// ─────────────────────────────────────────────────────────────
// STEP 4 — Unsplash keyword search (relevant but generic)
// ─────────────────────────────────────────────────────────────
if (!$imageData) {
    $stopw = ['news','the','and','for','with','from','this','that','are','was','has','have','its','government','parliament'];
    $kws   = array_filter(
        explode(' ', strtolower($q)),
        fn($w) => strlen($w) > 3 && !in_array($w, $stopw)
    );
    $kw = rawurlencode(implode(',', array_slice(array_values($kws), 0, 3)));
    $r  = fg_fetch("https://source.unsplash.com/{$w}x{$h}/?" . $kw, 12);
    if (fg_valid_image($r['body'], $r['mime'])) {
        $imageData = $r['body'];
        $mimeType  = explode(';', $r['mime'])[0];
    }
}

// ─────────────────────────────────────────────────────────────
// STEP 5 — Serve image (or SVG fallback)
// ─────────────────────────────────────────────────────────────
if ($imageData) {
    file_put_contents($cacheFile, $imageData);
    file_put_contents($metaFile, $mimeType);
    header('Content-Type: ' . $mimeType);
    header('Cache-Control: public, max-age=604800');
    header('X-FG-Cache: MISS');
    echo $imageData;
    exit;
}

// SVG category placeholder — never broken
$catMap = [
    'politi'  => ['#1a0000','#e63232','🏛'],
    'health'  => ['#001a08','#00c853','💊'],
    'science' => ['#00081a','#2979ff','🔬'],
    'technol' => ['#0d0014','#aa00ff','💻'],
    'sports'  => ['#1a0800','#ff6d00','🏏'],
    'financ'  => ['#1a1500','#ffd600','📈'],
    'enterta' => ['#1a000d','#ff4081','🎬'],
    'world'   => ['#00131a','#00bcd4','🌍'],
    'environ' => ['#041a00','#8bc34a','🌿'],
    'educati' => ['#0d0900','#ff9800','📚'],
];
$bg = '#0a0a0a'; $ac = '#e63232'; $icon = '📰';
foreach ($catMap as $k => [$b,$a,$em]) {
    if (str_contains(strtolower($q), $k)) { $bg=$b; $ac=$a; $icon=$em; break; }
}
$label = htmlspecialchars(mb_strtoupper(mb_substr($q, 0, 24)));
$svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$w}" height="{$h}">
  <defs>
    <linearGradient id="gb" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$bg}"/>
      <stop offset="100%" stop-color="#111"/>
    </linearGradient>
    <pattern id="gr" width="36" height="36" patternUnits="userSpaceOnUse">
      <path d="M36 0L0 0 0 36" fill="none" stroke="{$ac}" stroke-width="0.25" opacity="0.12"/>
    </pattern>
  </defs>
  <rect width="{$w}" height="{$h}" fill="url(#gb)"/>
  <rect width="{$w}" height="{$h}" fill="url(#gr)"/>
  <rect x="0" y="0" width="{$w}" height="2" fill="{$ac}" opacity="0.7"/>
  <text x="50%" y="42%" font-size="44" text-anchor="middle" dominant-baseline="middle">{$icon}</text>
  <text x="50%" y="58%" font-family="Georgia,serif" font-size="13" font-weight="bold"
    fill="{$ac}" text-anchor="middle" dominant-baseline="middle" letter-spacing="3">{$label}</text>
  <text x="50%" y="68%" font-family="monospace" font-size="8" fill="#2a2a2a"
    text-anchor="middle" letter-spacing="2">IMAGE UNAVAILABLE</text>
</svg>
SVG;
header('Content-Type: image/svg+xml');
header('Cache-Control: public, max-age=3600');
echo $svg;