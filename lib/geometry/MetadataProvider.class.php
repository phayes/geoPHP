<?php

interface MetadataProvider
{
    public function hasMetadataKey($key);
    public function setMetadataKey($key, $value);
    public function getMetadataKey($key);
    public function getMetadata();
}
