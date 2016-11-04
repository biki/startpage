<?php

class RSSParser {
	private $xml = null;
	private $array = null;

	public function __construct($rssUrl) {
		try {
			$curl = curl_init();
			curl_setopt_array($curl, [
				CURLOPT_URL            => $rssUrl,
				CURLOPT_USERAGENT      => "spider",
				CURLOPT_TIMEOUT        => 120,
				CURLOPT_CONNECTTIMEOUT => 30,
				CURLOPT_RETURNTRANSFER => TRUE,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_ENCODING       => "UTF-8",
				CURLOPT_HEADER	       => false
			]);
			$data = curl_exec($curl);
			
			if($data === FALSE) {
				throw new Exception(curl_error($curl), curl_errno($curl));
			}

			curl_close($curl);

			$this->xml = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NOCDATA);
			return $this->xml;
		} catch(Exception $e) {
			trigger_error(sprintf('Curl failed with error #%d: %s', $e->getCode(), $e->getMessage()), E_USER_ERROR);
		}
	}

	public function getAsArray() {
		if($this->array == null) {
			$this->array = json_decode(json_encode($this->xml), true);
		}
		return $this->array;
	}
}

class MediaType {
	const SHOW = 1;
	const MOVIE = 2;
}

class RSSItem {
	public $data = null;
	public $type = null;
	public $uploadedUrl = null;

	public function __construct($item, $type) {
		unset($item["description"]);
		$this->data = $item;
		$this->type = $type;
		$this->getUploadedUrl();
		$this->getReadableName();
	}

	private function getReadableName() {
		$value = $this->data["title"];
		if(stripos($value, "&") !== false) {
			$value = substr($value, 0, strpos($value, "&"));
		}

		$words = preg_split('/[.]/', $value);
        $words = array_filter($words, create_function('$var','return !(preg_match("/(?:HDTV|bluray|\w{2,3}rip)|(?:x264)|(?:\d{4})|(?:\d{3,4}p)|(?:AC\d)/i", $var));'));
        $betterName = join($words, " ");

        $this->data["title"] = $betterName;
	}

	private function getUploadedUrl() {
		if($this->uploadedUrl != null) return $this->uploadedUrl;
		
		if(isset($this->data["enclosure"])) {
			foreach($this->data["enclosure"] as $link) {
				$link = flatten($link)["url"];
				if(strpos($link, "uploaded.net") !== false && strpos($link, "720p") !== false) {
					$arr = explode('/', $link);
					$id = implode('/', array_slice($arr, 4, 1));
					$this->uploadedUrl = "http://ul.to/$id";
				}
			}
		}
	}
}