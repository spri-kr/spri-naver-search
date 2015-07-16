<?php

/*
Plugin Name: Spri Naver news Search Api
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
		$this->query_table   = $wpdb->prefix . "spri_naver_news_query";
		$this->status_table  = $wpdb->prefix . "spri_naver_news_status";


		add_shortcode( 'spri-naver-search', array( $this, 'naver_search' ) );
		add_action( 'spri_naver_cron_job', array( $this, 'do_cron_job' ) );

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
				originallink VARCHAR(512),
				link VARCHAR(512) NOT NULL,
				description VARCHAR(512) NOT NULL,
				pubDate VARCHAR(20) NOT NULL,
				orilink_query_hash CHAR(40) NOT NULL,
				PRIMARY KEY (id),
				index(query_id, pubDate),
				index(query_id),
				index(title),
				index(link),
				index(link_hash),
				index(pubDate),
				UNIQUE (orilink_query_hash)
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

				PRIMARY KEY (status)
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

		$sql              = $wpdb->prepare( "
			select * from %s where
		",
			array() );
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
		$attr['sort']    = 'sim';
		unset( $attr['class'] );
		unset( $attr['template'] );

		$xml        = $this->get_naver_xml( $attr );
		$total_page = $xml->channel->total / 100;
		if ( $total_page > 10 ) {
			$total_page = 10;
		}

		$articles = array();
		for ( $i = 1; $i <= $total_page; $i ++ ) {
			$attr['start'] = $attr['display'] * $i;
			$t             = $this->get_naver_xml( $attr );
			foreach ( $t->channel->item as $article ) {

				array_push( $articles,
					$article
				);
			}
		}

		$q_hash       = sha1( $attr['query'] );
		$query_id_sql = "select id from $this->query_table where query_hash = '$q_hash';";
		$query_id     = $wpdb->get_row( $query_id_sql );

		$s = "";
		foreach ( $articles as $article ) {

			$pubDate = date_create_from_format( 'D, d M Y H:i:s T', $article->pubDate );

			$insert_sql = $wpdb->prepare( "
				INSERT INTO $this->article_table
				(title, originallink, link, description, pubDate, query_id, orilink_query_hash)
				VALUES
				(%s, %s, %s, %s, %s, %d, SHA1(%s));",
				array(
					$article->title,
					$article->originallink,
					$article->link,
					$article->description,
					$pubDate->format( 'Y-m-d H:i:s' ),
					(int) $query_id->id,
					$article->originallink . (string)$query_id->id,
				)
			);
			$wpdb->query( $insert_sql );
			$wpdb->show_errors();
		}
		//	set query status to maintenance
		//return var_dump($s);
	}

	function naver_search( $attr ) {
		$attr = shortcode_atts( array(
			'key'      => 'c1b406b32dbbbbeee5f2a36ddc14067f', // dummy key
			'query'    => 'SPRI',
			'target'   => 'news',
			'display'  => '10',
			'start'    => '1',
			'sort'     => 'sim',
			'class'    => 'spri-naver-search',
			'template' => 'basic',
		), $attr );

		return $this->new_crawl( $attr );

		//check if search is first time
		//	query is in db
		if ( $this->is_exist_query( $attr['query'] ) ) {

			$this->get_news_articles_by_query();

		} //	query is not in db
		else {

			$this->insert_query( $attr['query'] );

			return $this->new_crawl( $attr );

		}


		$xml  = $this->get_naver_xml( $attr );
		$temp = array();

		foreach ( $xml->channel->item as $data ) {
			$temp[] = $data;
		}
		usort( $temp, function ( $a, $b ) {
			$a_date = date_create_from_format( 'D, d M Y H:i:s T', $a->pubDate );
			$b_date = date_create_from_format( 'D, d M Y H:i:s T', $b->pubDate );

			return $a_date < $b_date;
		} );

		$html = "<div class='$attr[class]' >";

		foreach ( $temp as $data ) {
			$title        = (string) $data->title;
			$link         = (string) $data->link;
			$originallink = (string) $data->originallink;
			$description  = (string) $data->description;
			$pubDate      = date_create_from_format( 'D, d M Y H:i:s T', $data->pubDate );

			require( plugin_dir_path( __FILE__ ) . "template/" . $attr['template'] . ".php" );

		}

		$html .= "</div>";

		return $html;
	}

	function is_exist_query( $q ) {
		global $wpdb;

		$q_hash = sha1( $q );
		$sql    = "select exists(select * from wp_spri_naver_news_query where query_hash = '$q_hash') AS exist";
		$r      = $wpdb->get_row( $sql );

		return $r->exist;
		//$wpdb->get
	}

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

	public function get_news_articles_by_query() {
		global $wpdb;
	}


}

// And here we go
new spri_naver_news();
