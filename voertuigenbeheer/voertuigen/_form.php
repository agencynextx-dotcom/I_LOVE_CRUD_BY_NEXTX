<?php if ($errors !== []): ?>
    <ul class="error-list">
        <?php foreach ($errors as $error): ?>
            <li><?= e($error) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post">
    <?= csrf_field() ?>

    <div class="form-grid">
        <div class="form-field">
            <label for="kenteken">Kenteken *</label>
            <input id="kenteken" name="kenteken" maxlength="20"
                   value="<?= old('kenteken', $vehicle['kenteken']) ?>" required>
        </div>

        <div class="form-field">
            <label for="merk">Merk *</label>
            <input id="merk" name="merk" maxlength="80"
                   value="<?= old('merk', $vehicle['merk']) ?>" required>
        </div>

        <div class="form-field">
            <label for="model">Model *</label>
            <input id="model" name="model" maxlength="100"
                   value="<?= old('model', $vehicle['model']) ?>" required>
        </div>

        <div class="form-field">
            <label for="bouwjaar">Bouwjaar *</label>
            <input type="number" id="bouwjaar" name="bouwjaar" min="1950" max="<?= date('Y') ?>"
                   value="<?= old('bouwjaar', $vehicle['bouwjaar']) ?>" required>
        </div>

        <div class="form-field">
            <label for="kilometerstand">Kilometerstand *</label>
            <input type="number" id="kilometerstand" name="kilometerstand" min="0"
                   value="<?= old('kilometerstand', $vehicle['kilometerstand']) ?>" required>
        </div>

        <div class="form-field">
            <label for="status">Status *</label>
            <select id="status" name="status">
                <?php foreach (VOERTUIG_STATUSSEN as $status): ?>
                    <option <?= (($_POST['status'] ?? $vehicle['status']) === $status) ? 'selected' : '' ?>>
                        <?= e($status) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-actions">
        <button class="button" type="submit">Opslaan</button>
        <a class="button button-secondary" href="index.php">Annuleren</a>
    </div>
</form>
