<?php

/***
 * Scripts to change content in Data Base
 */

if (!class_exists('UpdateText')) {
    class UpdateText
    {
        public $newText;
        public $currentText;
        public $postId;
        public $blogId;

        public function __construct()
        {
            $this->newText = get_field('def_text_alt_casinos', 'option');
            $this->blogId = get_current_blog_id();
            add_action('admin_menu', [$this, 'registerDevsPage']);
            add_action('ctrn_upd_hook', [$this, 'updateTextAlternativeCasinos']);
        }

        public function registerDevsPage()
        {
            add_submenu_page('tools.php', 'Developer Tools', 'Developer Tools', 'manage_options', 'ctrn-dev-tools', [
                $this,
                'developerToolsAdminPage'
            ]);
        }

        public function developerToolsAdminPage()
        {
            include_once('templates/admin.php');
            if (isset($_POST['ctrn-updating']) && $_POST['ctrn-updating']) {
                do_action('ctrn_upd_hook');
            }

            if (isset($_POST['ctrn-reset-status'])) {
                if (update_blog_option($this->blogId, 'ctrn_upd_status', 'reset')) {
                    echo "Status was reseted";
                }
            }
        }

        public function updateTextAlternativeCasinos()
        {
            $status = get_blog_option($this->blogId, 'ctrn_upd_status');
            if ('success' !== $status) :
                $posts = new WP_Query(
                    [
                        'posts_per_page' => '-1',
                        'post_type' => ['casino_review'],
                        'fields' => 'ids'
                    ]
                );
                if ($posts->have_posts()) {
                    while ($posts->have_posts()) :
                        $posts->the_post();
                        $this->postId = get_the_ID();
                        $this->currentText = get_field('alternative_casinos_popup_text', $this->postId);
                        if ($this->currentText != null) {
                            switch ($this->blogId) {
                                case 1:
                                    $this->updateMainVersion();
                                    break;
                                case 4:
                                case 2:
                                    $this->updateOtherVersions();
                                    break;
                            }
                        }
                    endwhile;
                }
                update_blog_option($this->blogId, 'ctrn_upd_status', 'success');
                    echo 'DB was updated';
            else :
                echo 'DB already updated';
            endif;
        }

        public function updateMainVersion()
        {
            $oldText = 'Unfortunately the current bonus is not available at the moment. Below are a few similar casinos:';
            if (stripos($this->currentText, $oldText) !== false) {
                if (!update_field('alternative_casinos_popup_text', $this->newText, $this->postId)) {
                    echo "<a href=\"" . get_the_permalink() . "\">"
                        . get_the_title() . " didn't update</a>";
                }
            }
        }

        public function updateOtherVersions()
        {
            if (!update_field('alternative_casinos_popup_text', $this->newText, $this->postId)) {
                echo "<a href=\"" . get_the_permalink() . "\">"
                    . get_the_title() . " didn't update</a>";
            }
        }
    }
}
new UpdateText();
