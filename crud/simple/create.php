<?php
require_once __DIR__ . '/Includes/db.php';
require_once __DIR__ . '/Includes/functions.php';

require_login();

$item = ['name' => '', 'email' => '', 'phone' => '', 'address' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item = user_input();
    $errors = validate_user($item);

    if ($errors === []) {
        $statement = $pdo->prepare(
            'INSERT INTO users (name, email, phone, address) VALUES (?, ?, ?, ?)'
        );
        $statement->execute([$item['name'], $item['email'], $item['phone'], $item['address']]);

        flash('success', 'Gebruiker toegevoegd.');
        redirect('index.php');
    }
}

page_start('Toevoegen');
?>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Create</p>
            <h1>Add User</h1>
        </div>
    </div>
    <section class="panel"><?php require __DIR__ . '/_form.php'; ?></section>
</main>
<?php page_end(); ?>
