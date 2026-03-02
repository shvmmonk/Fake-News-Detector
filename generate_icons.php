<?php
/**
 * generate_icons.php
 * Run this ONCE in the browser to generate all PWA icons.
 * Visit: http://localhost/your-project/generate_icons.php
 *
 * Creates: icons/icon-{72,96,128,144,152,192,384,512}.png
 */

$iconDir = __DIR__ . '/icons/';
if (!is_dir($iconDir)) mkdir($iconDir, 0755, true);

$sizes = [72, 96, 128, 144, 152, 192, 384, 512];

$generated = [];
$failed    = [];

foreach ($sizes as $size) {
    $file = $iconDir . 'icon-' . $size . '.png';

    // Create image
    $img = imagecreatetruecolor($size, $size);
    imageantialias($img, true);

    // Colors
    $bg      = imagecolorallocate($img, 13, 13, 13);      // #0d0d0d
    $red     = imagecolorallocate($img, 230, 50, 50);     // #e63232
    $redDark = imagecolorallocate($img, 140, 20, 20);     // darker red
    $white   = imagecolorallocate($img, 240, 240, 240);   // #f0f0f0
    $grey    = imagecolorallocate($img, 40, 40, 40);      // border

    // Background
    imagefilledrectangle($img, 0, 0, $size-1, $size-1, $bg);

    // Rounded corners (approximate with filled arcs)
    $r = (int)($size * 0.2); // corner radius ≈ 20% of size
    // Fill corners with bg to simulate rounding
    imagefilledrectangle($img, 0, 0, $r, $r, $bg);
    imagefilledrectangle($img, $size-$r, 0, $size, $r, $bg);
    imagefilledrectangle($img, 0, $size-$r, $r, $size, $bg);
    imagefilledrectangle($img, $size-$r, $size-$r, $size, $size, $bg);

    // Main rounded rect (red background)
    $pad = (int)($size * 0.08);
    imagefilledrectangle($img, $pad, $pad, $size-$pad, $size-$pad, $red);
    // Round corners of the red rect with tiny arcs
    $cr = (int)($size * 0.12);
    imagefilledarc($img, $pad+$cr,      $pad+$cr,      $cr*2,$cr*2, 180,270, $bg, IMG_ARC_PIE);
    imagefilledarc($img, $size-$pad-$cr,$pad+$cr,      $cr*2,$cr*2, 270,360, $bg, IMG_ARC_PIE);
    imagefilledarc($img, $pad+$cr,      $size-$pad-$cr,$cr*2,$cr*2, 90, 180, $bg, IMG_ARC_PIE);
    imagefilledarc($img, $size-$pad-$cr,$size-$pad-$cr,$cr*2,$cr*2, 0,  90,  $bg, IMG_ARC_PIE);

    // Letter "F" or "FG" centered
    $cx = (int)($size / 2);
    $cy = (int)($size / 2);

    // Draw shield shape (simplified as a polygon)
    $shieldW = (int)($size * 0.38);
    $shieldH = (int)($size * 0.44);
    $sx = $cx - $shieldW/2;
    $sy = $cy - $shieldH/2 - (int)($size*0.04);
    $points = [
        $sx,                $sy,
        $sx + $shieldW,     $sy,
        $sx + $shieldW,     $sy + $shieldH * 0.55,
        $cx,                $sy + $shieldH,
        $sx,                $sy + $shieldH * 0.55,
    ];
    imagefilledpolygon($img, $points, $white);

    // Inner shield (darker)
    $ipad = (int)($size * 0.05);
    $inner = [
        $sx+$ipad,                $sy+$ipad,
        $sx + $shieldW - $ipad,   $sy+$ipad,
        $sx + $shieldW - $ipad,   $sy + $shieldH * 0.52,
        $cx,                      $sy + $shieldH - $ipad,
        $sx+$ipad,                $sy + $shieldH * 0.52,
    ];
    imagefilledpolygon($img, $inner, $red);

    // White "F" inside shield
    $fSize = max(2, (int)($size * 0.18));
    $fX    = $cx - (int)($fSize * 0.3);
    $fY    = $cy - (int)($fSize * 0.5);
    imagestring($img, 5, $fX, $fY, 'FG', $white);

    // Top accent bar
    $barH = max(2, (int)($size * 0.04));
    imagefilledrectangle($img, $pad, $pad, $size-$pad, $pad+$barH, $white);

    // Save
    if (imagepng($img, $file)) {
        $generated[] = $file;
    } else {
        $failed[] = "icon-{$size}.png";
    }
    imagedestroy($img);
}

// Also generate a simple favicon.ico (using the 32px version)
$fav = imagecreatetruecolor(32, 32);
$fbg = imagecolorallocate($fav, 13, 13, 13);
$fred = imagecolorallocate($fav, 230, 50, 50);
$fwh  = imagecolorallocate($fav, 240, 240, 240);
imagefilledrectangle($fav, 0, 0, 31, 31, $fbg);
imagefilledrectangle($fav, 2, 2, 29, 29, $fred);
imagestring($fav, 4, 7, 8, 'FG', $fwh);
imagepng($fav, $iconDir . 'favicon-32.png');
imagedestroy($fav);

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>FakeGuard — Icon Generator</title>
<style>
body{background:#0d0d0d;color:#f0f0f0;font-family:monospace;padding:40px;line-height:1.8;}
h1{color:#e63232;margin-bottom:24px;font-size:1.4rem;}
.ok{color:#00c853;} .err{color:#e63232;} .icon-row{display:flex;flex-wrap:wrap;gap:16px;margin:24px 0;}
.icon-item{text-align:center;} .icon-item img{border:1px solid #333;display:block;margin-bottom:6px;}
.icon-item span{font-size:0.7rem;color:#777;}
.done{background:#0d1a0d;border:1px solid #00c853;border-radius:8px;padding:16px 24px;margin-top:24px;}
</style>
</head>
<body>
<h1>🛡️ FakeGuard PWA Icon Generator</h1>

<?php if (!empty($generated)): ?>
<p class="ok">✅ Generated <?= count($generated) ?> icons successfully!</p>
<div class="icon-row">
<?php foreach ($sizes as $size): ?>
  <div class="icon-item">
    <img src="icons/icon-<?= $size ?>.png" width="<?= min($size, 80) ?>" height="<?= min($size, 80) ?>" alt="<?= $size ?>px">
    <span><?= $size ?>×<?= $size ?></span>
  </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($failed)): ?>
<p class="err">❌ Failed: <?= implode(', ', $failed) ?></p>
<?php endif; ?>

<div class="done">
  <p class="ok">✅ All done! Next steps:</p>
  <br>
  <p>1. Delete this file (generate_icons.php) — it's only needed once</p>
  <p>2. Make sure manifest.json and sw.js are in your project root</p>
  <p>3. Make sure header.php has the PWA meta tags (already added)</p>
  <p>4. Open Chrome DevTools → Application → Manifest to verify</p>
  <p>5. On mobile Chrome: tap <strong>⋮ → Add to Home Screen</strong> to install!</p>
</div>
</body>
</html>