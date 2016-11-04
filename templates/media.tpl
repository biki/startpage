<div class="ui tiny relaxed list">
	{% for item in media %}
		<div class="item">
			{% if item.type == 1 %}
				<i class="desktop icon"></i>
			{% else %}
				<i class="film icon"></i>
			{% endif %}
			<div class="content">
    			<a class="header" href="{{ item.data.link }}">{{ item.data.title }}</a>
    			{# <div class="description" style="font-size: 90%; line-height: 1.6;">
    				{% if item.uploadedUrl %}
    					<a href="{{ item.uploadedUrl }}"><i class="download icon"></i> {{ item.uploadedUrl }}</a><br>
    				{% endif %}
    				<i class="calendar icon"></i> {{ item.data.pubDate }}
    			</div> #}
			</div>
		</div>
	{% endfor %}
</div>