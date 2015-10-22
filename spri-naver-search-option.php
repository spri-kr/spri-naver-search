<?php

class spri_naver_option {

    private $article_table;
    private $query_table;
    private $status_table;

    function __construct( $tables = array() ) {

        // register menus
        add_action( 'admin_menu', array( $this, 'add_menu_and_page' ) );

        // set settings
        add_action( 'admin_init', array( $this, 'plugin_admin_init' ) );

        // register server-side ajax function
        add_action( 'wp_ajax_spri_naver_get_article_list', array( $this, 'ajax_get_articles' ) );
        add_action( 'wp_ajax_spri_naver_update_display', array( $this, 'ajax_update_article_display' ) );

        // set tables
        $this->article_table = $tables['article_table'];
        $this->query_table = $tables['query_table'];
        $this->status_table = $tables['status_table'];
    }

    function add_menu_and_page() {
        add_menu_page(
                'SPRI Naver Search',
                'SPRI Naver Search',
                'administrator',
                'spri-naver-search',
                array( $this, 'options_view' )
        );

        $article_page_hook = add_submenu_page(
                'spri-naver-search',
                'SPRI Naver Search Article Manager',
                'Article Manage',
                'manage_options',
                'spri-naver-search-articles',
                array( $this, 'article_dashboard' )
        );

        // load js on only article manage page
        add_action( 'load-' . $article_page_hook, array( $this, 'article_manage_js_load' ) );
    }

    function article_manage_js_load() {

        // load template engine
        wp_enqueue_script( 'spri-naver-template-engine-js',
                plugins_url( '/js/ICanHaz.min.js', __FILE__ ),
                array( 'jquery' ) );
        // load utility lib
        wp_enqueue_script( 'spri-naver-underscore-js',
                "https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.8.3/underscore-min.js",
                array( 'jquery' ) );
        // load ajax functions
        wp_enqueue_script( 'spri-naver-basic-js',
                plugins_url( '/js/scripts.js', __FILE__ ),
                array( 'jquery' ) );

        // load bootstrap
        wp_enqueue_script( 'spri-naver-bootstrap-js',
                "https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js",
                array( 'jquery' ) );
        wp_enqueue_style( 'spri-naver-bootstrap-css',
                "https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" );

        // load bootstrap-switch
        wp_enqueue_script( 'spri-naver-bootstrap-switch-js',
                plugins_url( '/lib/bootstrap-switch/dist/js/bootstrap-switch.min.js', __FILE__ ),
                array( 'jquery' ) );
        wp_enqueue_style( 'spri-naver-bootstrap-switch-css',
                plugins_url( '/lib/bootstrap-switch/dist/css/bootstrap3/bootstrap-switch.min.css', __FILE__ ) );
    }

    function options_view() {
        ?>
        <div>
            <h2>SPRI Naver Search options</h2>
            attributes for search.
            <form action="options.php" method="post">

                <?php settings_fields( 'spri_naver_option_group' ); ?>
                <?php do_settings_sections( 'spri-naver-search' ); ?>

                <input name="Submit" type="submit" value="<?php esc_attr_e( 'Save Changes' ); ?>"/>
            </form>
        </div>
        <?php
    }

    function article_dashboard() {
        global $wpdb;
        // get 'is_crawl=y' query list
        $q_list = $wpdb->get_results( "Select query, id from {$this->query_table}" );
        ?>

        <!-- Article template uesd at js/scripts.js -->
        <script id="article_template" type="text/html">
            <div class='item item-{{id}} col-xs-3'>
                <h4 class='article-title'>{{{title}}}</h4>
                <input type="checkbox" name="{{id}}" value="{{status}}" checked>
                <p class='article-description'> {{{description}}} </p>
                <p class='article-pubdate'>{{pubDate}}</p>
            </div>
        </script>

        <h2>Article manager</h2>

        <div class="spri-naver-article-manager option_group">
            <select name="query">
                <?php
                foreach ( $q_list as $q ) {
                    echo "<option value = {$q->id}>{$q->query}</option>";
                }
                ?>
            </select>

            <button id="get_article">
                조회
            </button>
        </div><!--option group end-->

        <div class="spri-naver-article-manager article-list">
        </div><!--Article list end-->
        <?php
    }


	/**
     * set settings
     */
    function plugin_admin_init() {

        register_setting( 'spri_naver_option_group', 'spri_naver_option_name' );

        add_settings_section( 'spri_naver_option_section',
                'Attributes',
                array( $this, 'attr_section_display' ),
                'spri-naver-search' );

        add_settings_field(
                'api_key',
                'Search API key',
                array( $this, 'api_option_display' ),
                'spri-naver-search',
                'spri_naver_option_section'
        );

    }

    function attr_section_display() {
        echo "Set default options";
    }

    function api_option_display() {
        $options = get_option( 'spri_naver_option_name' );
        echo "<input name='spri_naver_option_name[api_key]' size='40' type='text' value='{$options['api_key']}' />";
    }

	/**
     * Server-side ajax function for getting articles
     * used for display article manager page
     */
    function ajax_get_articles() {
        global $wpdb;
        $q_id = $_POST['query_id'];

        $articles = $wpdb->get_results(
                "SELECT
    article.id,
    article.title,
    article.description,
    article.originallink,
    YEAR(article.pubDate)  as year,
    MONTH(article.pubDate) as month,
    article.pubDate,
    status.status
FROM wp_spri_naver_news_article as article left join wp_spri_naver_news_status as status
        on article.id = status.article_id
WHERE query_id = {$q_id}
ORDER BY pubDate DESC
                "
        );

        echo json_encode( $articles );

        wp_die();
    }

	/**
     * Server side ajax function for update display option
     * does not return any value
     */
    function ajax_update_article_display() {
        global $wpdb;
        $p_id = $_POST['p_id'];
        $display_val = $_POST['display_val'];

        $sql = $wpdb->prepare(
                "insert into {$this->status_table} (article_id, status) values (%d, %s) on duplicate key update status=%s",
                $p_id,
                $display_val,
                $display_val
        );

        $wpdb->query( $sql );

        wp_die();
    }

}

;
