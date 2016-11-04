<div class="ui four column grid">
    {% for item in series %}
    <div class="column">
        <div class="ui tiny list" {% if not loop.first %} style="opacity: .5;" {% endif %}>
            <div class="item">
                <i class="calendar icon"></i>
                <div class="content">
                    <div class="header">{{ item.title }}</div>
                    <div class="list">
                        {% for episodeList in item.episodes %} 
                          {% for episode in episodeList %}
                            <div class="item">
                                <i class="desktop icon"></i>
                                <div class="content">
                                    <div class="header">{{ episode.show }}</div>
                                    <div class="muted description">{{ episode.format }} {{ episode.name }}</div>
                                </div>
                            </div>
                          {% endfor %} 
                        {% endfor %}
                    </div>
                </div>
            </div>
        </div>
    </div>
    {% endfor %}
</div>
