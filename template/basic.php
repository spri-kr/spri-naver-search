<?php
$template = <<< ARTICLE

<div class='item' >
<h4 class='title'><a href='{$item->originallink}' target='_blank'> {$item->title}</a></h4>
<p class='description'> {$item->description} </p>
<p class='pubdate'>{$item->pubDate}</p>
</div>

ARTICLE;
