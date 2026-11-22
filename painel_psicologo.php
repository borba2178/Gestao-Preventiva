<?php
require 'conexao.php';
exigir_login('psicologo');
zerar_ofensivas($conn);

$id = $_SESSION['id'];

$stmt = $conn->prepare(
    "SELECT f.id_Func, f.Nome, f.Email, f.Telefone, f.Ofensiva, f.Pontuacao, e.Nome AS empresa, MAX(t.Dat_Ini) AS ultima
     FROM g07_Funcionario f
     LEFT JOIN g07_Empresa e ON e.id_Emp = f.id_Emp
     LEFT JOIN g07_Tentativas t ON t.id_Func = f.id_Func
     WHERE f.id_Psic = ?
     GROUP BY f.id_Func, f.Nome, f.Email, f.Telefone, f.Ofensiva, f.Pontuacao, e.Nome
     ORDER BY f.Pontuacao DESC, f.Nome"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$pacientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$emAlerta = fn($p) => $p['ultima'] !== null && (int)$p['Pontuacao'] >= LIMITE_ALERTA;
$atencao = count(array_filter($pacientes, $emAlerta));

exibir_topo('Painel do psicólogo');
?>
<h1>Painel do psicólogo</h1>
<p class="apoio">Funcionários que escolheram você para acompanhamento.</p>

<section class="indicadores">
  <div class="indicador"><strong><?= count($pacientes) ?></strong><span>funcionários vinculados</span></div>
  <div class="indicador"><strong><?= $atencao ?></strong><span>em atenção</span></div>
</section>

<section class="bloco">
  <h2>Acompanhamento</h2>
  <?php if (!$pacientes): ?>
    <p>Nenhum funcionário vinculado a você ainda. Eles podem escolher você durante o cadastro ou pelo painel.</p>
  <?php else: ?>
    <div class="tabela-rolagem">
      <table>
        <thead>
          <tr><th>Nome</th><th>Contato</th><th>Empresa</th><th>Última pontuação</th><th>Ofensiva</th><th>Última resposta</th><th>Situação</th></tr>
        </thead>
        <tbody>
        <?php foreach ($pacientes as $p): ?>
          <tr class="<?= $emAlerta($p) ? 'linha-alerta' : '' ?>">
            <td><?= e($p['Nome']) ?></td>
            <td><?= e($p['Email']) ?><?= $p['Telefone'] ? '<br><small>' . e($p['Telefone']) . '</small>' : '' ?></td>
            <td><?= e($p['empresa']) ?></td>
            <td><?= $p['ultima'] === null ? '—' : (int)$p['Pontuacao'] ?></td>
            <td><?= (int)$p['Ofensiva'] ?> dias</td>
            <td><?= fmt_data($p['ultima']) ?></td>
            <td><?= selo_status($p['Pontuacao'], $p['ultima']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="nota">Pontuação de 0 a <?= pontuacao_maxima($conn) ?>. A partir de <?= LIMITE_ALERTA ?>, o funcionário é marcado como atenção.</p>
  <?php endif; ?>
</section>
<?php exibir_rodape(); ?>
