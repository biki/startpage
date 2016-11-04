<div class="ui tiny relaxed list">
	{% for event in events %}
		<div class="item" data-event-id="{{ event.id }}">
			<div class="right floated content">
				{{ event.prettyDate }} <span class="muted">({{ event.remaining }})</span>
			</div>
			<div class="content">
    			{{ event.text }}
			</div>
		</div>
	{% endfor %}
</div>