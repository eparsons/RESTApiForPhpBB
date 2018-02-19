<?php
namespace RestClientForPhpbb;

require('RestClient.php');

$forum = new Forum();
$user = $forum->logout();

var_dump($user);
