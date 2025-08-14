<?php
    $value = $value ?? null;
?>

<template x-if="type === 'media'">
    @include('media-manager::form.media', ['label' => __('core-cms::admin.value'), 'name' => 'value', 'value' => $value])
</template>