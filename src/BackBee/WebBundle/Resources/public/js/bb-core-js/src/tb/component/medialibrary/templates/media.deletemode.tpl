<div>
    {% if isOrphaned %}
        <p><strong>{{ "orphaned_media_delete_this_media" | trans }}?</strong></p>
    {% else %}
        <p><strong class='bb5-alert'>{{ "warning" | trans }},</strong></p>
        <p class='bb5-alert'>{{ "are_you_sure_you_want_to_delete_this_media" | trans }}?</p>
        <div data-content-page=''><p><strong>{{"this_media_is_being_used_on_the_following_pages" | trans }} :</strong></p>
            <div class='bb5-dialog-overflow-y'>
                <ul class='contents'>
                    {% for item in items %}
                        <li class='page-title'>{{item.title}}</li>
                    {% endfor %}
                </ul>
            </div>
        </div>
    {% endif %}
</div>
