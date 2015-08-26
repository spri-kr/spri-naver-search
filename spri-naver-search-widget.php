<?php

class spri_naver_article_widget extends WP_Widget {

    private $article_table;
    private $query_table;
    private $status_table;

    function __construct() {
        global $wpdb;
        $this->article_table = $wpdb->prefix . "spri_naver_news_article";
        $this->query_table = $wpdb->prefix . "spri_naver_news_query";
        $this->status_table = $wpdb->prefix . "spri_naver_news_status";


        parent::__construct(
                'spri-naver-article-widget', //id
                'SPRI Naver Article Widget', //name
                array(
                        'description' => 'Article slide widget from naver search result'
                )

        );

    }

    function widget( $args, $instance ) {
        global $wpdb;

        $q = (int) $instance['q'];
        $n = (int) $instance['n'];

        $sql = "select *
				from {$this->article_table}
				where query_id = {$q}
				and id not in (
					select article_id
					from {$this->status_table}
					where status = 'false'
				)
				order by pubDate desc
				limit {$n}";

        $article_list = $wpdb->get_results( $sql );

        $html = "<div class='owl-carousel spri-naver-search-slide widget widget_dock'>";
        foreach ( $article_list as $item ) {
            $html .= '<div>';

            $href = "href='{$item->originallink}'";
            $target = "target='_blank'";

            if ( $item->originallink == $item->link ) {
                $href = "";
                $target = "target='_self";
            }

            $html .= <<<ARTICLE

<a {$href} {$target}> <h2> {$item->title} </h2></a>
<a  {$href} {$target}>
<p> {$item->description} </p>
</a>
ARTICLE;

            $html .= '</div>';


        }
        $html .= "</div>";


        echo $html;
    }

    function form( $instance ) {
        global $wpdb;

        $q_list = $wpdb->get_results( " SELECT * FROM wp_spri_naver_news_query " );

        $q = isset( $instance['q'] ) ? esc_attr( $instance['q'] ) : 0;
        $n = isset( $instance['n'] ) ? absint( $instance['n'] ) : 6;

        ?>

        <p>
            <label for="<?php echo $this->get_field_id( 'q' ); ?>">Query</label>
            <select name="<?php echo $this->get_field_name( 'q' ) ?>" id="<?php echo $this->get_field_id( 'q' ) ?>">
                <?php
                foreach ( $q_list as $q_item ) {
                    ?>
                    <option value="<?php echo $q_item->id; ?>"
                            <?php if ( $q == $q_item->id ) {
                                echo 'selected=selected';
                            } ?> >
                        <?php echo $q_item->query; ?>
                    </option>
                    <?php
                }
                ?>
            </select>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id( 'n' ) ?>">Number of articles</label>
            <input type="text" id="<?php echo $this->get_field_id( 'n' ); ?>"
                   name="<?php echo $this->get_field_name( 'n' ) ?>" value="<?php echo $n ?>" size="3">
        </p>
        <?php
    }


    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;

        $instance['q'] = $new_instance['q'];
        $instance['n'] = $new_instance['n'];

        return $instance;
    }
}