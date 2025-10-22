@php
    $imageHeight = $imageHeight ?? null;
@endphp


@if($content->media_id)
    {!! image_tag($content->media_id, null, $imageHeight, "media_{$content->slug}_{$content->media_id}") !!}
@endif