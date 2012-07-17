<?php

interface MetadataProvider
{
    public function provides($key);
    public function has($target, $key);
    public function set($target, $key, $value);
    public function get($target, $key, $options);
    public function id();
}
