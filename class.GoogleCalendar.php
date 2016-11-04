<?php

class GoogleCalendar {
	const CREDENTIALS_FILE = "credentials.json";

	private $client = null;
	private $service = null;

	public function __construct() {
		$client = new Google_Client();
		$client->setClientId(CLIENT_ID);
		$client->setClientSecret(CLIENT_SECRET);
		$client->setRedirectUri("http://127.0.0.1/startpage");
		$client->setScopes(array(Google_Service_Calendar::CALENDAR));
		$client->setAccessType("offline");
		$this->client = $client;

		if(file_exists(GoogleCalendar::CREDENTIALS_FILE)) {
			$this->setToken(file_get_contents(GoogleCalendar::CREDENTIALS_FILE));
			$this->addService();
		}
	}

	private function addService() {
		$this->service = new Google_Service_Calendar($this->client);
	}

	public function addAllDayEvent($name, $date) {
		$event = new Google_Service_Calendar_Event(array(
			"summary" => $name,
			"start" => [
				"date" => $date, # 2015-05-01
				"timeZone" => "Europe/Berlin"
			],
			"end" => [
				"date" => $date,
				"timeZone" => "Europe/Berlin"
			]
		));

		return $this->service->events->insert("primary", $event);
	}

	public function addEvent($name, $start, $end) {
		$event = new Google_Service_Calendar_Event(array(
			"summary" => $name,
			"start" => [
				"dateTime" => $start, # 2015-05-01T00:00:00
				"timeZone" => "Europe/Berlin"
			],
			"end" => [
				"dateTime" => $end,
				"timeZone" => "Europe/Berlin"
			]
		));

		return $this->service->events->insert("primary", $event);
	}

	public function removeEvent($id) {
		return $this->service->events->delete("primary", $id);
	}

	public function setToken($token) {
		$this->client->setAccessToken($token);
		if($this->client->isAccessTokenExpired()) {
			$this->client->refreshToken($this->client->getRefreshToken());
			file_put_contents(GoogleCalendar::CREDENTIALS_FILE, $this->client->getAccessToken());
		}
	}

	public function getAuthUrl() {
		return $this->client->createAuthUrl();
	}

	public function getClient() {
		return $this->client;
	}
}