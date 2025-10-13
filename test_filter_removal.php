<?php
/**
 * フィルター削除リンクのテスト
 */

// エラー表示を有効にする
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== フィルター削除リンクテスト ===\n";

// 現在のURLパラメータをシミュレート
$_GET = [
    'architects_slug' => 'mori-building',
    'lang' => 'ja'
];

echo "現在のGETパラメータ:\n";
print_r($_GET);

echo "\n=== フィルター削除リンクの生成 ===\n";

// 修正前の方法（動作しない）
echo "修正前の方法:\n";
$oldMethod = array_merge($_GET, ['architects_slug' => null]);
$oldUrl = http_build_query($oldMethod);
echo "URL: ?" . $oldUrl . "\n";

// 修正後の方法
echo "\n修正後の方法:\n";
$removeArchitectFilter = $_GET;
unset($removeArchitectFilter['architects_slug']);
$newUrl = http_build_query($removeArchitectFilter);
echo "URL: ?" . $newUrl . "\n";

echo "\n=== 期待される結果 ===\n";
echo "建築家フィルターを削除した場合:\n";
echo "- 現在のURL: /architects/mori-building/?lang=ja\n";
echo "- 削除後のURL: /?lang=ja\n";
echo "- つまり、ルーティングを考慮して、メインページに戻る必要がある\n";

echo "\n=== 解決策 ===\n";
echo "建築家ページの場合、フィルター削除後はメインページ（/）に戻る必要がある\n";
echo "都道府県フィルターの場合は、同じページ内でフィルターを削除できる\n";

echo "\n=== テスト完了 ===\n";
?>
