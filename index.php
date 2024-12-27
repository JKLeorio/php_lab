<?php
// Файл: index.php
session_start();

// Установка соединения с базой данных
$dbname = 'news.db';

if (!file_exists($dbname)) {
    touch($dbname);
}

try {
    $pdo = new PDO("sqlite:" . $dbname);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Создание таблиц, если они не существуют
    $pdo->exec("CREATE TABLE IF NOT EXISTS news (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        content TEXT NOT NULL,
        created_at DATETIME NOT NULL
    );

    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL
    );");
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Проверка авторизации
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Пагинация
$newsPerPage = 5;
$totalNewsStmt = $pdo->query("SELECT COUNT(*) as count FROM news");
$totalNews = $totalNewsStmt->fetch(PDO::FETCH_ASSOC)['count'];
$totalPages = ceil($totalNews / $newsPerPage);

$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $newsPerPage;

// Создание и редактированние новости
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    if (isset($_POST['id']) && $_POST['id'] !== '') {
        // Редактивование новости
        $id = (int)$_POST['id'];
        $title = htmlspecialchars($_POST['title']);
        $content = htmlspecialchars($_POST['content']);

        $stmt = $pdo->prepare("UPDATE news SET title = ?, content = ? WHERE id = ?");
        $stmt->execute([$title, $content, $id]);
    } else {
        // Добавление новости
        $title = htmlspecialchars($_POST['title']);
        $content = htmlspecialchars($_POST['content']);

        $stmt = $pdo->prepare("INSERT INTO news (title, content, created_at) VALUES (?, ?, datetime('now'))");
        $stmt->execute([$title, $content]);
    }

    header("Location: index.php");
    exit;
}

// Удаление новости
if (isset($_GET['delete']) && isLoggedIn()) {
    $id = (int)$_GET['delete'];

    $stmt = $pdo->prepare("DELETE FROM news WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: index.php");
    exit;
}

// Список новостей
$stmt = $pdo->prepare("SELECT * FROM news ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $newsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$newsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новости</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="toolbar">
        <?php if (isLoggedIn()): ?>
            <a href="logout.php">Выйти</a>
        <?php else: ?>
            <a href="login.php">Вход</a>
            <a href="register.php">Регистрация</a>
        <?php endif; ?>
    </div>

    <h1>Новости</h1>

    <?php if (isLoggedIn()): ?>
        <form method="post" class="news-form">
            <h2 id="form-title">Добавить новость</h2>
            <p class="form-status" id="form-status">Форма для создания новости</p>
            <input type="hidden" name="id" id="news-id">
            <label>
                Заголовок:<br>
                <input type="text" name="title" id="news-title" required>
            </label><br><br>
            <label>
                Содержание:<br>
                <textarea name="content" id="news-content" rows="5" required></textarea>
            </label><br><br>
            <button type="submit">Сохранить</button>
            <button type="button" id="cancel-edit" onclick="cancelEdit()" style="display: none;">Отменить</button>
        </form>
    <?php endif; ?>

    <center>
        <h2 >Список новостей</h2>
    </center>
    <?php if ($newsList): ?>
        <?php foreach ($newsList as $news): ?>
            <div class="news-item">
                <h2><?= $news['title'] ?></h2>
                <p><?= nl2br($news['content']) ?></p>
                <small>Опубликовано: <?= $news['created_at'] ?></small>
                <?php if (isLoggedIn()): ?>
                    <div class="news-actions">
                        <button onclick="editNews(<?= $news['id'] ?>, '<?= htmlspecialchars($news['title'], ENT_QUOTES) ?>', '<?= htmlspecialchars($news['content'], ENT_QUOTES) ?>')">Редактировать</button>
                        <a href="?delete=<?= $news['id'] ?>" onclick="return confirm('Вы уверены, что хотите удалить эту новость?');">Удалить</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>" class="<?= $i == $currentPage ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php else: ?>
        <p>Новостей пока нет.</p>
    <?php endif; ?>

    <script>
        function editNews(id, title, content) {
            document.getElementById('news-id').value = id;
            document.getElementById('news-title').value = title;
            document.getElementById('news-content').value = content;
            document.getElementById('form-title').textContent = 'Редактировать новость';
            document.getElementById('form-status').textContent = 'Форма для редактирования новости';
            document.getElementById('cancel-edit').style.display = 'inline';
        }

        function cancelEdit() {
            document.getElementById('news-id').value = '';
            document.getElementById('news-title').value = '';
            document.getElementById('news-content').value = '';
            document.getElementById('form-title').textContent = 'Добавить новость';
            document.getElementById('form-status').textContent = 'Форма для создания новости';
            document.getElementById('cancel-edit').style.display = 'none';
        }
    </script>
</body>
</html>