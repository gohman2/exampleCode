<?php

/***
 * Scripts to change content in Data Base
 */

if (!class_exists('UpdateGameUrl')) {
    class UpdateGameUrl
    {

        public $currentGame;
        public $postId;
        public $blogId;

        public function __construct()
        {

            $this->blogId = get_current_blog_id();
            add_action('admin_menu', [$this, 'registerDevsPage']);
            add_action('ctrn_upd_game_hook', [$this, 'updateGameDemoUrl']);
        }

        public function registerDevsPage()
        {
            add_submenu_page(
                'tools.php',
                'Dev Tools game url migrate',
                'Dev Tools game url migrate',
                'manage_options',
                'ctrn-game-tools',
                [
                $this,
                'developerToolsAdminPage'
                ]
            );
        }

        public function developerToolsAdminPage()
        {
            include_once('templates/admin-game.php');
            if (isset($_POST['ctrn-game-updating']) && $_POST['ctrn-game-updating']) {
                do_action('ctrn_upd_game_hook');
            }

            if (isset($_POST['ctrn-game-reset-status'])) {
                if (update_blog_option($this->blogId, 'ctrn_game_upd_status', 'reset')) {
                    echo "Status was reset";
                }
            }
        }

        public function updateGameDemoUrl()
        {
            $status = get_blog_option($this->blogId, 'ctrn_game_upd_status');
            if ('success' !== $status) :
                $posts = new WP_Query(
                    [
                        'posts_per_page' => '-1',
                        'post_type' => ['casino_game', 'casino_jackpot'],
                        'fields' => 'ids'
                    ]
                );
                if ($posts->have_posts()) {
                    while ($posts->have_posts()) :
                        $posts->the_post();
                        $this->postId = get_the_ID();
                        $this->currentGame = get_field('desktop_url', $this->postId);
                        $preview = $this->getPreview();
                        if (!empty($this->currentGame)) {
                            $this->updateFromDesktopUrl();
                        } elseif (!empty($preview['embed_video'])) {
                            $this->currentGame = $preview['embed_video'];
                            $this->updateFromEmbedVideo();
                        }
                    endwhile;
                }
                update_blog_option($this->blogId, 'ctrn_game_upd_status', 'success');
                    echo 'DB was updated';
            else :
                echo 'DB already updated';
            endif;
        }

        public function getPreview()
        {
            $result = get_field('casino_game-preview', $this->postId);
            if (!empty($result)) {
                return $result;
            }

            $result = get_field('casino_jackpot-preview', $this->postId);
            if (!empty($result)) {
                return $result;
            }

            return '';
        }

        public function updateFromDesktopUrl()
        {
            if (!update_field('game_demo_url', $this->currentGame, $this->postId)) {
                echo "<a href=\"" . get_the_permalink() . "\">"
                    . get_the_title() . " didn't update</a>";
            }
        }

        public function updateFromEmbedVideo()
        {
            preg_match('/src="([^"]+)"/', $this->currentGame, $iframeSrc);
            if (!empty($iframeSrc[1])) {
                if (!update_field('game_demo_url', $iframeSrc[1], $this->postId)) {
                    echo "<a href=\"" . get_the_permalink() . "\">"
                        . get_the_title() . " didn't update</a>";
                }
            }
        }
    }
}
new UpdateGameUrl();
