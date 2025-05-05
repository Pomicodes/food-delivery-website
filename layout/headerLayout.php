<?php
// Set the document root path
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);

// Calculate the correct path
$cssPath = './public/css/styles.css'; // Always from root
// OR if you need relative:
//$cssPath = $docRoot . '/public/css/styles.css';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sweet Bite</title>
  
  <link
  rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/bulma@1.0.2/css/bulma.min.css"
>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?php echo $cssPath; ?>">
<style>
  :root {
    --primary: #ff7b25;
    --primary-light: #ff9d5c;
    --background: #EDEDEDFF;
    --background-dark: #1a1a1a;
    --text: #363636;
    --text-dark: #f5f5f5;
    --card-bg: #ffffff;
    --card-bg-dark: #2d2d2d;
}

body {
    background-color: var(--background);
    color: var(--text);
    transition: background-color 0.3s ease, color 0.3s ease;
}
.fs{
    background: url('../public/assets/images/onboarding.jpg') no-repeat center center fixed;
    overflow: hidden;
    height: 100vh;
}
body.dark-mode {
    background-color: var(--background-dark);
    color: var(--text-dark);
}

.card, .box {
    background-color: var(--card-bg);
    transition: background-color 0.3s ease;
}

body.dark-mode .card,
body.dark-mode .box {
    background-color: var(--card-bg-dark);
    color: var(--text-dark);
}
.title, .label,.icon{
  color: var(--text);
}
body.dark-mode .title,
body.dark-mode .icon,
body.dark-mode .subtitle,
body.dark-mode .label {
    color: var(--text-dark) !important;
}


.is-primary {
    background-color: var(--primary) !important;
}
.has-shadow {
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}
.is-primary {
    background-color: var(--primary) !important;
}
.is-primary:hover {
    background-color: var(--primary-light) !important;
}
.checkbox:hover {
    color: var(--primary);
}
a:hover {
    color: var(--primary) !important;
}
.input:focus, .checkbox input[type="checkbox"]:focus + .check {
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 0.125em rgba(255, 123, 37, 0.25);
}
.a-nav{
    position: absolute;
    top: 0;
    right: 0;
    margin: 20px;
    
}
.a-nav .none{
    background-color: transparent !important;
}
.text-1{
    font-family: 'chicago' ;
    color: black;
    font-weight: bold;
    font-size: 64px;
    margin-top: 5px;
}
.text-2{
    font-family: 'chicago' ;
    color: black;
    font-weight: bold;
    font-size: 44px;
    margin-top: 5px;
}
</style>
</head>
<body class="<?php echo isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'true' ? 'dark-mode' : ''; ?>">

<span>
  <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'User'; ?>
</span>
