<?php
function _get_object_property($obj, $prop, $defaultValue = null) {
    return $obj[$prop] ?? $defaultValue;
}
  