<?php

// controllers/homepage.php


require_once('/../src/model/Database.php');


function bIndex() {

    $posts = getPosts();


    require('/../index.html');

}
?>
