<?php
require_once __DIR__ . '/../src/init.php';
require_role(['user', 'admin', 'moderator']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    delete_auction($_POST['id']);
}
header('Location: my-auctions');
exit;
