<?php
$stmt = $conn->prepare("SELECT * FROM recompensa WHERE grupo_id = :gid ORDER BY costo ASC");
$stmt->execute([':gid' => $id_grupo]);
$recompensas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-section">
    <h3><i class="bi bi-gift"></i> Recompensas del grupo</h3>
    <?php if (empty($recompensas)): ?>
        <p class="text-muted">Todavía no hay recompensas creadas.</p>
    <?php else: ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Descripción</th>
                    <th>Costo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recompensas as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['titulo']) ?></td>
                        <td><?= htmlspecialchars($r['descripcion']) ?></td>
                        <td><?= $r['costo'] ?> pts</td>
                        <td>
                            <form method="POST" action="eliminar_recompensa.php" onsubmit="return confirm('¿Eliminar recompensa?')">
                                <input type="hidden" name="id_recompensa" value="<?= $r['id_recompensa'] ?>">
                                <input type="hidden" name="id_grupo" value="<?= $id_grupo ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
