<?php
session_start();
session_unset();
session_destroy();
header("Location: https://samokati4.ru/");
exit;
?>