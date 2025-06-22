<?php
// src/init.php
// Ustawienie strefy czasowej
date_default_timezone_set('Europe/Warsaw');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/auctions.php';
require_once __DIR__ . '/bids.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/admin.php';
