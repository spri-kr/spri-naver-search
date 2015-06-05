<?php
$html .= "<div class='item' >";
$html .= "<h4 class='title'><a href='$originallink' target='_blank'>" . $title . "</a></h4>";
$html .= "<p class='description'>" . $description . "</p>";
$html .= "<p class='pubdate'>" . date_format($pubDate, 'Y년 m월 d일'). "</p>";
$html .= "</div>";