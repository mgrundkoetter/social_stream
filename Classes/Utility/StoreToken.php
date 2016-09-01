<?php
if (isset($_GET['code'])) {
    $url = urldecode($_GET['state'])."&code=".$_GET['code'];
    header('Location: '.$url);
    exit;
} else {
    echo '<b>Fehler mit der Authentifizierung. Bitte versuchen Sie es erneut.</b>';
}