<table class="easy-table">
    <thead>
        <tr>
            <th>account</th>
            <th class="right">amount</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($account_summary as $account => $amount): ?>
            <tr>
                <td><?= $account ?></td>
                <td class="right"><?= $amount ?></td>
            </tr>
        <?php endforeach ?>
    </tbody>
</table>

