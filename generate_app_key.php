<?php
/**
 * 安全なAPP_KEY生成スクリプト
 */

echo "=== 安全なAPP_KEY生成 ===\n\n";

// 32バイト（256ビット）のランダムキーを生成
$appKey = bin2hex(random_bytes(32));

echo "生成されたAPP_KEY:\n";
echo "APP_KEY={$appKey}\n\n";

echo "HETEML本番環境での設定方法:\n";
echo "1. config/env.heteml ファイルを開く\n";
echo "2. 以下の行を置き換える:\n";
echo "   APP_KEY=your-heteml-production-secret-key-here\n";
echo "   ↓\n";
echo "   APP_KEY={$appKey}\n\n";

echo "⚠️ 重要な注意事項:\n";
echo "- このキーは絶対に他人に知られてはいけません\n";
echo "- 本番環境でのみ使用してください\n";
echo "- キーを変更すると既存のセッションが無効になります\n";
echo "- バックアップを取ってから変更してください\n\n";

echo "=== APP_KEY生成完了 ===\n";
?>
