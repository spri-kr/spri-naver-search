<?php

/*
Plugin Name: SPRI Naver news Search Api
Version: 1.3
Author: ungsik.yun@gmail.com
Description: Shortcode and widget for specific search result from naver.
*/

// http://developer.naver.com/wiki/pages/News

require_once( "spri-naver-search-option.php" );
require_once( "spri-naver-search-widget.php" );

class spri_naver_news {

	private $article_table;
	private $query_table;
	private $status_table;
	private $options;

	function __construct() {

		// database table names
		global $wpdb;
		$this->article_table = $wpdb->prefix . "spri_naver_news_article";
		$this->query_table   = $wpdb->prefix . "spri_naver_news_query";
		$this->status_table  = $wpdb->prefix . "spri_naver_news_status";

		// shortcodes
		add_shortcode( 'spri-naver-search', array( $this, 'shortcode_naver_search' ) );

		// actions
		add_action( 'spri_naver_cron_job', array( $this, 'do_cron_job' ) );
		add_action( 'wp_head', array( $this, 'load_css' ) );

		// filters
		add_filter( 'query_vars', array( $this, 'url_query_filter' ) );
		add_filter( 'cron_schedules', array( $this, 'add_custom_cron_interval' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_plugin_setting_link' ) );

		// plugin activation and deactivation
		register_activation_hook( __FILE__, array( $this, 'activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivation' ) );

		// option page creating. pass tables
		new spri_naver_option( array(
			'article_table' => $this->article_table,
			'query_table'   => $this->query_table,
			'status_table'  => $this->status_table,
		) );

		// load options
		$this->options = get_option( 'spri_naver_option_name' );

		//widget scripts load
		add_action( 'wp_enqueue_scripts', array( $this, 'register_widget_scripts' ) );

		// widget create
		add_action( 'widgets_init', array( $this, 'register_article_widget' ) );
	}

	/**
	 * run at plugin activation
	 */
	function activation() {
		$this->set_up_database();
		$this->cron_job_registration();
	}

	/**
	 * setting up database at activation time
	 */
	function set_up_database() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql1 = "CREATE TABLE {$this->article_table} (
id INT(11) NOT NULL AUTO_INCREMENT,
query_id int(11) NOT NULL,
title VARCHAR(50) NOT NULL,
originallink VARCHAR(512) NOT NULL,
link VARCHAR(512) NOT NULL,
description VARCHAR(512) NOT NULL,
pubDate VARCHAR(20) NOT NULL,
uniqueness_hash CHAR(40) NOT NULL,
index (id),
index (query_id, pubDate),
index (query_id),
index (title),
index (link),
index (pubDate),
UNIQUE (uniqueness_hash)
) $charset_collate;
";

		$sql2 = "CREATE TABLE {$this->query_table} (
id int(11) NOT NULL AUTO_INCREMENT,
query VARCHAR(100) NOT NULL,
query_hash char(40) NOT NULL,
status VARCHAR(20),
index (id),
index (id, query_hash),
index (query_hash),
index (query),
index (id),
index (status)

) $charset_collate;
";

		$sql3 = "CREATE TABLE {$this->status_table} (
id int(11) NOT NULL AUTO_INCREMENT,
article_id int(11) NOT NULL,
`status` VARCHAR(20) NOT NULL,
index (id),
INDEX (`status`),
index (article_id),
index (article_id, `status`),
UNIQUE (article_id)

) $charset_collate;
";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql1 );
		dbDelta( $sql2 );
		dbDelta( $sql3 );
	}

	/**
	 * register wp_cron jop at activation time
	 */
	function cron_job_registration() {
		wp_schedule_event( time(), 'twicedaily', 'spri_naver_cron_job' );
	}

	/**
	 * run at plugin deactivation
	 */
	function deactivation() {
		$this->cron_job_clear();
	}

	/**
	 * clear wp_cron job at deactivation time
	 */
	function cron_job_clear() {
		wp_clear_scheduled_hook( 'spri_naver_cron_job' );
	}

	/**
	 * cron function for crawling.
	 * Crawl the naver news with query and insert result into database
	 */
	public function do_cron_job() {
		global $wpdb;

		//Get list of querys
		$query_list = $this->get_query_list();

		// for each query, get new articles
		foreach ( $query_list as $query ) {
			$this->maintenance_crawl( $query );
		}

	}

	/**
	 * Simple function that getting query list stored via shortcode.
	 *
	 * @return mixed query list. wpdb result object
	 */
	function get_query_list() {
		global $wpdb;

		return $wpdb->get_results( "
		SELECT * FROM wp_spri_naver_news_query
		" );
	}

	/**
	 * Maintenance status
	 * query have this status are already crawled past articles.
	 * So crawl daily updates after whole article crawl.
	 *
	 * @return q wpdb_object
	 */
	function maintenance_crawl( $q ) {
		global $wpdb;

		// set attrs for crawling
		$attr = array(
			'key'     => $this->options['api_key'],
			'query'   => $q->query,
			'target'  => 'news',
			'display' => '100',
			'start'   => '1',
			'sort'    => 'sim',
		);

		$xml = $this->get_naver_xml( $attr );

		$total_page = $this->calculate_total_page( $xml );

		$articles = $this->get_search_results_from_naver( $attr, $total_page );

		$this->insert_articles( $q->id, $articles );
	}

	/**
	 * @param $attr receive via shortcode attributes
	 *
	 * @return SimpleXMLElement RSS feed
	 */
	public function get_naver_xml( $attr ) {

		//Get xml content
		$url = "http://openapi.naver.com/search?";
		$url .= http_build_query( $attr );

		$xml = simplexml_load_string( file_get_contents( $url ) );

		return $xml;
	}

	/**
	 * @param $xml RSS feed
	 *
	 * @return float|int count of feed's article number
	 */
	protected function calculate_total_page( $xml ) {
		$total_page = $xml->channel->total / 100;
		if ( $total_page > 10 ) {
			$total_page = 10;
		}

		return $total_page;
	}

	/**
	 * get all articles from naver search results
	 *
	 * @param $attr attributes getting via shortcode
	 * @param $total_page number of total page of naver news feed.
	 *
	 * @return array extracted article
	 */
	protected function get_search_results_from_naver( $attr, $total_page ) {
		$attr['start'] = 1;
		$articles      = array();
		for ( $i = 1; $i <= $total_page; $i ++ ) {
			$xml           = $this->get_naver_xml( $attr );
			$articles      = $this->extract_articles_from_xml( $xml, $articles );
			$attr['start'] = $attr['display'] * $i;
		}

		return $articles;
	}

	/**
	 * @param $xml xml file from get_naver_xml
	 * @param array $articles array contains or be contains articles
	 *
	 * @return array associated array
	 */
	protected function extract_articles_from_xml( $xml, $articles = array() ) {

		foreach ( $xml->channel->item as $article ) {
			if ( $article->originallink == "" ) {
				$article->originallink = $article->link;
			}
			array_push( $articles,
				$article
			);
		}

		return $articles;
	}

	/**
	 * @param $q_id query id on database
	 * @param $articles articles be inserted into DB
	 */
	protected function insert_articles( $q_id, $articles ) {
		global $wpdb;

		foreach ( $articles as $article ) {
			$pubDate = date_create_from_format( 'D, d M Y H:i:s T', $article->pubDate );

			$insert_sql = $wpdb->prepare( "
				INSERT INTO $this->article_table
				(title, originallink, link, description, pubDate, query_id, uniqueness_hash)
				VALUES
				(%s, %s, %s, %s, %s, %d, SHA1(%s));",
				array(
					$article->title,
					$article->originallink,
					$article->link,
					$article->description,
					$pubDate->format( 'Y-m-d H:i:s' ),
					(int) $q_id,
					$article->title . (string) $q_id . $article->pubDate,
				)
			);

			$wpdb->query( $insert_sql );
		}
	}

	function add_custom_cron_interval( $schedules ) {
		// $schedules stores all recurrence schedules within WordPress
		$schedules['ten_seconds'] = array(
			'interval' => 10,  // Number of seconds, 600 in 10 minutes
			'display'  => 'Once Every 10 seconds'
		);

		// Return our newly added schedule to be merged into the others
		return (array) $schedules;
	}

	function register_widget_scripts() {
		wp_enqueue_script(
			'spri-naver-widget-slide-script',
			plugins_url( '/js/slide.js', __FILE__ ),
			array( 'jquery' )
		);

		wp_enqueue_script(
			'spri-naver-owl-slider-script',
			plugins_url( '/lib/owl-carousel/owl.carousel.min.js', __FILE__ ),
			array( 'jquery' )
		);

		wp_register_style( 'spri-naver-widget-style', plugins_url( '/css/style.css', __FILE__ ) );
		wp_register_style( 'spri-naver-widget-slider-style',
			plugins_url( '/lib/owl-carousel/owl.carousel.css', __FILE__ ) );

		wp_enqueue_style( 'spri-naver-widget-style' );
		wp_enqueue_style( 'spri-naver-widget-slider-style' );
	}

	/**
	 * shortcode function.
	 *
	 * @return string html snippet has results
	 */
	public function shortcode_naver_search( $attr ) {

		// Set default value
		$attr = shortcode_atts( array(
			'key'      => $this->options['api_key'],
			'query'    => 'SPRI',
			'target'   => 'news',
			'display'  => '10',
			'start'    => '1',
			'sort'     => 'sim',
			'class'    => 'spri-naver-search',
			'template' => 'basic',
			'is_crawl' => 'n'
		),
			$attr );

		if ( $attr['is_crawl'] == 'y' ) {
			// get articles form database.

			//check if the search is first time
			if ( ! $this->is_exist_query( $attr['query'] ) ) {
				// if first time, query is not in db
				$this->insert_query( $attr['query'] );
				// Do new crawl
				$this->new_crawl( $attr );
			}

			$q_id = $this->get_query_id_from_query( $attr['query'] );

			// Check page parameter and Get article by parameter
			$articles = $this->retrieve_articles( $q_id );

			// building html part
			$html = $this->generate_year_month_navigation( $q_id );
			$html .= $this->generate_articles_html( $attr, $articles );

		} else {
			// get articles from naver search api
			$xml      = $this->get_naver_xml( $attr );
			$articles = $this->extract_articles_from_xml( $xml );

			// sort articles date order
			usort( $articles,
				function ( $a, $b ) {
					return strtotime( $a->pubDate ) < strtotime( $b->pubDate );
				} );

			$html = $this->generate_articles_html( $attr, $articles );

		}

		//TODO "Show more article function"

		$html .= $this->add_naver_bi();

		return $html;
	}

	/**
	 *check if query string exist
	 *
	 * @param $q query string
	 *
	 * @return mixed return 0 or 1
	 */
	function is_exist_query( $q ) {
		global $wpdb;

		$q_hash = sha1( $q );
		$sql    = "select exists
					(select * from
					wp_spri_naver_news_query
					where query_hash = '$q_hash') AS exist";

		$r = $wpdb->get_row( $sql );

		return $r->exist;
	}

	/**
	 * @param $q query be resisted to crawl
	 */
	function insert_query( $q ) {
		global $wpdb;

		$sql = $wpdb->prepare( "
			INSERT INTO $this->query_table
			(query, query_hash)
			VALUE
			(%s, SHA1(%s))
		",
			array( $q, $q )
		);

		$wpdb->query( $sql );

	}

	/**
	 * New status
	 * new to crawl. query have this status does not have any articles on database
	 */
	function new_crawl( $attr ) {
		global $wpdb;
		//$wpdb->show_errors();

		// set attrs for crawling
		$attr['display'] = '100';
		$attr['sort']    = 'sim';

		$query_id = $this->get_new_query_id( $attr );

		$xml = $this->get_naver_xml( $attr );

		$total_page = $this->calculate_total_page( $xml );

		$articles = $this->get_search_results_from_naver( $attr, $total_page );

		$this->insert_articles( $query_id->id, $articles );
	}

	/**
	 * get query's id via query string hash
	 *
	 * @param $attr shortcode attr
	 * @param $wpdb
	 *
	 * @return mixed
	 */
	protected function get_new_query_id( $attr ) {
		global $wpdb;
		$q_hash       = sha1( $attr['query'] );
		$query_id_sql = "select id from $this->query_table where query_hash = '$q_hash';";
		$query_id     = $wpdb->get_row( $query_id_sql );

		return $query_id;
	}

	/**
	 * get query id by query string
	 *
	 * @param $q string
	 *
	 * @return int $id query id from query string
	 */
	protected function get_query_id_from_query( $q ) {
		global $wpdb;

		//escaping quotes from query string
		$q   = addslashes( $q );
		$sql = <<<SQL
SELECT * FROM $this->query_table WHERE query = '{$q}';
SQL;

		$query_id = $wpdb->get_row(
			$sql
		);

		return (int) $query_id->id;
	}

	/**
	 * get article list on shortcode call. it retrieve articles by url parameter 'ym'
	 * if 'ym' parameter does not exist it getting latest articles
	 *
	 * @param $q_id int query id
	 *
	 * @return mixed
	 */
	protected function retrieve_articles( $q_id ) {
		global $wpdb;
		global $wp_query;

		//TODO sanitize $ym

		// Get year and month from GET param or db
		if ( isset( $wp_query->query_vars['ym'] ) ) {
			$ym = $wp_query->query_vars['ym'];
		} else {
			$ym_sql    = "select date_format(pubDate, '%Y%m') AS ym
				from {$this->article_table}
				where query_id = {$q_id}
				and id not in (
					select article_id
					from {$this->status_table}
					where status = 'false'
				)
				order by ym desc";
			$ym_result = $wpdb->get_row( $ym_sql );
			$ym        = (string) $ym_result->ym;
		}

		// Get articles base on ym
		$sql = "select *
				from $this->article_table
				where query_id = {$q_id}
				and id not in (
					select article_id
					from {$this->status_table}
					where status = 'false'
				)
				and date_format(pubDate, '%Y%m') = '{$ym}'
				order by pubDate desc";

		$articles = $wpdb->get_results( $sql );

		return $articles;
	}

	/**
	 * @param int $q_id query id from database
	 *
	 * @return string return html snippet that display year and month selector
	 */
	private function generate_year_month_navigation( $q_id ) {

		$ym_data = $this->get_year_month_data_by_query_id( $q_id );

		// group by year
		$year_list = array();
		foreach ( $ym_data as $item ) {
			$y = $item->y;
			$m = $item->m;
			$c = $item->c;

			if ( ! isset( $year_list[ $y ] ) ) {
				$year_list[ $y ] = array();
			}
			$year_list[ $y ][ $m ]['cnt'] = $c;
		}

		$html = "<div class='spri-naver-search pull-right spri-select'>";

		$json_year_list = json_encode( $year_list );

		//TODO separate scripts to js file. localize json_year_list
		$html .= <<<SCRIPTS
<script type="application/javascript">

    urlParam = function (name) {
        var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
        if (results == null) {
            return null;
        }
        else {
            return results[1] || 0;
        }
    };

    year_list = {$json_year_list};

    jQuery(document).ready(function ($) {
        jQuery("#spri-year").change(function () {
            var year = jQuery(this).children("option:selected").val();

            jQuery("#spri-month option").each(function () {

                var cur_month = jQuery(this).val();
                var is_in = cur_month in year_list[year];

                if (is_in > 0) {
                    jQuery(this).show();
                    jQuery(this).prop('disabled', false);
                    jQuery(this).prop('selected', true);
                    jQuery(this).text(cur_month + "월 (" + year_list[year][cur_month].cnt + "건)");
                }
                else {
                    jQuery(this).hide();
                    jQuery(this).prop('disabled', true);
                }
            });

        });


        jQuery("#spri-go-to-yyyymm").click(function () {
            var year = jQuery("#spri-year").val();
            var month = jQuery("#spri-month").val();

            if (month < 10) {
                month = "0" + month;
            }

            window.location = "?ym=" + year + month;
        });

        var set_option = function () {
            var ym = urlParam("ym");
            if (ym == null) {
                jQuery("#spri-year").change();
            }
            else {
                var y = ym.slice(0, 4);
                var m = Number(ym.slice(4));
                console.log(y, m);
                jQuery("#spri-year option[value=" + y + "]").prop("selected", true);
                jQuery("#spri-year").change();
                jQuery("#spri-month option[value=" + m + "]").prop("selected", true);
            }

        };

        set_option();
    });


</script>

SCRIPTS;


		$html .= "<select id='spri-year'>";
		foreach ( $year_list as $year => $item ) {
			$html .= "<option value='$year'>{$year}년</option>";
		}
		$html .= "</select>";

		$html .= "<select id='spri-month'>";
		foreach ( range( 1, 12 ) as $month ) {
			$html .= "<option value='{$month}'></option>";
		}
		$html .= "</select>";

		$html .= "<button id='spri-go-to-yyyymm'>조회</button>";
		$html .= "</div>";
		$html .= "<div class='clear-both' ></div>";
		$html .= "<hr class='clear-both spri-naver-search' />";

		return ( $html );
	}

	/**
	 * get year and month list of requested query's article
	 *
	 * @param $q_id int query id on DB
	 *
	 * @return mixed wpdb result obj
	 */
	private function get_year_month_data_by_query_id( $q_id ) {

		global $wpdb;

		$sql = <<<SQL
SELECT
    date_format(pubDate, '%Y%m') AS ym,
    YEAR(pubDate)                as y,
    month(pubDate)               as m,
    count(*)                     as c
FROM wp_spri_naver_news_article
WHERE query_id = {$q_id}
and id not in (
select article_id
from {$this->status_table}
where status = 'false'
)
GROUP BY ym
ORDER BY ym DESC;
SQL;

		return $wpdb->get_results( $sql );
	}

	/**
	 *
	 * return html snippet based on 'template'
	 *
	 * @param $attr
	 * @param $articles
	 *
	 * @return string
	 */
	protected function generate_articles_html( $attr, $articles ) {

		$html = "<div class='$attr[class] spri-naver-search pull-left' >";


		foreach ( $articles as $item ) {
			$template = "";
			require( plugin_dir_path( __FILE__ ) . "template/" . $attr['template'] . ".php" );
			$html .= $template;
		}

		$html .= "</div>";

		return $html;
	}

	private function add_naver_bi() {

		$path = plugins_url( "/img/powered_by_NAVER.png", __FILE__ );
		$bi   = <<< BIHTML
<a href="http://developer.naver.com/wiki/pages/OpenAPI" target="_blank" class="pull-right naver-bi">
    <img src="{$path}" alt="NAVER OpenAPI" />
</a>
BIHTML;

		return $bi;
	}

	/**
	 * run at wp_head action
	 */
	function load_css() {
		$plugin_url = plugin_dir_url( __FILE__ );

		wp_enqueue_style( 'spri-naver-style', $plugin_url . 'css/style.css' );
	}

	/**
	 * add new url param filter on wordpress valid filter list
	 *
	 * @param $q
	 *
	 * @return array
	 */
	function url_query_filter( $q ) {
		$q[] = 'ym';

		return $q;
	}

	function add_plugin_setting_link( $links ) {
		$setting_link = '<a href="' . admin_url( 'options-general.php?page=spri-naver-search' ) . '">' . __( Settings ) . '</a>';

		return array_merge( $links, array( $setting_link ) );
	}

	function register_article_widget() {
		register_widget( 'spri_naver_article_widget' );
	}

}

// And here we go
new spri_naver_news();
