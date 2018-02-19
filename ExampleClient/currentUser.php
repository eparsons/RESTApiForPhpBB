<?php
namespace RestClientForPhpbb;

require('RestClient.php');

$forum = new Forum();
$user = $forum->getCurrentUser();

var_dump($user);
