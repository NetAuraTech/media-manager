<?php
    $content= $content ?? null;
    $openGraphLogo = $openGraphLogo ?? null;

    $image = $openGraphLogo;

    if($content && $content->media_id) {
        $image = image_url($content->media_id);
    }
?>

@if($image)
    <meta property='og:image' content="{{ $image }}"/>
    <meta name='twitter:image' content="{{ $image }}"/>
@endif