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

class spri_naver_search {
	function __construct(){
		add_shortcode('spri-naver-search', array($this, 'naver_search'));
		register_activation_hook( __FILE__, array($this, 'activation' ));
	}

	function naver_search($attr){
		$attrs =  shortcode_atts( array(
			'key' => 'c1b406b32dbbbbeee5f2a36ddc14067f', // dummy key
			'query' => 'SPRI',
			'target' => 'news',
			'display' => '10',
			'start' => '1',
			'sort' => 'sim',
			'class' => 'spri-naver-search',
			'template' => 'basic',
		), $attr );

		$url = "http://openapi.naver.com/search?";
		$url .= http_build_query($attrs);

		$data =file_get_contents($url);
		$xml = simplexml_load_string($data);

		$temp = array();

		foreach($xml->channel->item as $data) {
			$temp[] = $data;
		}
		usort($temp, function($a, $b){
			$a_date = date_create_from_format('D, d M Y H:i:s T', $a->pubDate);
			$b_date = date_create_from_format('D, d M Y H:i:s T', $b->pubDate);

			return $a_date < $b_date;
		});

		$html = "<div class='$attrs[class]' >";

		foreach($temp as $data){
			$title = (string)$data->title;
			$link = (string)$data->link;
			$originallink = (string)$data->originallink;
			$description = (string)$data->description;
			$pubDate = date_create_from_format('D, d M Y H:i:s T', $data->pubDate);

			require (plugin_dir_path(__FILE__)."template/".$attrs['template'].".php");

		}

		$html .= "</div>";

		return $html;
	}

	function activation(){
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$table_name      = $wpdb->prefix . "spri_naver_search";

		$sql = "CREATE TABLE $table_name (
				id INT(11) NOT NULL AUTO_INCREMENT,
				title VARCHAR(50) NOT NULL,

				PRIMARY KEY (id),
				) $charset_collate;
				";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
}

new spri_naver_search();

//register_activation_hook( __FILE__, 'spri_chart_create_table' );

