<?php

/*
Plugin Name: SPRI Naver news Search Api
Version: 1.0
Author: ungsik.yun@gmail.com
Description: Shortcode generating specific search result from naver.
*/

// http://developer.naver.com/wiki/pages/News

/*
 * Key and query is required
 * */
require_once( "spri-naver-search-option.php" );

class spri_naver_news {

	private $article_table;
	private $query_table;
	private $status_table;
	private $options;

	function __construct() {

		// database table names
		global $wpdb;
		$this->article_table = $wpdb->prefix . "spri_naver_news_article";
		$this->query_table = $wpdb->prefix . "spri_naver_news_query";
		$this->status_table = $wpdb->prefix . "spri_naver_news_status";

		// shortcodes
		add_shortcode( 'spri-naver-search', array( $this, 'naver_search' ) );

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

		// option page creating
		new spri_naver_option();

		// load options
		$this->options = get_option( 'spri_naver_option_name' );
	}

	function activation() {
		$this->set_up_database();
		$this->cron_job_registration();

	}

	function deactivation() {
		$this->cron_job_clear();
	}

	function set_up_database() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql1 = "CREATE TABLE $this->article_table (
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

		$sql2 = "CREATE TABLE $this->query_table (
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

		$sql3 = "CREATE TABLE $this->status_table (
id int(11) NOT NULL AUTO_INCREMENT,
`status` VARCHAR(20) NOT NULL,
index (id),
INDEX (status)
) $charset_collate;
";
//INSERT INTO $this->status_table
//(`status`)
//VALUES
// ('NEW'),
// ('MAINTENANCE');
//";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql1 );
		dbDelta( $sql2 );
		dbDelta( $sql3 );
	}

	function cron_job_registration() {
		//wp_schedule_event( time(), 'ten_seconds', 'spri_naver_cron_job' );
		wp_schedule_event( time(), 'twicedaily', 'spri_naver_cron_job' );
	}

	function cron_job_clear() {
		wp_clear_scheduled_hook( 'spri_naver_cron_job' );
	}

	/**
	 *cron function for crawling.
	 *Crawl the naver news with query and insert result into database
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
	 * Maintenance status
	 * query have this status are already crawled past articles.
	 * So crawl daily updates after whole article crawl.
	 */
	function maintenance_crawl( $q ) {
		global $wpdb;

		$attr = array(
			'key'     => $this->options['api_key'],
			'query'   => $q->query,
			'target'  => 'news',
			'display' => '100',
			'start'   => '1',
			'sort'    => 'sim',
		);

		$xml = $this->get_naver_xml( $attr );

		$total_page = $xml->channel->total / 100;
		if ( $total_page > 10 ) {
			$total_page = 10;
		}

		$articles = $this->get_search_results_from_naver( $attr, $total_page );

		$this->insert_articles( $q->id, $articles );
	}

	/**
	 * New status
	 * new to crawl. query have this status does not have any articles on database
	 */
	function new_crawl( $attr ) {
		global $wpdb;
		//$wpdb->show_errors();

		$attr['display'] = '100';
		$attr['sort'] = 'sim';

		$xml = $this->get_naver_xml( $attr );

		$total_page = $xml->channel->total / 100;
		if ( $total_page > 10 ) {
			$total_page = 10;
		}

		$articles = $this->get_search_results_from_naver( $attr, $total_page );

		$q_hash = sha1( $attr['query'] );
		$query_id_sql = "select id from $this->query_table where query_hash = '$q_hash';";
		$query_id = $wpdb->get_row( $query_id_sql );

		$this->insert_articles( $query_id->id, $articles );
	}

	public function naver_search( $attr ) {

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
		),
			$attr );

		//check if search is first time
		if ( ! $this->is_exist_query( $attr['query'] ) ) {
			//	query is not in db
			$this->insert_query( $attr['query'] );
			$this->new_crawl( $attr );
		}
		$q_id = $this->get_query_id_from_query( $attr['query'] );

		// Check page parameter.
		// Get article by parameter
		$articles = $this->retrive_articles( $q_id );

		// building html part
		$html = $this->generate_html( $attr, $articles );
		$html .= $this->append_year_month_link( $q_id );

		//TODO "Show more article"

		return $html;
	}

	function is_exist_query( $q ) {
		global $wpdb;

		$q_hash = sha1( $q );
		$sql = "select exists
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

	public function get_naver_xml( $attr ) {

		//Get xml content
		$url = "http://openapi.naver.com/search?";
		$url .= http_build_query( $attr );

		$xml = simplexml_load_string( file_get_contents( $url ) );

		return $xml;
	}

	protected function retrive_articles( $q_id ) {
		global $wpdb;
		global $wp_query;

		//TODO sanitize $ym

		if ( isset( $wp_query->query_vars['ym'] ) ) {
			$ym = $wp_query->query_vars['ym'];
		} else {
			date_default_timezone_set( "Asia/Seoul" );
			$ym = date( "Ym" );
		}

		$sql = "select *
				from $this->article_table
				where query_id = {$q_id}
				and date_format(pubDate, '%Y%m') = {$ym}
				order by pubDate desc";

		$articles = $wpdb->get_results( $sql );

		return $articles;
	}

	protected function generate_html( $attr, $articles ) {

		$article_count = count( $articles );

		$html = "<p>{$article_count} 개</p>";

		$html .= "<div class='$attr[class]' >";

		foreach ( $articles as $item ) {
			require( plugin_dir_path( __FILE__ ) . "template/" . $attr['template'] . ".php" );
			$html .= $template;
		}

		$html .= "</div>";

		return $html;
	}

	function url_query_filter( $q ) {
		$q[] = 'ym';

		return $q;
	}

	/**
	 * @param $q
	 *
	 * @return int $id query id from query string
	 */
	protected function get_query_id_from_query( $q ) {
		global $wpdb;

		//escaping quotes from query string
		$q = addslashes( $q );
		$query_id = $wpdb->get_row(
			"SELECT id FROM $this->query_table WHERE query = '{$q}'"
		);

		return (int) $query_id->id;
	}

	/**
	 * @param int $q_id query id from database
	 */
	private function append_year_month_link( $q_id ) {

		$ym_data = $this->get_year_month_data_by_query_id( $q_id );

		$buttons = "<div class='spri-naver-search'>";

		foreach ( $ym_data as $item ) {
			$buttons .=
				<<<HTML
				<a href="?ym={$item->ym}" class="link">{$item->y}년 {$item->m}월</a>
HTML;
		}

		$buttons .= "</div>";

		return ( $buttons );
	}

	function load_css() {
		$plugin_url = plugin_dir_url( __FILE__ );

		wp_enqueue_style( 'spri-naver-style', $plugin_url . 'css/style.css' );
	}

	private function get_year_month_data_by_query_id( $q_id ) {

		global $wpdb;

		$sql = <<<SQL
			SELECT date_format(pubDate, '%Y%m') AS ym,
YEAR(pubDate) as y,
month(pubDate) as m,
count(*) as c
FROM wp_spri_naver_news_article
WHERE query_id = {$q_id}
GROUP BY ym
ORDER BY ym DESC;
SQL;

		return $wpdb->get_results( $sql );
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

	private function get_query_list() {
		global $wpdb;

		return $wpdb->get_results( "
		SELECT * FROM wp_spri_naver_news_query
		" );
	}

	protected function get_search_results_from_naver( $attr, $total_page ) {
		$articles = array();
		$attr['start'] = 1;
		for ( $i = 1; $i <= $total_page; $i ++ ) {
			$t = $this->get_naver_xml( $attr );
			foreach ( $t->channel->item as $article ) {
				if ( $article->originallink == "" ) {
					$article->originallink = $article->link;
				}
				array_push( $articles,
					$article
				);
			}
			$attr['start'] = $attr['display'] * $i;
		}

		return $articles;
	}

	/**
	 * @param $q
	 * @param $articles
	 * @param $wpdb
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

	function add_plugin_setting_link( $links ) {
		$setting_link = '<a href="' . admin_url( 'options-general.php?page=spri-naver-search' ) . '">' . __( Settings ) . '</a>';
		$t = '<a href="google.com">ddddddd</a>';

		//$setting_link = "asd";
		return array_merge( $links, array( $setting_link ) );
		//return $links;
	}

}

// And here we go
new spri_naver_news();
