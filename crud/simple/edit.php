<?php
require_once __DIR__ . '/Includes/db.php';
require_once __DIR__ . '/Includes/functions.php';

require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$item = find_user($pdo, $id);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item = user_input();
    $errors = validate_user($item);

    if ($errors === []) {
        $statement = $pdo->prepare(
            'UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?'
        );
        $statement->execute([$item['name'], $item['email'], $item['phone'], $item['address'], $id]);

        flash('success', 'Gebruiker gewijzigd.');
        redirect('index.php');
    }
}

page_start('Wijzigen');
?>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Edit</p>
            <h1>Edit User</h1>
        </div>
    </div>
    <section class="panel"><?php require __DIR__ . '/_form.php'; ?></section>
</main>
<?php page_end(); ?>
