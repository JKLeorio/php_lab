<?php
// Файл: register.php
session_start();
$dbname = 'news.db';

try {
    $pdo = new PDO("sqlite:" . $dbname);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $password]);
        header("Location: login.php");
        exit;
    } catch (PDOException $e) {
        $error = "Ошибка регистрации: имя пользователя уже существует.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Регистрация</h1>
    <form method="post" class="auth-form">
        <label>Имя пользователя:<br>
            <input type="text" name="username" required>
        </label><br><br>
        <label>Пароль:<br>
            <input type="password" name="password" required>
        </label><br><br>
        <button type="submit">Зарегистрироваться</button>
        <p><a href="login.php">Уже есть аккаунт? Войти</a></p>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
    </form>
</body>
</html>
