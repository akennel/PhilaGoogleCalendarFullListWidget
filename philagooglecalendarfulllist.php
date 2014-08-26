<?php
/* Plugin Name: Phila Google Calendar Full List Widget
Plugin URI: localhost/wordpress
Description: Shows Google Event Details. Meant for Event-List Page.
Version: 1.0
Author: Andrew Kennel
Author URI: localhost/wordpress
*/
add_shortcode('PhilaGoogleCalendarFullListWidget', 'philaGoogleCalendarFullListWidget_handler');

function philaGoogleCalendarFullListWidget_handler(){

	//set the total number of items to display
	$itemLimit = 10;
	$eventArray = array();

	//Array of calendar URLs
	//Be sure to limit the number of items returned by the $itemLimit
	$urlArray = array(
		'http://www.google.com/calendar/feeds/3efanutsrofqu273785lh789ko%40group.calendar.google.com/public/full?orderby=starttime&sortorder=ascending&max-results=' . $itemLimit . '&futureevents=true&alt=json',
		'http://www.google.com/calendar/feeds/7kd4f0sstd7k9065gjmpj7jrp8%40group.calendar.google.com/public/full?orderby=starttime&sortorder=ascending&max-results=' . $itemLimit . '&futureevents=true&alt=json'
	);

	foreach($urlArray as $key => $currentURL)
	{
		//Working locally we need to use a proxy server to return data. When deploying on server, change to direct read.
		$data = PhilaGoogleCalendarFullGetFeed($currentURL);
		//$data = PhilaGoogleCalendarFullGetFeedFromProxy($currentURL);
		foreach ($data->feed->entry as $item)
		{
			$array_item = (array) $item;
			
			$title = (array) $item->title;		
			$start = $array_item['gd$when'][0]->startTime;	
			$content = (array) $item->content;		
			
			$eventArray[] = array('title' => $title['$t'], 'startDate' => $start, 'content' => $content['$t']);
		}
	}
	
	//Sort our array of events by date
	function date_compare($a, $b)
	{
	    $t1 = strtotime($a['startDate']);
	    $t2 = strtotime($b['startDate']);
	    return $t1 - $t2;
	}    
	usort($eventArray, 'date_compare');
	
	//Protect against when event array is smaller than item limit
	if(sizeof($eventArray) < $itemLimit)
	{
		$itemLimit = sizeof($eventArray);
	}
	
	$eventCount = (int)0;
	$eventString = "<div id=\"PhilaGoogleCalendarEventSection\">";
	
	
	//Loop through the events until we hit the item limit
	while($eventCount < $itemLimit){
		$currentEvent = (array)$eventArray[$eventCount];
		$startDate = new DateTime($currentEvent['startDate']);//convert startdate string to DateTime object
		
		$eventString .= "<div id=\"Event$eventCount\" class=\"phila-event-details\">";
		$eventString .= "<div class=\"PhilaGoogleCalendarDateRow\">".date_format($startDate, 'l').", ".date_format($startDate, 'm/d/Y')."</div>"; 
		$eventString .= "<div class=\"PhilaGoogleCalendarTitleRow\">".date_format($startDate, 'g:i A')." - ".$currentEvent['title']."</div>";
		$eventString .= "<div class=\"PhilaGoogleCalendarContentRow\">".$currentEvent['content']."</div></div>";
		$eventCount++;
	}
	
	$eventString .= "</div>";
	
	$output = "<div id=\"PhilaGoogleCalendarWidget\" class=\"PhilaWidget\">";
	$output .= "	<span id=\"PhilaGoogleCalendarMainWindow\">";
	$output .= "		<h1 class=\"PhilaWidgetTitle\">Events</h1>";
	$output .= $eventString;
	$output .= "	</span>";
	$output .= "</div>";
	
	return $output;
}

function PhilaGoogleCalendarFullGetFeedFromProxy($url){
//Use local proxy server when runnign on dev machine
$aContext = array(
    'http' => array(
        'proxy' => 'tcp://127.0.0.1:3128',
        'request_fulluri' => true,
    ),
);
$cxContext = stream_context_create($aContext);
$data = json_decode(file_get_contents($url, True, $cxContext));

return $data;
}

function PhilaGoogleCalendarFullGetFeed($url){
//When running on server, no proxy is required
$data = json_decode(file_get_contents($url, True));

return $data;
}

function philaGoogleCalendarFullListWidget($args, $instance) { // widget sidebar output
  extract($args, EXTR_SKIP);
  echo $before_widget; // pre-widget code from theme
  echo philaGoogleCalendarFullListWidget_handler();
  echo $after_widget; // post-widget code from theme
}
?>
