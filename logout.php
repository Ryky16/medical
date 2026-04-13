<?php
session_start();
session_unset();
session_destroy();
header('Location: /medical01/');
exit();
