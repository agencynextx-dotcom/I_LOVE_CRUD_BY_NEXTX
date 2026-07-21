<?php if ($errors !== []): ?>
    <ul class="error-list">
        <?php foreach ($errors as $error): ?>
            <li><?= e($error) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form method="post">
    <div class="form-grid">
        <div class="form-field full">
            <label for="naam">Naam *</label>
            <input id="naam" name="naam" maxlength="100" value="<?= old('naam', $item['naam']) ?>" required>
        </div>

        <div class="form-field">
            <label for="opleiding_id">Opleiding *</label>
            <select id="opleiding_id" name="opleiding_id" required>
                <option value="">Kies een opleiding</option>
                <?php foreach ($opleidingen as $opleiding): ?>
                    <option value="<?= $opleiding['id'] ?>"
                        <?= (int) ($_POST['opleiding_id'] ?? $item['opleiding_id']) === $opleiding['id'] ? 'selected' : '' ?>>
                        <?= e($opleiding['naam']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field">
            <label for="cijfer">Cijfer *</label>
            <input type="number" id="cijfer" name="cijfer" min="0" max="10" step="0.1"
                   value="<?= old('cijfer', $item['cijfer']) ?>" required>
        </div>

        <div class="form-field">
            <label for="status">Status *</label>
            <select id="status" name="status">
                <?php foreach (STUDENT_STATUSSEN as $status): ?>
                    <option <?= (($_POST['status'] ?? $item['status']) === $status) ? 'selected' : '' ?>>
                        <?= e($status) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-actions">
        <button class="button">Opslaan</button>
        <a class="button button-secondary" href="index.php">Annuleren</a>
    </div>
</form>
