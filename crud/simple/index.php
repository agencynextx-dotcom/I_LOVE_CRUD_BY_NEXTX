<?php
require_once __DIR__ . '/Includes/db.php';
require_once __DIR__ . '/Includes/functions.php';

require_login();

$statement = $pdo->query('SELECT * FROM users ORDER BY id');
$users = $statement->fetchAll();

page_start('Overzicht');
?>
<main class="container">
    <div class="page-heading">
        <div>
            <p class="eyebrow">CRUD zonder relaties</p>
            <h1>User List</h1>
        </div>
        <a class="button" href="create.php">Add User</a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $index => $user): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= e($user['name']) ?></td>
                        <td><?= e($user['email']) ?></td>
                        <td><?= e($user['phone']) ?></td>
                        <td><?= e($user['address']) ?></td>
                        <td class="actions">
                            <a class="button button-small" href="edit.php?id=<?= $user['id'] ?>">Edit</a>
                            <form method="post" action="delete.php" onsubmit="return confirm('Gebruiker verwijderen?');">
                                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                <button class="button button-small button-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if ($users === []): ?>
                    <tr><td colspan="6" class="empty">Geen gebruikers gevonden.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
<?php page_end(); ?>
