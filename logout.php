<?php
session_start();
session_unset();
session_destroy();

// Ana sayfaya yönlendir
header("Location: index.html");
exit;
?>