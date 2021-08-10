<?php

/**
 * @param $acfImageArray
 * @param $size
 * @return mixed|void
 */
function getImageSrcBySize($acfImageArray, $size)
{
    if (!is_array($acfImageArray)) {
        return;
    }
    $url = $acfImageArray['url'];

    if (!empty($acfImageArray['sizes'][ $size ])) {
        $url = $acfImageArray['sizes'][ $size ];
    }

    return $url;
}

/**
 * @param $scheduledImageGoLink
 * @param $casinoGoLink
 * @return mixed
 */
function getScheduledImageGoLink($scheduledImageGoLink, $casinoGoLink)
{
    return !empty($scheduledImageGoLink) ? $scheduledImageGoLink : $casinoGoLink;
}

/**
 * @param $id
 * @param $field
 * @param $size
 * @return mixed|void
 */
function getHeroImgSrc($id, $field, $size)
{
    $image =  get_field($field, $id);
    $url = getImageSrcBySize($image, $size);

    return $url;
}

/**
 * @param $post
 * @param int $word_limit
 * @return string|void
 */
function getPostExcerpt($post, $word_limit = 21)
{
    if (!is_a($post, 'WP_Post')) {
        return;
    }
    if (!empty($post->post_excerpt)) {
        $excerpt = $post->post_excerpt;
    } else {
        $content = wp_strip_all_tags($post->post_content);
        $excerpt = wp_trim_words($content, $word_limit, ' ...');
    }
    return $excerpt;
}

/**
 * @param string $template
 * @return int|WP_Post
 */
function getPageIdByTemplate(string $template)
{
    $args = [
        'post_type' => 'page',
        'fields' => 'ids',
        'nopaging' => true,
        'meta_key' => '_wp_page_template',
        'meta_value' => $template
    ];
    $page = get_posts($args);
    return !empty($page) ? $page[0] : false;
}

/**
 * @param $title
 * @param $description
 */
function printContentBlock($title = '', $description = '')
{
    if (!empty($title)) {
        echo sprintf('<h2>%s</h2>', $title);
    }

    if (!empty($description)) {
        echo $description;
    }
}

add_action('wp_ajax_get_redirection_popup', 'ajaxGetRedirectionPopup');
add_action('wp_ajax_nopriv_get_redirection_popup', 'ajaxGetRedirectionPopup');
function ajaxGetRedirectionPopup()
{
    $nonce = !empty($_POST['nonce']) ? $_POST['nonce'] : false;
    if (!wp_verify_nonce($nonce, 'redirection_popup')) {
        wp_send_json(array('content' => 'Invalid nonce'));
    }
    $popup = get_field('popup_settings', 'option');
    if (!empty($popup)) {
        $title = !empty($popup['title']) ? $popup['title'] : false;
        $text = !empty($popup['text']) ? $popup['text'] : false;
        $confirm = !empty($popup['confirm']) ? $popup['confirm'] : __('yes', 'site');
        $reject = !empty($popup['reject']) ? $popup['reject'] : __('cancel', 'site');
    }
    if (!$title || !$text) {
        wp_send_json(array('content' => 'No content'));
    }
    ob_start();
    ?>
    <div class="popup-content tc-popup">
        <h5><?php echo $title; ?></h5>
        <p><?php echo $text; ?></p>
    </div>
    <div class="popup-footer">
        <button class="js--confirm-redirect btn btn-secondary btn-close">
            <?php echo $confirm; ?>
        </button>
        <button class="js--reject-redirect js--close-popup btn btn-secondary btn-close">
            <?php echo $reject; ?>
        </button>
    </div>
    <?php
    wp_send_json(array('content' => ob_get_clean(), 'status' => 1));
}


function addLastUpdateColumn($post_columns)
{
    $post_columns['last_updated'] = __('Last updated', 'site');

    return $post_columns;
}
add_filter('manage_posts_columns', 'addLastUpdateColumn');
add_filter('manage_pages_columns', 'addLastUpdateColumn');

function modifiedLastUpdateColumn($column_name, $post_id)
{
    if ('last_updated' != $column_name) {
        return;
    }
    $timeFormat = "Y/m/d \a\\t g:i a";
    echo get_the_modified_date($timeFormat, $post_id);
}
add_action('manage_posts_custom_column', 'modifiedLastUpdateColumn', 10, 2);
add_action('manage_pages_custom_column', 'modifiedLastUpdateColumn', 10, 2);

function addTopListStyle($templateName)
{
    add_action('wp_footer', function () use (&$templateName) {
        $toplistStyle = sprintf(
            '%s/assets/css/toplist-templates/%s.css',
            get_template_directory_uri(),
            $templateName
        );
        wp_enqueue_style($templateName, $toplistStyle);
    });
}

function addAttrToStyle($html, $handle, $href, $media)
{
    if (!is_admin()) {
        $html = "<link rel='preload' id='{$handle}-css' href='{$href}' as='style' type='text/css' media='{$media}' onload=\"this.rel='stylesheet'\" />
            <noscript><link rel='stylesheet' id='{$handle}-css' href='{$href}' type='text/css' media='{$media}' /></noscript>";
    }

    return $html;
}
add_filter('style_loader_tag', 'addAttrToStyle', 10, 4);

function addShortCodeStyle($stylePath, $name)
{
    if (empty($stylePath) || empty($name)) {
        return;
    }
    add_action('wp_footer', function () use (&$stylePath, &$name) {
        $style = get_template_directory_uri() . $stylePath;
        wp_enqueue_style($name, $style);
    });
}

function addShortCodeScript($scriptPath, $name)
{
    if (empty($scriptPath) || empty($name)) {
        return;
    }
    add_action('wp_footer', function () use (&$scriptPath, &$name) {
        $script = get_template_directory_uri() . $scriptPath;
        wp_enqueue_script($name, $script);
    });
}

add_action('wp_ajax_get_adblock_notice', 'ajaxGetAdblockNotice');
add_action('wp_ajax_nopriv_get_adblock_notice', 'ajaxGetAdblockNotice');
function ajaxGetAdblockNotice()
{
    $nonce = ! empty($_POST['nonce']) ? $_POST['nonce'] : false;
    if (! wp_verify_nonce($nonce, 'adblock_notice')) {
        wp_send_json(array('content' => 'Invalid nonce'));
    }
    $isEnabled = get_field('theme-adblock_detection-enabled', 'options');
    $message = get_field('theme-adblock_detection-message', 'options');
    if (! $isEnabled || empty($message)) {
        wp_send_json(array('content' => 'No content'));
    }
    ob_start();
    ?>
    <div class="adblock-detected-notice">
        <div class="message">
            <div class="left">
                <img src="<?php echo IMAGES; ?>nc-icons-adblock.svg" width="36" height="36"
                     alt="AdBlock Icon">
                <strong class="title"><?php _e('AdBlock detected.', 'site') ?></strong>
            </div>
            <div class="right">
                <?php echo $message; ?>
            </div>
            <span class="icon-close btn-close-adblocknotice" id="btn-close-adblocknotice"></span>
        </div>
    </div>
    <?php
    wp_send_json(array('content' => ob_get_clean(), 'status' => 1));
}

add_filter('the_content', 'addWrapperTable', 1, 1);
function addWrapperTable($content)
{
    return str_replace(
        ['<table', '</table>'],
        ['<div class="horizontal-scroll"><table', '</table></div>'],
        $content
    );
}

/**
 * @param $id
 * @param $desktop
 * @param false $mobile
 * @return string
 */
function getImage($id, $desktop, $mobile = false)
{
    if (wp_is_mobile() && $mobile) {
        return get_the_post_thumbnail($id, $mobile);
    } else {
        return get_the_post_thumbnail($id, $desktop);
    }
}

function renderPreloadTag($images)
{
    if (empty($images)) {
        return;
    }
    foreach ($images as $item) {
        $srcset = '';
        if (!empty($item['imagesrcset'])) {
            foreach ($item['imagesrcset'] as $key => $value) {
                $srcset .= $key . ' ' . $value . 'w,';
            }
        }
        $imageSrcset = !empty($srcset) ? 'imagesrcset = "' . mb_substr($srcset, 0, -1) . '"' : '';
        $imgSizes = !empty($imageSrcset) ? "imagesizes='50vw'" : '';
        echo "<link rel='preload' href='{$item['img']}' as='image' {$imageSrcset} {$imgSizes}> ";
    }
}

function getPreloadParams($img, $imagesrcset = false)
{
    if (empty($img)) {
        return false;
    }
    return [
        [
            'img' => $img,
            'imagesrcset' =>  $imagesrcset,
        ]
    ];
}

function checkSrcset($img, $width)
{
    if (!empty($img)) {
        return [$img => $width];
    } else {
        return false;
    }
}

function checkNlVersion()
{
    return get_current_blog_id() == 5;
}
