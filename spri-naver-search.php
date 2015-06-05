<?php

/*
Plugin Name: Spri Naver Search Api
Version: 1.0
Author: ungsik.yun@gmail.com
*/

// http://developer.naver.com/wiki/pages/News

/*
 * Key and query is required
 * */
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

add_shortcode('spri-naver-search', 'naver_search');

