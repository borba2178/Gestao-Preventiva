<?php
require 'conexao.php';
exigir_login('chefe');
zerar_ofensivas($conn);

$id = $_SESSION['id'];

$stmt = $conn->prepare("SELECT id_Emp, Nome, Descr FROM g07_Empresa WHERE id_Chefe = ? ORDER BY Nome");
$stmt->bind_param('i', $id);
$stmt->execute();
$empresas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt = $conn->prepare(
    "SELECT f.id_Func, f.Nome, f.Email, f.Ofensiva, e.Nome AS empresa, MAX(t.Dat_Ini) AS ultima
     FROM g07_Funcionario f
     JOIN g07_Empresa e ON e.id_Emp = f.id_Emp
     LEFT JOIN g07_Tentativas t ON t.id_Func = f.id_Func
     WHERE e.id_Chefe = ?
     GROUP BY f.id_Func, f.Nome, f.Email, f.Ofensiva, e.Nome
     ORDER BY e.Nome, f.Nome"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$funcionarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total = count($funcionarios);
$respondeuHoje = 0;
$semSequencia = 0;
$somaOfensiva = 0;
foreach ($funcionarios as $f) {
    if ($f['ultima'] !== null && date('Y-m-d', strtotime($f['ultima'])) === date('Y-m-d')) $respondeuHoje++;
    if ((int)$f['Ofensiva'] === 0) $semSequencia++;
    $somaOfensiva += (int)$f['Ofensiva'];
}
$media = $total ? round($somaOfensiva / $total, 1) : 0;

exibir_topo('Painel do chefe');
?>
<h1>Painel da empresa</h1>
<p class="apoio"><?= e(implode(', ', array_column($empresas, 'Nome'))) ?></p>

<section class="indicadores">
  <div class="indicador"><strong><?= $total ?></strong><span>funcionários</span></div>
  <div class="indicador"><strong><?= $respondeuHoje ?></strong><span>responderam hoje</span></div>
  <div class="indicador"><strong><?= str_replace('.', ',', $media) ?></strong><span>ofensiva média (dias)</span></div>
  <div class="indicador"><strong><?= $semSequencia ?></strong><span>sem sequência ativa</span></div>
</section>

<section class="bloco">
  <h2>Participação dos funcionários</h2>
  <?php if (!$funcionarios): ?>
    <p>Nenhum funcionário vinculado à sua empresa ainda. Quando eles se cadastrarem escolhendo a empresa, aparecerão aqui.</p>
  <?php else: ?>
    <div class="tabela-rolagem">
      <table>
        <thead>
          <tr><th>Nome</th><th>Empresa</th><th>Ofensiva</th><th>Última resposta</th></tr>
        </thead>
        <tbody>
        <?php foreach ($funcionarios as $f): ?>
          <tr>
            <td><?= e($f['Nome']) ?><br><small><?= e($f['Email']) ?></small></td>
            <td><?= e($f['empresa']) ?></td>
            <td><?= (int)$f['Ofensiva'] ?> dias</td>
            <td><?= fmt_data($f['ultima']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
  <p class="nota">Por privacidade, pontuações e respostas individuais ficam visíveis apenas ao psicólogo vinculado.</p>
</section>
<?php exibir_rodape(); ?>
