<?php

/**
 * Game Card Widget - displays game card widget
 */

class GameCardWidget extends WP_Widget
{

    public $title;
    public $game;
    public $widgetId;
    public $target;

    public function __construct()
    {
        parent::__construct(
            "game_card_widget",
            __("Game card widget", "site"),
            ['description' => __('Displays game card', 'site')]
        );
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
        $this->title = get_field('title', $this->widgetId) ? get_field('title', $this->widgetId) : '';
        $this->game = get_field('game', $this->widgetId);

        echo $args['before_widget'];
        if ($this->title) {
            echo $args['before_title'];
            $this->getTitle();
            echo $args['after_title'];
        }
        $this->getContent();
        echo $args['after_widget'];
    }

    public function getTitle()
    {
        ?>
        <div>
            <h2><?php echo $this->title; ?></h2>
            <?php
            $date = get_field('release_date', $this->game[0]->ID);
            $releaseDate = new DateTime($date);
            if ($date) { ?>
                <p>
                    <time>
                        <?php _e($releaseDate->format('F Y'), 'site'); ?>
                    </time>
                </p>
            <?php } ?>
        </div>
        <?php
    }

    public function getContent()
    {
        $cardText = get_field('card_text', $this->widgetId);
        $addCta = get_field('add_cta', $this->widgetId);
        $ctaLink = get_field('cta_link', $this->widgetId);
        $this->target = ! empty($ctaLink['target']) ? $ctaLink['target'] : '_self';
        $game_sticker = getCasinoGameSticker($this->game[0]->ID);
        ?>
        <div class="widget-content">
            <?php if ($cardText) {
                printf('<p>%s</p>', $cardText);
            } ?>
            <div class="game-box">
                <div class="media-holder">
                    <a href="<?php echo get_permalink($this->game[0]->ID); ?>">
                        <?php $post_thumbnail = get_the_post_thumbnail($this->game[0]->ID, 'games_mid_preview');
                        if ($post_thumbnail) {
                            echo $post_thumbnail;
                        } ?>
                        <span class="hover-box"><span><span class="icon-play"></span></span></span>
                        <?php
                        if ($game_sticker) {
                            printf('<span class="sticker">%s</span>', $game_sticker);
                        } ?>
                    </a>
                </div>
            </div>
            <?php if ($addCta && $ctaLink) { ?>
                <a href="<?php echo $ctaLink['url']; ?>" class="btn btn-primary"
                   target="<?php echo $this->target; ?>" <?php echo $this->getLinkAttributes(); ?>>
                    <?php if ($ctaLink['title']) {
                        echo $ctaLink['title'];
                    } else {
                        printf(__("Play %s", "site"), $this->game[0]->post_title);
                    } ?>
                </a>
            <?php } ?>
        </div>
        <?php
    }

    public function getLinkAttributes()
    {
        $relAttr = [];
        $attributeString = '';

        $isNofollow = get_field('make_the_link_nofollow', $this->widgetId);
        if ($this->target == '_blank') {
            $relAttr[] = 'noreferrer';
        }
        if ($isNofollow) {
            $relAttr[] = 'nofollow';
        }
        if (! empty($relAttr)) {
            $attributeString = sprintf('rel="%s"', trim(implode(' ', $relAttr)));
        }

        return $attributeString;
    }
}
