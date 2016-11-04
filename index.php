<?php

error_reporting(-1);
ini_set("display_errors", "On");
date_default_timezone_set("Europe/Berlin");

session_start();

require "config.php";

require "vendor/autoload.php";
require "vendor/redbean/rb.php";

require "class.RSSParser.php";
require "class.GoogleCalendar.php";

R::setup("mysql:host=127.0.0.1;dbname=countdowns", "root", "root");

$app = new \Slim\App([
    "settings" => [
        "displayErrorDetails" => true
    ],
]);

$container = $app->getContainer();

$container["view"] = function($container) {
	$view = new \Slim\Views\Twig("templates", [
		"debug" => true,
	    "cache" => "cache"
	]);
	$view->addExtension(new \Slim\Views\TwigExtension(
	    $container["router"],
	    $container["request"]->getUri()
	));

	return $view;
};

function flatten(array $array) { 
	$return = array(); 
	array_walk_recursive($array, function($a, $b) use (&$return) { 
		$return[$b] = $a; 
	}); 
	return $return;
}

$calendar = new GoogleCalendar();

/**
 * Renders the homepage.
 */
$app->get("/", function($request, $response, $args) use ($calendar) {
	if(isset($_GET["code"])) {
		try {
			$token = $calendar->getClient()->authenticate($_GET["code"]);
			file_put_contents("credentials.json", $token);
			$calendar->setToken($token);
		} catch(Google_Auth_Exception $e) {
			// TODO maybe was machen hier?
		}

		return $response->withRedirect($this->get("router")->pathFor("home"));
	}

	return $this->view->render($response, "index.tpl", $args);
})->setName("home");

/**
 * Redirects to the Google OAuth page.
 */
$app->get("/auth", function($request, $response, $args) use ($calendar) {
	$url = $calendar->getAuthUrl();
	return $response->withRedirect(filter_var($url, FILTER_SANITIZE_URL));
});

/**
 * Fetches current series download links via RSS.
 * Called via Ajax.
 */
$app->get("/getSeries", function($request, $response, $args) {
	$items = [];
	$data = (new RSSParser("http://www.ddlvalley.cool/category/tv-shows/hd-720/feed/"))->getAsArray();
	foreach($data["channel"]["item"] as $item) {
		$items[] = new RSSItem($item, MediaType::SHOW);
	}

	$args["media"] = $items;

	return $this->view->render($response, "media.tpl", $args);
});

// $app->get("/seriesCalendar", function($request, $response, $args) {
// 	$items = [];
// 	$data = new RSSParser("https://episodecalendar.com/en/rss_feed/bennykaspar@gmail.com/");
// 	$data = $data->getAsArray()["channel"]["item"];
// 	foreach($data as $key => $item) {
// 		$items[$item["title"]] = $item;
// 		if(!isset($item["episodes"]["episode"][0])) {
// 			$tmp = $item["episodes"]["episode"];
// 			unset($items[$item["title"]]["episodes"]["episode"]);
// 			$items[$item["title"]]["episodes"]["episode"][0] = $tmp;
// 		}		
// 	}

// 	// var_dump($items); die;
	
// 	$args["series"] = $items;

// 	return $this->view->render($response, "series.tpl", $args);
// });

/**
 * Fetches current movie download links via RSS.
 * Called via Ajax.
 */
$app->get("/getMovies", function($request, $response, $args) {
	$items = [];
	$data = (new RSSParser("http://www.ddlvalley.cool/category/movies/bluray-720p/feed/"))->getAsArray();
	foreach($data["channel"]["item"] as $item) {
		$items[] = new RSSItem($item, MediaType::MOVIE);
	}

	$args["media"] = $items;

	return $this->view->render($response, "media.tpl", $args);
});

/**
 * Renders the events.
 * Is called via Ajax to refresh the list.
 */
$app->get("/renderEvents", function($request, $response, $args) {
	$timeNow = date("Y-m-d");

	$events = R::find("countdown", "date >= ? ORDER BY date ASC", [ $timeNow ]);

	$dtToday = new DateTime($timeNow);
	foreach($events as $key => $countdown) {
		$dtCountdown = new DateTime($countdown["date"]);
		$diff = $dtToday->diff($dtCountdown);

		if($diff->days == 0) {
			$events[$key]["today"] = true;
			$events[$key]["remaining"] = "today";
		} elseif($diff->days == 1) {
			$events[$key]["remaining"] = "tomorrow";
		} elseif($diff->days > 1) {
			$events[$key]["remaining"] = str_pad($diff->format("%a"), 2, '0', STR_PAD_LEFT) . " days";
		}

		$events[$key]["prettyDate"] = $dtCountdown->format("d.m.y");
	}
	$args["events"] = $events;

	return $this->view->render($response, "events.tpl", $args);
});

/**
 * Synchronizes all events with the Google Calendar.
 */
$app->get("/sync", function($request, $response, $args) use ($calendar) {
	$events = R::findAll("countdown");
	foreach($events as $event) {
		if($event->calendarIdentifier == null) {
			$googleEvent = $calendar->addAllDayEvent($event->text, $event->date);
			$event->calendarIdentifier = $googleEvent->id;
			R::store($event);
		}
	}
	echo "Done! :)";
});

/**
 * Unsynchronizes all events with the Google Calendar.
 */
$app->get("/unsync", function($request, $response, $args) use ($calendar) {
	$events = R::findAll("countdown");
	foreach($events as $event) {
		if($event->calendarIdentifier != null) {
			$googleEvent = $calendar->removeEvent($event->calendarIdentifier);
			$event->calendarIdentifier = null;
			R::store($event);
		}
	}
	echo "Done! :)";
});

/**
 * Adds a new date/event/countdown
 */
$app->post("/add", function($request, $response, $args) use ($calendar) {
	$event = R::dispense("countdown");
	$event->date = $request->getParsedBody()["date"];
	$event->text = $request->getParsedBody()["text"];
	$event->timeCreated = time();

	// Add event to Google Calendar
	$googleEvent = $calendar->addAllDayEvent($event->text, $event->date);
	$event->calendarIdentifier = $googleEvent->id;

	R::store($event);
});

/**
 * Deletes an event.
 */
$app->post("/delete", function($request, $response, $args) use ($calendar) {
	$id = $request->getParsedBody()["id"];
	$event = R::load("countdown", $id);
	if($event->id != null) {
		// Delete event from Google Calendar
		if($event->calendarIdentifier != null) {
			$calendar->removeEvent($event->calendarIdentifier);
		}

		R::trash($event);
	}	
});

$app->run();

R::close();