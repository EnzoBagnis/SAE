<?php

// controllers/post.php


require_once('/../src/model/Database.php');


function insciption(string $identifier)

{

    $post = getPost($identifier);

    $comments = getComments($identifier);


    require('/../views/formulaire.php');

}
