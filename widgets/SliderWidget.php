<?php

/**
 * Toplist Widget - displays toplist with black template
 */

class SliderTopListWidget extends WP_Widget
{
    public $widgetId;
    public $title;
    public $sidebarTopListId;
    public $location;
    public $toplistItems = [];
    public $navigation;
    public $content;
    public $casinoId;
    public $bonusId;
    public $offerTitle;
    public $bonusDetailedTerms;
    public $bonusTerms;
    public $casinoTitle;
    public $boxTitle;
    public $boxInfo;


    public function __construct()
    {
        $this->location = get_current_geo_ip_location();
        parent::__construct(
            "slider_toplist_widget",
            __("Slider Toplist widget", "site"),
            ['description' => __('Displays toplist with new style', 'site')]
        );

        if (is_active_widget(false, false, $this->id_base) || is_customize_preview()) {
            add_action('wp_footer', [$this, 'sliderWidgetStyle']);
            add_action('wp_footer', [$this, 'sliderWidgetScript']);
        }
    }

    public function form($instance)
    {
    }

    public function update($new_instance, $old_instance)
    {
    }

    public function widget($args, $instance)
    {
        if (! isset($args['widget_id'])) {
            $args['widget_id'] = $this->id;
        }
        $this->widgetId = 'widget_' . $args['widget_id'];
        $this->title = ! empty(get_field('title', $this->widgetId)) ? get_field('title', $this->widgetId) : '';
        $sidebarTopList = get_field('toplist', $this->widgetId);
        $addLink = get_field('add_link', $this->widgetId);
        $this->sidebarTopListId = $sidebarTopList[0]->ID;
        $this->setToplistItems();
        $this->setContent();

        if (! empty($this->content) && ! empty($this->navigation)) {
            ?>
            <div class="aside-holder">
                <div class="aside-heading">
                    <?php
                    $this->getTitle();
                    if ($addLink) {
                        $this->getAddLink();
                    }
                    ?>
                </div>
                <div class="aside-container js-aside-container">
                    <div class="casinos-info-items">
                        <?php echo $this->content; ?>
                    </div>
                    <div class="casinos-items">
                        <?php echo $this->navigation; ?>
                        <span class="casino-arrow js-casino-arrow"></span>
                    </div>
                </div>
            </div>
            <?php
        }
    }

    public function getAddLink()
    {
        $icon = get_field('icon', $this->widgetId);
        $link = get_field('link', $this->widgetId);
        if (! empty($link)) {
            $ctaLinkTarget = $link['target'] ? $link['target'] : '_self';
        }
        $linkUrl = !empty($link['url']) ? $link['url'] : false;
        ?>
        <div class="btn-box">
            <?php if (!empty($linkUrl)) { ?>
            <a href="<?php echo $linkUrl; ?>"
               target="<?php echo $ctaLinkTarget; ?>"
                <?php echo $this->getRelAttr($ctaLinkTarget); ?>
               class="see-all-btn">
            <?php }
            if ($icon['url']) { ?>
                    <img src="<?php echo $icon['url']; ?>" alt="<?php echo $icon['alt']; ?>" width="40"
                         height="39">
            <?php } ?>
                <?php if ($link) { ?>
                    <span><?php echo $link['title'] ?></span>
                <?php } ?>
            <?php if (!empty($linkUrl)) {
                echo '</a>';
            } ?>
        </div>
        <?php
    }

    public function getTitle()
    {
        ?>
        <div class="heading-box">
            <h3><?php echo $this->title; ?></h3>
            <p><?php echo(__('Last updated', 'site') . ": "); ?>
                <time
                    datetime="<?php echo get_the_modified_date('Y-m-d', $this->sidebarTopListId); ?>">
                    <?php echo get_the_modified_date('d.m.y', $this->sidebarTopListId); ?>
                </time>
            </p>
        </div>
        <?php
    }

    public function getRelAttr($target)
    {

        $relAttr = '';
        if ($target == '_blank') {
            $relAttr = sprintf('rel="%s"', 'noreferrer noopener');
        }

        return $relAttr;
    }

    public function setToplistItems()
    {
        $casinos = false;
        $numberOfCasinos = get_field('number_of_casinos', $this->widgetId);
        $params['limit'] = ! empty($numberOfCasinos) ? $numberOfCasinos : 5;
        $customToplist = ct_get_custom_toplist_casinos($this->sidebarTopListId);
        $casinos = ! empty($customToplist['toplist']) ? $customToplist['toplist'] : false;

        if ($casinos) {
            $casinos = filter_casinos_based_on_geoip($casinos, $this->location, $params);
            $this->toplistItems = array_slice($casinos, 0, $params['limit'], $preserve_keys = true);
        }
    }

    public function setContent()
    {
        $nlVersion = checkNlVersion();
        if (! empty($this->toplistItems)) {
            $position = 0;
            foreach ($this->toplistItems as $key => $item) {
                $this->casinoId = $item['casino'];
                $goLink = $item['custom_go_link'];
                $logo = get_the_post_thumbnail($this->casinoId, 'small_logo');
                $this->casinoTitle = get_the_title($this->casinoId);
                $ratingHtml = $this->getHtmlRating();
                $tracking_params = getTrackingParamsToplist($this->casinoTitle, ++$position, $rating);
                $goLinkMarkup = getCasinoGolinkMarkup(
                    $this->casinoId,
                    __('Play now', 'site'),
                    $goLink,
                    null,
                    $tracking_params
                );
                $bonusTitle = get_field('bonus_title', $this->widgetId);
                $this->setBonusInfo();
                $this->setTitleAndInfo();
                $tcBlock = $this->getTcBlock();

                $this->navigation .= "<button class='casino-logo js-casino-logo'>{$logo}</button>";
                if ($nlVersion) {
                    $this->content .= " <div class='casino-item-box js-casino-item-box'>
                        <div class='casino-meta'>
                            {$this->boxTitle}
                            <div class='info-box'>
                                {$ratingHtml}
                            </div>
                        </div>
                    </div>";
                } else {
                    $this->content .= " <div class='casino-item-box js-casino-item-box'>
                        <div class='casino-meta'>
                            {$this->boxTitle}
                            <div class='info-box'>
                                {$ratingHtml}
                                {$this->boxInfo}
                            </div>
                        </div>
                        <dl class='bonuses-list'>
                            <dt>{$bonusTitle}</dt>
                            <dd>{$this->offerTitle}</dd>
                        </dl>
                        <div class='btn-box'>
                            {$goLinkMarkup}
                        </div>
                        {$tcBlock}
                        {$this->bonusDetailedTerms}
                    </div>";
                }
            }
        }
    }

    public function getTcBlock()
    {

        if ($show_tc_link = get_field('casino_review-tc_text_with_link_enabled', $this->casinoId)) {
            $tc_link = getCasinoTcTextWithLink($this->casinoId);
        }
        $tc_text_based_on_geoip = get_tc_text_depends_on_geoip();
        ob_start();
        ?>
        <div class="tc-block">
            <?php if (! $this->bonusDetailedTerms) {
                if ($show_tc_link) {
                    echo $tc_link;
                } elseif ($tc_text_based_on_geoip) {
                    printf('<span class="btn-tc">%s</span>', $tc_text_based_on_geoip);
                } elseif ($this->bonusTerms) {
                    echo get_tc_block($this->bonusId, $this->bonusTerms, $this->casinoTitle);
                }
            }
            if ($this->bonusDetailedTerms && $tc_text_based_on_geoip) {
                printf('<span class="btn-tc">%s</span>', $tc_text_based_on_geoip);
            }
            ?>
        </div>
        <?php

        return ob_get_clean();
    }

    public function setBonusInfo()
    {
        $this->bonusId = getCasinoActiveBonusByCategory($this->casinoId, 'welcome-bonus');
        if (! $this->bonusId) {
            $this->bonusId = getCasinoActiveBonusByCategory($this->casinoId, getWelcomeBonusTranslatedSlug());
        }
        if ($this->bonusId) {
            $bonus_offer = get_bonus_offer($this->bonusId);
            $this->offerTitle = ! empty($bonus_offer['title']) ? $bonus_offer['title'] : '';
            $this->bonusDetailedTerms = ! empty($bonus_offer['bonus_detailed_tc']) ?
                $bonus_offer['bonus_detailed_tc']
                : '';
            $this->bonusTerms = ! empty($bonus_offer['bonus_terms']) ? $bonus_offer['bonus_terms'] : '';
        }
    }

    public function setTitleAndInfo()
    {
        $casinoUrl = get_permalink($this->casinoId);
        $deLocale = get_locale() == 'de_DE';
        $showCasinoLinks = get_field('show_casino_links', $this->casinoId);
        $isShowReviewLink = ($deLocale && $showCasinoLinks) || ! $deLocale;
        if ($isShowReviewLink) {
            $this->boxTitle = sprintf('<h3><a href="%s">%s</a></h3>', $casinoUrl, $this->casinoTitle);
            $this->boxInfo = sprintf(
                '<a href="%s" class="casino-link"><svg class="icon icon-info">
          <use xlink:href="' . SVGSPRITE . '#info"></use>
      </svg></a>',
                $casinoUrl
            );
        } else {
            $this->boxTitle = sprintf('<h3>%s</h3>', $this->casinoTitle);
            $this->boxInfo = '';
        }
    }

    public function getHtmlRating()
    {
        $rating = get_field('casino_review-casino_rating', $this->casinoId);
        $starsRating = get_stars_rating($rating);
        return ! empty($starsRating) ? $starsRating : '';
    }

    public function sliderWidgetStyle()
    {
        $stylePath = '/assets/css/widgets/sidebar-toplist.css';
        $style = get_template_directory_uri() . $stylePath;
        wp_enqueue_style('slider-widget', $style);
    }
    public function sliderWidgetScript()
    {
        $scriptPath = '/assets/js/sliderToplistWidget.min.js';
        $script = get_template_directory_uri() . $scriptPath;
        wp_enqueue_script('slider-widget', $script);
    }
}
