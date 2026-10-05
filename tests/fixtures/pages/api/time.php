<?php
use function Quire\name;

name('api.time');

// Returning an array sends JSON.
return ['time' => date(DATE_ATOM)];
