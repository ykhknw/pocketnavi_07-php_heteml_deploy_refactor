<?php
session_start();
if (empty($_SESSION['logged_in'])) {
    header('Location: upload_login.php');
    exit;
}

// ==== 設定 ====
$dbHost = 'mysql320.phy.heteml.lan';
$dbName = '_shinkenchiku_02';
$dbUser = '_shinkenchiku_02';
$dbPass = 'ipgdfahuqbg3';

// ==== ユーティリティ関数 ====
function getExifDateTime($filepath) {
    $exif = @exif_read_data($filepath);
    if (isset($exif['DateTimeOriginal'])) {
        $dt = DateTime::createFromFormat('Y:m:d H:i:s', $exif['DateTimeOriginal']);
        if ($dt) return $dt->format('Ymd_Hi');
    }
    return null;
}

function resizeImage($srcPath, $destPath, $mime, $maxSize = 1500, $jpegQuality = 80) {
    list($width, $height) = getimagesize($srcPath);
    $scale = min($maxSize / $width, $maxSize / $height, 1);
    $newW = intval($width * $scale);
    $newH = intval($height * $scale);
    $dst = imagecreatetruecolor($newW, $newH);

    $src = ($mime === 'image/jpeg') ? imagecreatefromjpeg($srcPath) : imagecreatefrompng($srcPath);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);

    // 透かしを右下に追加
    $fontPath = __DIR__ . '/DejaVuSans.ttf';
    $text = 'kenchikuka.com';
    $fontSize = 40;
    $white = imagecolorallocatealpha($dst, 255, 255, 255, 60);
    $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
    $textWidth = abs($bbox[2] - $bbox[0]);
    $x = $newW - $textWidth - 20;
    $y = $newH - 20;
    imagettftext($dst, $fontSize, 0, $x, $y, $white, $fontPath, $text);

    if ($mime === 'image/jpeg') imagejpeg($dst, $destPath, $jpegQuality);
    else imagepng($dst, $destPath, 9);
    imagedestroy($src);
    imagedestroy($dst);
}

function saveWebp($srcPath, $destPath, $mime, $quality = 80) {
    $src = ($mime === 'image/jpeg') ? imagecreatefromjpeg($srcPath) : imagecreatefrompng($srcPath);
    imagewebp($src, $destPath, $quality);
    imagedestroy($src);
}

function createThumbnail($srcPath, $thumbPath, $mime, $size = 300) {
    list($w, $h) = getimagesize($srcPath);
    $src = ($mime === 'image/jpeg') ? imagecreatefromjpeg($srcPath) : imagecreatefrompng($srcPath);

    $min = min($w, $h);
    $srcX = intval(($w - $min) / 2);
    $srcY = intval(($h - $min) / 2);

    $thumb = imagecreatetruecolor($size, $size);
    imagecopyresampled($thumb, $src, 0, 0, $srcX, $srcY, $size, $size, $min, $min);

    // サムネイルにも透かし追加
    $fontPath = __DIR__ . '/DejaVuSans.ttf';
    $text = 'kenchikuka.com';
    $fontSize = 14;
    $white = imagecolorallocatealpha($thumb, 255, 255, 255, 70);
    $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
    $textWidth = abs($bbox[2] - $bbox[0]);
    $x = $size - $textWidth - 15;
    $y = $size - 15;
    imagettftext($thumb, $fontSize, 0, $x, $y, $white, $fontPath, $text);

    imagejpeg($thumb, $thumbPath, 80);
    imagedestroy($src);
    imagedestroy($thumb);
}

// ==== POST処理 ====
$message = '';
$messageClass = 'danger';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slugUrl = trim($_POST['slugUrl'] ?? '');
    $uidInput = trim($_POST['uid'] ?? '');
    $uid = '';
    $slug = '';

    // URLまたは slug を解析して slug を取得
    if (!empty($slugUrl)) {
        if (!filter_var($slugUrl, FILTER_VALIDATE_URL)) {
            $message = 'エラー: URL形式が不正です。';
        } else {
            $path = parse_url($slugUrl, PHP_URL_PATH);
            if (preg_match('#^/buildings/([^/]+)#', $path, $matches)) {
                $slug = $matches[1];
            } else {
                $message = 'エラー: URLからslugを抽出できませんでした。';
            }
        }
    } elseif (!empty($uidInput)) {
        $uid = $uidInput;
    } else {
        $message = 'エラー: 建築物slug入りURLまたはUIDを入力してください。';
    }

    if (empty($message) && !empty($slug)) {
        try {
            $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (Exception $e) {
            die("データベース接続エラー: " . $e->getMessage());
        }

        // slug に対応する uid を取得
        $stmt = $pdo->prepare("SELECT uid FROM buildings_table_3 WHERE slug = :slug");
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $message = "指定された建築物slug <code>$slug</code> の建物が見つかりません。<br>
                        入力値: <code>$slugUrl</code>";
        } else {
            $uid = $row['uid'];
        }
    }

    // 画像処理（複数対応）
    if (empty($message) && $uid) {
        if (strpos($uid, 'SK_') !== 0) {
            $message = 'エラー: UIDは "SK_" で始まる必要があります。';
        } elseif (!isset($_FILES['photo'])) {
            $message = 'エラー: ファイルが選択されていません。';
        } else {
            $results = [];
            foreach ($_FILES['photo']['error'] as $i => $error) {
                if ($error !== UPLOAD_ERR_OK) {
                    $results[] = "❌ ファイル ".htmlspecialchars($_FILES['photo']['name'][$i])." のアップロードに失敗しました。";
                    continue;
                }

                $tmp = $_FILES['photo']['tmp_name'][$i];
                $origName = basename($_FILES['photo']['name'][$i]);
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmp);
                finfo_close($finfo);

                if (!is_uploaded_file($tmp) || !in_array($mime, ['image/jpeg', 'image/png'])) {
                    $results[] = "❌ ".htmlspecialchars($origName)." はJPEG/PNGではありません。";
                    continue;
                }

                $dateStr = getExifDateTime($tmp) ?? date('Ymd_Hi');
                $baseName = $uid . '_' . $dateStr . '_' . $i;

                $saveDir = dirname(__DIR__) . "/pictures/$uid/";
                $thumbDir = $saveDir . "thumbs/";
                if (!file_exists($thumbDir)) mkdir($thumbDir, 0755, true);

                $jpgPath = $saveDir . "$baseName.jpg";
                $webpPath = $saveDir . "$baseName.webp";
                $thumbPathJpg = $thumbDir . "{$baseName}_thumb.jpg";
                $thumbPathWebp = $thumbDir . "{$baseName}_thumb.webp";

                resizeImage($tmp, $jpgPath, $mime);
                saveWebp($jpgPath, $webpPath, 'image/jpeg');
                createThumbnail($tmp, $thumbPathJpg, $mime);
                saveWebp($thumbPathJpg, $thumbPathWebp, 'image/jpeg');

                $results[] = "✅ ".htmlspecialchars($origName)." を保存しました<br>
                              ・JPEG: $baseName.jpg<br>
                              ・WebP: $baseName.webp<br>
                              ・サムネイル: {$baseName}_thumb.jpg / _thumb.webp";
            }

            $message = implode("<hr>", $results) . '<br><a href="' . $_SERVER['PHP_SELF'] . '" class="btn btn-secondary mt-3">別の建物をアップロードする</a>';
            $messageClass = 'success';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>画像アップロード | 建築写真</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 bg-white p-4 rounded shadow-sm">
            <h2 class="mb-4 text-center">📷 建築写真アップロード</h2>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageClass ?>"><?= $message ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="slugUrl" class="form-label">建築物slug入りURL（例：https://kenchikuka.com/buildings/kushiro-castle-hotel?lang=ja）</label>
                    <input type="url" class="form-control" id="slugUrl" name="slugUrl">
                </div>
                <div class="mb-3">
                    <label for="uid" class="form-label">UID（例：SK_12345）</label>
                    <input type="text" class="form-control" id="uid" name="uid">
                </div>
                <div class="mb-3">
                    <label for="photo" class="form-label">画像ファイル（JPEG/PNG 複数選択可）</label>
                    <input type="file" class="form-control" id="photo" name="photo[]" accept="image/jpeg,image/png" multiple required>
                </div>
                <div class="row">
                    <div class="col">
                        <button type="submit" class="btn btn-primary w-100">アップロードする</button>
                    </div>
                    <div class="col">
                        <button type="reset" class="btn btn-secondary w-100">リセット</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
