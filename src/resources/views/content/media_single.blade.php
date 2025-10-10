@if($content->media_id)
    <div class="margin-block-start-6">
        {!! image_tag($content->media_id, null, 1312, "media_{$content->slug}_{$content->media_id}") !!}
    </div>
@endif