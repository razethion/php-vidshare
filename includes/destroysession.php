<?php
session_start();
session_destroy();
echo "Destroied session.";
exit();