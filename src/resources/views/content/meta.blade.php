<?php
    $defaultImage = $openGraphLogo ?? (object)['id' => '', 'width' => '', 'height' => '', 'alt' => ''];

    $image = image_url($defaultImage->id);
    $width = $defaultImage->width;
    $height = $defaultImage->height;

    $alt = method_exists($defaultImage, 'getDefaultAlt') ? $defaultImage->getDefaultAlt() : $defaultImage->alt;

    $contentMedia = $content->media ?? null;

    if ($contentMedia) {
        $image = image_url($contentMedia->id ?? '');
        $width = $contentMedia->width ?? '';
        $height = $contentMedia->height ?? '';
        $alt = $contentMedia->getDefaultAlt() ?? '';
    }
?>

@if($image)
    <meta property='og:image' content="{{ $image }}"/>
    <meta name='twitter:image' content="{{ $image }}"/>
    <meta property="og:image:width" content="{{ $width }}"/>
    <meta property="og:image:height" content="{{ $height }}"/>
    <meta property="og:image:alt" content="{{ $alt }}"/>
    <meta property="og:image:type" content="image/webp"/>
@endif