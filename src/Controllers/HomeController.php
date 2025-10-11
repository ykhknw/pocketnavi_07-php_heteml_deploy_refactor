<?php

require_once __DIR__ . '/BaseController.php';

/**
 * ホームコントローラー
 * メインページと検索機能を管理
 */
class HomeController extends BaseController {
    
    /**
     * メインページの表示
     */
    public function index() {
        $data = [
            'title' => 'PocketNavi',
            'message' => 'Welcome to PocketNavi!',
            'lang' => $this->lang
        ];
        
        $this->json($data);
    }
    
    /**
     * 建築物詳細ページの表示
     */
    public function building($slug) {
        $data = [
            'title' => 'Building Details',
            'slug' => $slug,
            'message' => "Building: {$slug}",
            'lang' => $this->lang
        ];
        
        $this->json($data);
    }
    
    /**
     * 建築家ページの表示
     */
    public function architect($slug) {
        $data = [
            'title' => 'Architect Details',
            'slug' => $slug,
            'message' => "Architect: {$slug}",
            'lang' => $this->lang
        ];
        
        $this->json($data);
    }
}
