<?php
    $value = $value ?? "";
?>

@if($value !== "")
    {!! image_tag($value, null, 150) !!}
@endif