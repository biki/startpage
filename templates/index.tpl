<!DOCTYPE html>
<html>
<head>
	<title>Hi!</title>

	<link rel="stylesheet" type="text/css" href="semantic/dist/semantic.min.css">
	<link rel="stylesheet" type="text/css" href="lib/main.css">

	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.0.0-rc1/jquery.min.js"></script>
	<script src="semantic/dist/semantic.min.js"></script>
	<script src="lib/moment.js"></script>
	<script src="lib/weather.js"></script>
</head>
<body>

<div class="ui container child">
	<div class="ui grid">
		<div class="sixteen wide column">
		    <div class="ui grid">
		        <div class="sixteen wide column">
		            <div class="ui center aligned segment">
						<div class="ui huge inverted header" id="time"></div>
		            	<div class="ui large inverted header" id="weather-temp"><i class="spinner loading icon"></i></div>
		            	<div class="ui tiny inverted header" id="weather-desc">&nbsp;</div>
		            </div>
		        </div>

		        <div class="sixteen wide column"><div class="ui divider"></div></div>

		        {% set size = "four" %}

		        <div class="centered row">
			        <div class="{{ size }} wide column">
			        	<div class="ui header"><i class="world icon"></i></div>
			        	<ul>
			        		<li><a href="http://inbox.google.com">inbox</a></li>
			        		<li><a href="http://www.facebook.com">facebook</a></li>
			        		<li><a href="http://youtube.com">youtube</a></li>
			        	</ul>
			        </div>
			        <div class="{{ size }} wide column">
			        	<div class="ui header"><i class="spy icon"></i></div>
			        	<ul>
			        		<li><a href="http://www.ddlvalley.cool/">ddlvalley</a></li>
			        		<li><a href="http://boerse.to/">boerse</a></li>
			        		<li><a href="http://bolt.cd/board">bolt</a></li>
			        	</ul>
			        </div>
			        <div class="{{ size }} wide column">
			        	<div class="ui header"><i class="reddit icon"></i></div>
			        	<ul>
			        		<li><a href="http://www.reddit.com">front</a></li>
			        		<li><a href="http://www.reddit.com/r/all/">r/all</a></li>
			        		<li><a href="http://www.reddit.com/r/elderscrollsonline/">r/elderscrollsonline</a></li>
			        	</ul>
			        </div>
			        <div class="{{ "three" }} wide column">
			        	<div class="ui header"><i class="at icon"></i></div>	
			        	<ul>
			        		<li><a href="https://merkurist.de/mainz">merkurist</a></li>
			        		<li><a href="https://lernen.h-da.de/">moodle</a></li>
			        		<li><a href="https://qis.h-da.de/">qis</a></li>
			        	</ul>
			        </div>
		        </div>

		        <div class="sixteen wide column"><div class="ui divider"></div></div>

		        <div class="three column row">
		        	<div class="column" id="seriesContainer"><i class="spinner loading icon"></i></div>
		        	<div class="column" id="moviesContainer"><i class="spinner loading icon"></i></div>	
		        	<div class="column">
		        		<div id="events"><i class="spinner loading icon"></i></div>

						<div class="ui form" style="display: none;">
							<div class="field">
								<input type="text" name="text" style="width: 150px;">
								<input type="date" name="date" style="width: 150px;">
								<button class="ui icon button"><i class="add icon"></i></button>
							</div>
						</div>
		        	</div>
		        </div>

		        <div class="sixteen wide column"><div class="ui divider"></div></div>

		        <div class="one column row">
		        	<div class="column">
		        		{# <div id="series"><i class="spinner loading icon"></i></div> #}
		        		<iframe src="https://episodecalendar.com/icalendar/bennykaspar@gmail.com/qTVq62zziktbpswbDxgm/?v=light" width="100%" height="500" frameborder="0" scrolling="auto" allowtransparency="true"></iframe>
		        	</div>
		        </div>
		    </div>
		</div>
	</div>
</div>

<script type="text/javascript">
$(document).ready(function() {
	$(document).keyup(function(e) {
	    if(e.keyCode == 65) {
	        $(".ui.form").show();
	    } else if(e.keyCode == 27) {
	    	$(".ui.form").hide();
	    }
	});

	function loadWeather() {
		$.simpleWeather({
			location: "Ginsheim",
			unit: "c",
			success: function(weather) {
				$("#weather-temp").html(weather.temp + " &deg;c");
				$("#weather-desc").html(weather.text);
			},
			error: function(error) {
				$("#weather").html('<p>'+error+'</p>');
			}
		});
	}

	function getSeries() {
		$.get("getSeries").done(function(r) {
			$("#seriesContainer").html(r);
		});
	}

	// function getCalendar() {
	// 	$.get("seriesCalendar").done(function(r) {
	// 		$("#series").html(r);
	// 	});
	// }

	function getMovies() {
		$.get("getMovies").done(function(r) {
			$("#moviesContainer").html(r);
		});
	}

	function renderEvents() {
		$.get("renderEvents").done(function(r) {
			$("#events").html(r);
		});
	}

	function updateTime() {
		$("#time").text(moment().format("HH:mm"));
	}

	$("#events").on("dblclick", "div.item", function() {
		if(!confirm("rly?")) return false;

		$.post("delete", {
			id: $(this).data("event-id")
		}).done(function(r) {
			renderEvents();
		});
	});

	$(".ui.icon.button").on("click", function() {
		$.post("add", {
			text: $("input[name='text']").val(),
			date: $("input[name='date']").val()
		}).done(function(r) {
			renderEvents();
			$(':input').val("");
		});
	});
	
	loadWeather();
	renderEvents();
	updateTime();
	getSeries();
	getMovies();
	// getCalendar();

	setInterval(function() {
    	updateTime();
	}, 60 * 1000);
});
</script>

</body>
</html>