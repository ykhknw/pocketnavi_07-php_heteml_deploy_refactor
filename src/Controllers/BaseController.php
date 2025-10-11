<?php

/**
 * ベースコントローラー
 * すべてのコントローラーの基底クラス
 */
abstract class BaseController {
    
    protected $lang;
    protected $request;
    
    public function __construct() {
        $this->lang = $this->getLanguage();
        $this->request = $this->getRequest();
    }
    
    /**
     * JSONレスポンスの送信
     */
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * リダイレクト
     */
    protected function redirect($url, $statusCode = 302) {
        http_response_code($statusCode);
        header("Location: $url");
        exit;
    }
    
    /**
     * 404エラー
     */
    protected function notFound() {
        http_response_code(404);
        echo "404 Not Found";
        exit;
    }
    
    /**
     * 言語の取得
     */
    private function getLanguage() {
        return $_GET['lang'] ?? 'ja';
    }
    
    /**
     * リクエスト情報の取得
     */
    private function getRequest() {
        return [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'uri' => $_SERVER['REQUEST_URI'] ?? '/',
            'path' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH),
            'query' => $_GET,
            'post' => $_POST
        ];
    }
    
    /**
     * 言語の取得（テスト用）
     */
    public function getLanguageForTest() {
        return $this->lang;
    }
}
