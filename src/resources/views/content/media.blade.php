@if($content->media_id)
    {!! image_tag($content->media_id, null, 900, "media_{$content->slug}_{$content->media_id}") !!}
@endif