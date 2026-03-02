<?php
/**
 * ============================================================
 *  ENHANCED NEWS SCRAPER — Fake News Detection System
 *  Fetches from NewsAPI + GNews + MediaStack + RSS Feeds
 *  Run this: php scrape_news.php  OR visit it in browser
 *  Schedule via CRON for auto-updates:
 *     0 * * * * php /path/to/scrape_news.php >> /tmp/scrape.log 2>&1
 * ============================================================
 */

require_once 'includes/db.php';

// ─── API KEYS (fill in yours) ───────────────────────────────
$NEWS_API_KEY    = "f1ce9a13b90144c792da61a2d86fb45f";  // newsapi.org
$GNEWS_API_KEY   = "YOUR_GNEWS_KEY";                     // gnews.io (free 100/day)
$MEDIASTACK_KEY  = "YOUR_MEDIASTACK_KEY";                // mediastack.com (free 500/mo)

// ─── CONFIG ─────────────────────────────────────────────────
define('MAX_PER_TOPIC',    10);   // articles per topic per API
define('MAX_TOTAL',       200);   // total cap per run
define('DEFAULT_SOURCE_ID',  1);  // fallback source_id
define('DEFAULT_USER_ID',    1);  // submitted_by user_id

$is_cli = php_sapi_name() === 'cli';
if (!$is_cli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8">
    <title>News Scraper</title>
    <style>body{background:#0d0d0d;color:#e0e0e0;font-family:monospace;padding:30px;line-height:1.7;}
    .ok{color:#00c853;} .err{color:#e63232;} .warn{color:#ffd600;} .info{color:#4fb3ff;}
    h2{color:#fff;border-bottom:1px solid #333;padding-bottom:8px;}
    .box{background:#1a1a1a;border:1px solid #333;border-radius:8px;padding:20px;margin-top:20px;}
    </style></head><body>';
}

log_msg("=== FAKE NEWS SCRAPER STARTED @ " . date('Y-m-d H:i:s') . " ===", 'info');

// ─── SETUP: Ensure sources & categories exist ────────────────
setup_sources($pdo);
setup_categories($pdo);

// ─── FETCH FROM ALL SOURCES ──────────────────────────────────
$all_articles = [];
$all_articles = array_merge($all_articles, fetch_newsapi($NEWS_API_KEY));
$all_articles = array_merge($all_articles, fetch_gnews($GNEWS_API_KEY));
$all_articles = array_merge($all_articles, fetch_rss_feeds());

// ─── DEDUPLICATE & INSERT ────────────────────────────────────
$added = $skipped = $errors = 0;
$seen_titles = [];

foreach (array_slice($all_articles, 0, MAX_TOTAL) as $art) {
    if (empty($art['title']) || empty($art['content'])) { $skipped++; continue; }

    // Normalize title for dedup
    $title_key = strtolower(preg_replace('/[^a-z0-9]/i', '', $art['title']));
    if (isset($seen_titles[$title_key])) { $skipped++; continue; }
    $seen_titles[$title_key] = true;

    // DB duplicate check
    $check = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE title = ?");
    $check->execute([$art['title']]);
    if ($check->fetchColumn() > 0) { $skipped++; continue; }

    // Resolve source_id
    $source_id   = resolve_source($pdo, $art['source'] ?? '');
    $category_id = resolve_category($pdo, $art['category'] ?? '');

    try {
        $stmt = $pdo->prepare("
            INSERT INTO articles (title, content, author, url, image_url, source_id, category_id, submitted_by, published_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            substr($art['title'], 0, 499),
            $art['content'],
            $art['author'] ?? 'Unknown',
            $art['url'] ?? null,
            !empty($art['image_url']) ? $art['image_url'] : null,
            $source_id,
            $category_id,
            DEFAULT_USER_ID,
            $art['date'] ? date('Y-m-d H:i:s', strtotime($art['date'])) : date('Y-m-d H:i:s'),
        ]);
        $added++;
        log_msg("  ✓ Added: " . substr($art['title'], 0, 70), 'ok');
    } catch (PDOException $e) {
        $errors++;
        log_msg("  ✗ Error: " . $e->getMessage(), 'err');
    }
}

log_msg("", '');
log_msg("━━━ SUMMARY ━━━", 'info');
log_msg("  ✅ Added   : $added", 'ok');
log_msg("  ⏭  Skipped : $skipped", 'warn');
log_msg("  ❌ Errors  : $errors", 'err');
log_msg("  📰 DB Total: " . $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn(), 'info');
log_msg("=== DONE @ " . date('Y-m-d H:i:s') . " ===", 'info');

if (!$is_cli) echo '</body></html>';


// ════════════════════════════════════════════════════════════
//  FETCH FUNCTIONS
// ════════════════════════════════════════════════════════════

function fetch_newsapi(string $key): array {
    if (str_starts_with($key, 'YOUR')) return [];
    log_msg("→ Fetching from NewsAPI.org...", 'info');

    $topics = [
        'misinformation'  => 'Health',
        'fake news'       => 'Politics',
        'artificial intelligence' => 'Technology',
        'climate change'  => 'Science',
        'election'        => 'Politics',
        'economy India'   => 'Finance',
        'health medicine' => 'Health',
        'cricket IPL'     => 'Sports',
        'Ukraine war'     => 'Politics',
        'NASA space'      => 'Science',
        'stock market'    => 'Finance',
        'cyber attack'    => 'Technology',
    ];

    $results = [];
    foreach ($topics as $topic => $cat) {
        $url = "https://newsapi.org/v2/everything?" . http_build_query([
            'q'        => $topic,
            'language' => 'en',
            'pageSize' => MAX_PER_TOPIC,
            'sortBy'   => 'publishedAt',
            'apiKey'   => $key,
        ]);
        $data = curl_get($url);
        if (!$data || !isset($data['articles'])) continue;

        foreach ($data['articles'] as $a) {
            if (($a['title'] ?? '') === '[Removed]') continue;
            $results[] = [
                'title'     => $a['title'] ?? '',
                'content'   => $a['description'] ?? $a['content'] ?? '',
                'author'    => $a['author'] ?? ($a['source']['name'] ?? 'Unknown'),
                'url'       => $a['url'] ?? '',
                'image_url' => $a['urlToImage'] ?? '',   // ← real article image
                'source'    => $a['source']['name'] ?? '',
                'category'  => $cat,
                'date'      => $a['publishedAt'] ?? '',
            ];
        }
        usleep(200000); // 0.2s rate limit pause
    }
    log_msg("  Got " . count($results) . " articles from NewsAPI", 'ok');
    return $results;
}

function fetch_gnews(string $key): array {
    if (str_starts_with($key, 'YOUR')) return [];
    log_msg("→ Fetching from GNews.io...", 'info');

    $topics = [
        ['q' => 'misinformation OR disinformation', 'cat' => 'Politics'],
        ['q' => 'India politics',                    'cat' => 'Politics'],
        ['q' => 'health disease outbreak',           'cat' => 'Health'],
        ['q' => 'technology innovation',             'cat' => 'Technology'],
        ['q' => 'India economy GDP',                 'cat' => 'Finance'],
    ];

    $results = [];
    foreach ($topics as $t) {
        $url = "https://gnews.io/api/v4/search?" . http_build_query([
            'q'        => $t['q'],
            'lang'     => 'en',
            'max'      => MAX_PER_TOPIC,
            'token'    => $key,
        ]);
        $data = curl_get($url);
        if (!$data || !isset($data['articles'])) continue;

        foreach ($data['articles'] as $a) {
            $results[] = [
                'title'     => $a['title'] ?? '',
                'content'   => $a['description'] ?? $a['content'] ?? '',
                'author'    => $a['source']['name'] ?? 'Unknown',
                'url'       => $a['url'] ?? '',
                'image_url' => $a['image'] ?? '',   // ← GNews uses 'image' field
                'source'    => $a['source']['name'] ?? '',
                'category'  => $t['cat'],
                'date'      => $a['publishedAt'] ?? '',
            ];
        }
        usleep(300000);
    }
    log_msg("  Got " . count($results) . " articles from GNews", 'ok');
    return $results;
}

function fetch_rss_feeds(): array {
    log_msg("→ Fetching from RSS Feeds (no key needed)...", 'info');

    $feeds = [
        // Source Name                     => [URL, Category]
        'BBC News'                  => ['http://feeds.bbci.co.uk/news/rss.xml',                         'Politics'],
        'BBC Technology'            => ['http://feeds.bbci.co.uk/news/technology/rss.xml',              'Technology'],
        'BBC Health'                => ['http://feeds.bbci.co.uk/news/health/rss.xml',                  'Health'],
        'BBC Science'               => ['http://feeds.bbci.co.uk/news/science_and_environment/rss.xml', 'Science'],
        'Reuters Top News'          => ['https://feeds.reuters.com/reuters/topNews',                    'Politics'],
        'Reuters Business'          => ['https://feeds.reuters.com/reuters/businessNews',               'Finance'],
        'Reuters Science'           => ['https://feeds.reuters.com/reuters/scienceNews',                'Science'],
        'NASA'                      => ['https://www.nasa.gov/rss/dyn/breaking_news.rss',               'Science'],
        'WHO News'                  => ['https://www.who.int/rss-feeds/news-english.xml',               'Health'],
        'The Guardian World'        => ['https://www.theguardian.com/world/rss',                        'Politics'],
        'The Guardian Tech'         => ['https://www.theguardian.com/us/technology/rss',                'Technology'],
        'The Guardian Science'      => ['https://www.theguardian.com/science/rss',                      'Science'],
        'TechCrunch'                => ['https://techcrunch.com/feed/',                                 'Technology'],
        'Wired'                     => ['https://www.wired.com/feed/rss',                               'Technology'],
        'Al Jazeera'                => ['https://www.aljazeera.com/xml/rss/all.xml',                    'Politics'],
        'NDTV India'                => ['https://feeds.feedburner.com/ndtvnews-top-stories',             'Politics'],
        'Indian Express'            => ['https://indianexpress.com/feed/',                               'Politics'],
        'ESPN Cricket'              => ['https://www.espncricinfo.com/rss/content/story/feeds/6.xml',   'Sports'],
        'Snopes (Fact Check)'       => ['https://www.snopes.com/feed/',                                 'Politics'],
        'FullFact'                  => ['https://fullfact.org/feed/latest/',                            'Politics'],
        'PolitiFact'                => ['https://www.politifact.com/rss/all/',                          'Politics'],
    ];

    $results = [];
    foreach ($feeds as $source_name => [$feed_url, $category]) {
        $xml_string = curl_get_raw($feed_url, 8);
        if (!$xml_string) continue;

        try {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xml_string, 'SimpleXMLElement', LIBXML_NOCDATA);
            if (!$xml) continue;

            $items = $xml->channel->item ?? $xml->entry ?? [];
            $count = 0;
            foreach ($items as $item) {
                if ($count >= MAX_PER_TOPIC) break;
                $title   = trim((string)($item->title ?? ''));
                $content = trim(strip_tags((string)($item->description ?? $item->summary ?? '')));
                $url     = trim((string)($item->link ?? $item->id ?? ''));
                $date    = trim((string)($item->pubDate ?? $item->published ?? $item->updated ?? ''));
                $author  = trim((string)($item->author ?? $item->creator ?? $source_name));

                // Atom feeds put link in href attribute
                if (empty($url) && isset($item->link['href'])) {
                    $url = (string)$item->link['href'];
                }

                if (!$title || strlen($content) < 30) continue;

                // Extract image from RSS — try enclosure, media:content, media:thumbnail
                $img_url = '';
                if (isset($item->enclosure) && (string)$item->enclosure['type'] && str_starts_with((string)$item->enclosure['type'], 'image')) {
                    $img_url = (string)$item->enclosure['url'];
                }
                if (!$img_url) {
                    $media_ns = $item->children('media', true);
                    if (isset($media_ns->content['url']))   $img_url = (string)$media_ns->content['url'];
                    if (!$img_url && isset($media_ns->thumbnail['url'])) $img_url = (string)$media_ns->thumbnail['url'];
                }
                // Try parsing og:image from description HTML as last resort
                if (!$img_url) {
                    preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', (string)($item->description ?? ''), $m);
                    if (!empty($m[1])) $img_url = $m[1];
                }

                $results[] = [
                    'title'     => $title,
                    'content'   => substr($content, 0, 2000),
                    'author'    => $author ?: $source_name,
                    'url'       => $url,
                    'image_url' => $img_url,
                    'source'    => $source_name,
                    'category'  => $category,
                    'date'      => $date,
                ];
                $count++;
            }
            log_msg("    ✓ $source_name: $count items", 'ok');
        } catch (Exception $e) {
            log_msg("    ✗ $source_name failed: " . $e->getMessage(), 'err');
        }
    }

    log_msg("  Got " . count($results) . " articles from RSS feeds", 'ok');
    return $results;
}


// ════════════════════════════════════════════════════════════
//  HELPER FUNCTIONS
// ════════════════════════════════════════════════════════════

function curl_get(string $url): ?array {
    $raw = curl_get_raw($url);
    if (!$raw) return null;
    return json_decode($raw, true);
}

function curl_get_raw(string $url, int $timeout = 15): ?string {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'FakeNewsDetector/1.0 (Educational Project)',
        CURLOPT_HTTPHEADER     => ['Accept: application/rss+xml, application/xml, text/xml, application/json'],
    ]);
    $response   = curl_exec($ch);
    $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error || $http_code >= 400) return null;
    return $response ?: null;
}

// Cache: source name → source_id
$_source_cache = [];
function resolve_source(PDO $pdo, string $name): int {
    global $_source_cache;
    if (!$name) return DEFAULT_SOURCE_ID;
    $key = strtolower($name);
    if (isset($_source_cache[$key])) return $_source_cache[$key];

    $stmt = $pdo->prepare("SELECT source_id FROM sources WHERE LOWER(name) LIKE ?");
    $stmt->execute(["%$key%"]);
    $row = $stmt->fetch();
    if ($row) { $_source_cache[$key] = $row['source_id']; return $row['source_id']; }

    // Auto-create unknown source with neutral score
    $score = guess_credibility($name);
    $pdo->prepare("INSERT IGNORE INTO sources (name, credibility_score, country) VALUES (?,?,?)")
        ->execute([$name, $score, guess_country($name)]);
    $id = $pdo->lastInsertId() ?: DEFAULT_SOURCE_ID;
    $_source_cache[$key] = $id;
    return $id;
}

$_cat_cache = [];
function resolve_category(PDO $pdo, string $name): int {
    global $_cat_cache;
    if (!$name) return 1;
    if (isset($_cat_cache[$name])) return $_cat_cache[$name];

    $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE name = ?");
    $stmt->execute([$name]);
    $row = $stmt->fetch();
    if ($row) { $_cat_cache[$name] = $row['category_id']; return $row['category_id']; }

    $pdo->prepare("INSERT IGNORE INTO categories (name) VALUES (?)")->execute([$name]);
    $id = $pdo->lastInsertId() ?: 1;
    $_cat_cache[$name] = $id;
    return $id;
}

function guess_credibility(string $name): int {
    $trusted = ['bbc', 'reuters', 'guardian', 'nytimes', 'ndtv', 'hindu', 'nasa', 'who', 'apnews', 'bloomberg', 'indianexpress'];
    $low     = ['whatsapp', 'forward', 'fake', 'viral', 'unknown', 'anonymous'];
    $nameLow = strtolower($name);
    foreach ($trusted as $t) if (str_contains($nameLow, $t)) return rand(82, 95);
    foreach ($low     as $l) if (str_contains($nameLow, $l)) return rand(8, 20);
    return rand(45, 75);
}

function guess_country(string $name): string {
    $map = ['bbc'=>'UK','guardian'=>'UK','reuters'=>'UK','ndtv'=>'India',
            'hindu'=>'India','indianexpress'=>'India','nasa'=>'USA','who'=>'Global',
            'aljazeera'=>'Qatar','techcrunch'=>'USA','wired'=>'USA'];
    foreach ($map as $k => $c) if (str_contains(strtolower($name), $k)) return $c;
    return 'Unknown';
}

function setup_sources(PDO $pdo): void {
    $sources = [
        ['BBC News',              'https://bbc.co.uk/news',         92, 'UK'],
        ['Reuters',               'https://reuters.com',            93, 'UK'],
        ['The Guardian',          'https://theguardian.com',        88, 'UK'],
        ['Al Jazeera',            'https://aljazeera.com',          78, 'Qatar'],
        ['NDTV',                  'https://ndtv.com',               80, 'India'],
        ['The Hindu',             'https://thehindu.com',           90, 'India'],
        ['Indian Express',        'https://indianexpress.com',      85, 'India'],
        ['Times of India',        'https://timesofindia.com',       82, 'India'],
        ['NASA',                  'https://nasa.gov',               99, 'USA'],
        ['WHO',                   'https://who.int',                97, 'Global'],
        ['TechCrunch',            'https://techcrunch.com',         80, 'USA'],
        ['Wired',                 'https://wired.com',              82, 'USA'],
        ['Snopes',                'https://snopes.com',             88, 'USA'],
        ['PolitiFact',            'https://politifact.com',         87, 'USA'],
        ['FullFact',              'https://fullfact.org',           90, 'UK'],
        ['AP News',               'https://apnews.com',             95, 'USA'],
        ['Bloomberg',             'https://bloomberg.com',          88, 'USA'],
        ['WhatsApp Forward',      null,                             10, 'Unknown'],
        ['NewsXFake',             'https://newsxfake.net',          15, 'Unknown'],
        ['Anonymous Blog',        null,                             12, 'Unknown'],
        ['ESPNCricinfo',          'https://espncricinfo.com',       85, 'India'],
        ['Mint',                  'https://livemint.com',           82, 'India'],
        ['Business Standard',     'https://business-standard.com', 84, 'India'],
    ];

    foreach ($sources as [$name, $url, $score, $country]) {
        $pdo->prepare("INSERT IGNORE INTO sources (name, website_url, credibility_score, country) VALUES (?,?,?,?)")
            ->execute([$name, $url, $score, $country]);
    }
}

function setup_categories(PDO $pdo): void {
    $cats = [
        ['Politics',       'Government, elections, policy news'],
        ['Health',         'Medical, disease, wellness news'],
        ['Science',        'Research, discoveries, space'],
        ['Technology',     'Tech, AI, cybersecurity, startups'],
        ['Sports',         'Cricket, football, sports events'],
        ['Finance',        'Economy, markets, business'],
        ['Entertainment',  'Bollywood, movies, celebrities'],
        ['World',          'International affairs'],
        ['Environment',    'Climate, nature, sustainability'],
        ['Education',      'Schools, universities, exams'],
    ];
    foreach ($cats as [$name, $desc]) {
        $pdo->prepare("INSERT IGNORE INTO categories (name, description) VALUES (?,?)")->execute([$name, $desc]);
    }
}

function log_msg(string $msg, string $type = ''): void {
    $is_cli = php_sapi_name() === 'cli';
    if ($is_cli) {
        $colors = ['ok'=>"\033[32m",'err'=>"\033[31m",'warn'=>"\033[33m",'info'=>"\033[36m",''=> "\033[0m"];
        echo ($colors[$type] ?? '') . $msg . "\033[0m\n";
    } else {
        $classes = ['ok'=>'ok','err'=>'err','warn'=>'warn','info'=>'info',''=> ''];
        $cls = $classes[$type] ?? '';
        echo '<div' . ($cls ? " class=\"$cls\"" : '') . '>' . htmlspecialchars($msg) . "</div>\n";
        ob_flush(); flush();
    }
}
