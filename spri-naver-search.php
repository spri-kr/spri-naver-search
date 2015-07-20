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

class spri_naver_news {

	private $article_table;
	private $query_table;
	private $status_table;

	function __construct() {

		global $wpdb;
		$this->article_table = $wpdb->prefix . "spri_naver_news_article";
		$this->query_table = $wpdb->prefix . "spri_naver_news_query";
		$this->status_table = $wpdb->prefix . "spri_naver_news_status";


		add_shortcode( 'spri-naver-search', array( $this, 'naver_search' ) );
		add_action( 'spri_naver_cron_job', array( $this, 'do_cron_job' ) );
		add_filter( 'query_vars', array( $this, 'url_query_filter' ) );

		register_activation_hook( __FILE__, array( $this, 'activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivation' ) );
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
				PRIMARY KEY (id),
				index(query_id, pubDate),
				index(query_id),
				index(title),
				index(link),
				index(uniqueness_hash),
				index(pubDate),
				UNIQUE (uniqueness_hash)
				) $charset_collate;
				";

		$sql2 = "CREATE TABLE $this->query_table (
				id int(11) NOT NULL AUTO_INCREMENT,
				query VARCHAR(100) NOT NULL,
				query_hash char(40) NOT NULL,
				status VARCHAR(20),
				PRIMARY KEY (id),
				index (id, query_hash),
				index (query_hash),
				index (query),
				index (id),
				index (status)

				) $charset_collate;
				";

		$sql3 = "CREATE TABLE $this->status_table (
				id int(11) NOT NULL AUTO_INCREMENT,
				status VARCHAR(20) NOT NULL,

				PRIMARY KEY (id),
				INDEX (status)
				) $charset_collate;

				INSERT INTO $this->status_table
				VALUES
				 ('NEW'),
				 ('MAINTENANCE');
		";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql1 );
		dbDelta( $sql2 );
		dbDelta( $sql3 );
	}

	function cron_job_registration() {
		wp_schedule_event( time(), 'daily', 'spri_naver_cron_job' );
	}

	function cron_job_clear() {
		wp_clear_scheduled_hook( 'spri_naver_cron_job' );
	}

	/**
	 *cron function for crawling.
	 *Crawl the naver news with query and insert result into database
	 */
	function do_cron_job() {

		global $wpdb;

		//$sql = $wpdb->prepare( "
		//	SELECT * FROM %s WHERE
		//",
		//	array() );
		$query_and_status = $wpdb->get_results( '' );
		//
		//$this->maintenance_crawl();
		//$this->new_crawl();
	}

	/**
	 * Maintenance status
	 * query have this status are already crawled past articles.
	 * So crawl daily updates after whole article crawl.
	 */
	function maintenance_crawl( $q ) {
		global $wpdb;

	}

	/**
	 * New status
	 * new to crawl. query have this status does not have any articles on database
	 */
	function new_crawl( $attr ) {
		global $wpdb;
		$wpdb->show_errors();

		$attr['display'] = '100';
		$attr['sort'] = 'sim';
		unset( $attr['class'] );
		unset( $attr['template'] );

		$xml = $this->get_naver_xml( $attr );

		$total_page = $xml->channel->total / 100;
		if ( $total_page > 10 ) {
			$total_page = 10;
		}

		$articles = array();
		for ( $i = 1; $i <= $total_page; $i ++ ) {
			$attr['start'] = $attr['display'] * $i;
			$t = $this->get_naver_xml( $attr );
			foreach ( $t->channel->item as $article ) {
				array_push( $articles,
					$article
				);
			}
		}

		$q_hash = sha1( $attr['query'] );
		$query_id_sql = "select id from $this->query_table where query_hash = '$q_hash';";
		$query_id = $wpdb->get_row( $query_id_sql );

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
					(int) $query_id->id,
					$article->title . (string) $query_id->id . $article->pubDate,
				)
			);

			$wpdb->query( $insert_sql );
			//$wpdb->show_errors();
		}

		//	TODO set query status to maintenance
	}

	function naver_search( $attr ) {
		// Set default value
		$attr = shortcode_atts( array(
			'key'      => 'c1b406b32dbbbbeee5f2a36ddc14067f', // dummy key
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

		// Check page parameter.
		// Get article by parameter
		global $wp_query;
		if ( isset( $wp_query->query_vars['spri_y_m'] ) ) {
			$yearMonth = $wp_query->query_vars['spri_y_m'];
			$articles = $this->retrive_articles( $attr['query'], $yearMonth );
		} else {
			$articles = $this->retrive_articles( $attr['query'] );
		}

		$html = $this->generate_html( $attr, $articles );

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
			array(
				$q,
				$q
			)
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

	protected function retrive_articles( $q, $ym = "" ) {
		global $wpdb;

		$query_id = $this->get_query_id_from_query( $q );

		$articles = $wpdb->get_results( "select * from $this->article_table where query_id = {$query_id}" );

		return $articles;
	}

	protected function generate_html( $attr, $articles ) {
		$html = "<div class='$attr[class]' >";


		foreach ( $articles as $item ) {
			$title = (string) $item->title;
			$link = (string) $item->link;
			$originallink = (string) $item->originallink;
			$description = (string) $item->description;
			$pubDate = (string)$item->pubDate;
			require( plugin_dir_path( __FILE__ ) . "template/" . $attr['template'] . ".php" );
			$html .= $template;
		}

		$html .= "</div>";

		return $html;
	}

	function url_query_filter( $q ) {
		$q[] = 'spri_y_m';

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

}

// And here we go
new spri_naver_news();
