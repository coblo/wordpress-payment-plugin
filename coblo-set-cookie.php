<?php

$cookie = $_GET["cookie"];

setcookie('coblo-id', $cookie, time()+60*60*24*30, "/", "");

echo '<h4>You got your cookie "' . $cookie . '" back.</h4>';
