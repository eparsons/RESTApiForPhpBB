<?php
namespace RestClientForPhpbb;

require('RestClient.php');

$forum = new Forum();
$user = $forum->login('user', 'pass', false);

var_dump($user);
