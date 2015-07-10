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
	function __construct() {
		add_shortcode( 'spri-naver-search', array( $this, 'naver_search' ) );
		register_activation_hook( __FILE__, array( $this, 'activation' ) );
	}

	function naver_search( $attr ) {
		$attrs = shortcode_atts( array(
			'key'      => 'c1b406b32dbbbbeee5f2a36ddc14067f', // dummy key
			'query'    => 'SPRI',
			'target'   => 'news',
			'display'  => '10',
			'start'    => '1',
			'sort'     => 'sim',
			'class'    => 'spri-naver-search',
			'template' => 'basic',
		), $attr );

		//Get xml content
		$url = "http://openapi.naver.com/search?";
		$url .= http_build_query( $attrs );

		$data = file_get_contents( $url );
		$xml  = simplexml_load_string( $data );

		//check if search is first time
		if ( $this->is_exist_query( $attrs->query ) ) {


		}


		$temp = array();

		foreach ( $xml->channel->item as $data ) {
			$temp[] = $data;
		}
		usort( $temp, function ( $a, $b ) {
			$a_date = date_create_from_format( 'D, d M Y H:i:s T', $a->pubDate );
			$b_date = date_create_from_format( 'D, d M Y H:i:s T', $b->pubDate );

			return $a_date < $b_date;
		} );

		$html = "<div class='$attrs[class]' >";

		foreach ( $temp as $data ) {
			$title        = (string) $data->title;
			$link         = (string) $data->link;
			$originallink = (string) $data->originallink;
			$description  = (string) $data->description;
			$pubDate      = date_create_from_format( 'D, d M Y H:i:s T', $data->pubDate );

			require( plugin_dir_path( __FILE__ ) . "template/" . $attrs['template'] . ".php" );

		}

		$html .= "</div>";

		return $html;
	}

	function activation() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$article_table = $wpdb->prefix . "spri_naver_news_article";
		$query_table   = $wpdb->prefix . "spri_naver_news_query";

		$sql1 = "CREATE TABLE $article_table (
				id INT(11) NOT NULL AUTO_INCREMENT,
				query_id int(11) NOT NULL,
				title VARCHAR(50) NOT NULL,
				title_hash BINARY(20) NOT NULL,
				originallink VARCHAR(512),
				link VARCHAR(512) NOT NULL,
				description VARCHAR(512) NOT NULL,
				pubDate DATE NOT NULL,
				PRIMARY KEY (id),
				index(query_id, pubDate),
				index(query_id),
				index(title),
				index(title_hash),
				index(pubDate)
				) $charset_collate;
				";

		$sql2 = "CREATE TABLE $query_table (
				id int(11) NOT NULL AUTO_INCREMENT,
				query VARCHAR(100) NOT NULL,
				query_hash char(40) NOT NULL,
				PRIMARY KEY (id),
				index (id, query_hash),
				index (query_hash),
				index (query),
				index (id)

				) $charset_collate;
				";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql1 );
		dbDelta( $sql2 );
	}

	function is_exist_query( $q ) {
		global $wpdb;

		$q_hash = hexdec( sha1( $q ) );
		$sql = "select exists(select * from wp_spri_naver_news_query where query_hash = 'f71e27b99793c8ae57803ae4a55d7a0ff390e260') AS exist";
		$r = $wpdb->get_row($sql);

		return $r->exist;
		//$wpdb->get
	}
}

new spri_naver_news();

//register_activation_hook( __FILE__, 'spri_chart_create_table' );