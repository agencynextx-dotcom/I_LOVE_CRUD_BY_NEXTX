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
        <div class="form-field full">
            <label for="omschrijving">Omschrijving *</label>
            <input id="omschrijving" name="omschrijving" maxlength="255"
                   value="<?= old('omschrijving', $item['omschrijving']) ?>" required>
        </div>

        <div class="form-field">
            <label for="onderhoudsdatum">Datum *</label>
            <input type="date" id="onderhoudsdatum" name="onderhoudsdatum"
                   value="<?= old('onderhoudsdatum', $item['onderhoudsdatum']) ?>" required>
        </div>

        <div class="form-field">
            <label for="kosten">Kosten *</label>
            <input type="number" id="kosten" name="kosten" min="0" step="0.01"
                   value="<?= old('kosten', $item['kosten']) ?>" required>
        </div>

        <div class="form-field">
            <label for="status">Status *</label>
            <select id="status" name="status">
                <?php foreach (ONDERHOUD_STATUSSEN as $status): ?>
                    <option <?= (($_POST['status'] ?? $item['status']) === $status) ? 'selected' : '' ?>>
                        <?= e($status) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-actions">
        <button class="button">Opslaan</button>
        <a class="button button-secondary" href="../voertuigen/show.php?id=<?= $vehicle['id'] ?>">
            Annuleren
        </a>
    </div>
</form>
