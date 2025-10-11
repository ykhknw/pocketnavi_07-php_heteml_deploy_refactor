<?php
/**
 * Favicon設定の統一スクリプト
 */

echo "=== Favicon設定の統一 ===\n\n";

echo "📋 現在のFavicon設定:\n";
echo "1. index.php: /assets/images/landmark.svg\n";
echo "2. contact.php: favicon.ico\n";
echo "3. about.php: favicon.ico\n";
echo "4. robots.txt: /favicon.ico, /favicon.png, /apple-touch-icon.png\n\n";

echo "🎯 推奨されるFavicon配置:\n";
echo "1. ルートディレクトリ（最優先）:\n";
echo "   public_html/\n";
echo "   ├── favicon.ico                 # ← ブラウザが自動検索\n";
echo "   ├── favicon-16x16.png\n";
echo "   ├── favicon-32x32.png\n";
echo "   ├── apple-touch-icon.png\n";
echo "   └── ...\n\n";

echo "2. assets/imagesディレクトリ:\n";
echo "   public_html/\n";
echo "   ├── assets/\n";
echo "   │   └── images/\n";
echo "   │       ├── favicon.ico        # ← 現在のlandmark.svgの場所\n";
echo "   │       ├── favicon-16x16.png\n";
echo "   │       ├── favicon-32x32.png\n";
echo "   │       └── apple-touch-icon.png\n";
echo "   └── ...\n\n";

echo "🔧 設定の統一手順:\n";
echo "1. Faviconファイルの準備:\n";
echo "   - favicon.ico (16x16, 32x32, 48x48)\n";
echo "   - favicon-16x16.png\n";
echo "   - favicon-32x32.png\n";
echo "   - apple-touch-icon.png (180x180)\n\n";

echo "2. ファイルの配置:\n";
echo "   # ルートディレクトリに配置（推奨）\n";
echo "   cp favicon.ico public_html/\n";
echo "   cp favicon-16x16.png public_html/\n";
echo "   cp favicon-32x32.png public_html/\n";
echo "   cp apple-touch-icon.png public_html/\n\n";

echo "3. HTMLの統一:\n";
echo "   <!-- すべてのページで統一 -->\n";
echo "   <link rel=\"icon\" href=\"/favicon.ico\" type=\"image/x-icon\">\n";
echo "   <link rel=\"icon\" href=\"/favicon-16x16.png\" type=\"image/png\" sizes=\"16x16\">\n";
echo "   <link rel=\"icon\" href=\"/favicon-32x32.png\" type=\"image/png\" sizes=\"32x32\">\n";
echo "   <link rel=\"apple-touch-icon\" href=\"/apple-touch-icon.png\">\n\n";

echo "⚠️ 重要な注意事項:\n";
echo "- ルートディレクトリのfavicon.icoが最優先で検索されます\n";
echo "- ファイル名は正確に指定してください\n";
echo "- 複数のサイズを用意することで、様々なデバイスに対応できます\n";
echo "- キャッシュをクリアしてから確認してください\n\n";

echo "📱 対応デバイス:\n";
echo "- デスクトップブラウザ: favicon.ico\n";
echo "- モバイルブラウザ: apple-touch-icon.png\n";
echo "- 高解像度ディスプレイ: favicon-32x32.png\n";
echo "- 低解像度ディスプレイ: favicon-16x16.png\n\n";

echo "=== Favicon設定完了 ===\n";
?>
