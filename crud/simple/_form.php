<?php if ($errors !== []): ?>
    <ul class="error-list">
        <?php foreach ($errors as $error): ?>
            <li><?= e($error) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post">
    <div class="form-grid">
        <div class="form-field">
            <label for="name">Name *</label>
            <input id="name" name="name" maxlength="100" value="<?= old('name', $item['name']) ?>" required>
        </div>

        <div class="form-field">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" maxlength="150"
                   value="<?= old('email', $item['email']) ?>" required>
        </div>

        <div class="form-field">
            <label for="phone">Phone *</label>
            <input id="phone" name="phone" maxlength="30" value="<?= old('phone', $item['phone']) ?>" required>
        </div>

        <div class="form-field">
            <label for="address">Address *</label>
            <input id="address" name="address" maxlength="255"
                   value="<?= old('address', $item['address']) ?>" required>
        </div>
    </div>

    <div class="form-actions">
        <button class="button">Opslaan</button>
        <a class="button button-secondary" href="index.php">Annuleren</a>
    </div>
</form>
