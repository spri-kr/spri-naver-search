<?php

$temp_date = date('Y년 m월 d일',strtotime($item->pubDate));
//$temp_date = $item->pubDate;
$template = <<< ARTICLE

<div class='item' >
<h4 class='title'><a href='{$item->originallink}' target='_blank'> {$item->title}</a></h4>
<p class='description'> {$item->description} </p>
<p class='pubdate'>{$temp_date}</p>
</div>

ARTICLE;
