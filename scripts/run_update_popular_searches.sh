#!/bin/bash

# 人気検索キャッシュ更新スクリプト実行用シェルスクリプト
# HETEMLサーバー用

# スクリプトのディレクトリに移動
cd /home/users/1/yukihiko/web/kenchikuka.com_new

# ログファイルに実行開始時刻を記録
echo "$(date): 人気検索キャッシュ更新を開始します" >> /home/users/1/yukihiko/web/kenchikuka.com_new/logs/cron_update_popular_searches.log

# PHPスクリプトを実行
/usr/local/bin/php /home/users/1/yukihiko/web/kenchikuka.com_new/scripts/update_popular_searches.php >> /home/users/1/yukihiko/web/kenchikuka.com_new/logs/cron_update_popular_searches.log 2>&1

# 実行終了時刻を記録
echo "$(date): 人気検索キャッシュ更新が完了しました" >> /home/users/1/yukihiko/web/kenchikuka.com_new/logs/cron_update_popular_searches.log
