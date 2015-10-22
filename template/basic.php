<?php
/*
 * This file is basic template for display articles
 * */

$temp_date = date( 'Y년 m월 d일', strtotime( $item->pubDate ) );

$href = "href='{$item->originallink}'";
$target = "target='_blank'";

if ( $item->originallink == $item->link ) {
	$href = "";
	$target = "target='_self'";
}

//$temp_date = $item->pubDate;
$template .= <<< ARTICLE

<div class='item' >
<h4 class='title'><a {$href} {$target}> {$item->title}</a></h4>
<p class='description'> {$item->description} </p>
<p class='pubdate'>{$temp_date}</p>
</div>

ARTICLE;
