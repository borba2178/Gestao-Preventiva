<?php
require 'conexao.php';
exigir_login('funcionario');
zerar_ofensivas($conn);

$id = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    if ($acao === 'remover') {
        $stmt = $conn->prepare("UPDATE g07_Funcionario SET id_Psic = NULL WHERE id_Func = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        definir_aviso('ok', 'Psicólogo removido. Você pode escolher outro quando quiser.');
    } elseif ($acao === 'vincular') {
        $idPsic = (int)($_POST['id_psic'] ?? 0);
        $stmt = $conn->prepare("SELECT 1 FROM g07_Psicologo WHERE id_Psic = ?");
        $stmt->bind_param('i', $idPsic);
        $stmt->execute();
        if ($idPsic > 0 && $stmt->get_result()->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE g07_Funcionario SET id_Psic = ? WHERE id_Func = ?");
            $stmt->bind_param('ii', $idPsic, $id);
            $stmt->execute();
            definir_aviso('ok', 'Psicólogo vinculado.');
        } else {
            definir_aviso('erro', 'Selecione um psicólogo da lista.');
        }
    }
    header('Location: painel_funcionario.php');
    exit();
}

$stmt = $conn->prepare(
    "SELECT f.Nome, f.Email, f.Ofensiva, f.Pontuacao, f.id_Psic, e.Nome AS empresa,
            p.Nome AS psic_nome, p.Email AS psic_email, p.Telefone AS psic_tel
     FROM g07_Funcionario f
     LEFT JOIN g07_Empresa e ON e.id_Emp = f.id_Emp
     LEFT JOIN g07_Psicologo p ON p.id_Psic = f.id_Psic
     WHERE f.id_Func = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$f = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare(
    "SELECT t.id_Tent, t.Dat_Ini, COALESCE(SUM(o.Valor), 0) AS pontos
     FROM g07_Tentativas t
     LEFT JOIN g07_Resp_Quest r ON r.id_Tent = t.id_Tent
     LEFT JOIN g07_Opcoes o ON o.id_Op = r.id_Op
     WHERE t.id_Func = ?
     GROUP BY t.id_Tent, t.Dat_Ini
     ORDER BY t.Dat_Ini DESC"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$tentativas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$psicologos = $conn->query("SELECT id_Psic, Nome FROM g07_Psicologo ORDER BY Nome")->fetch_all(MYSQLI_ASSOC);

$respondeuHoje = $tentativas && date('Y-m-d', strtotime($tentativas[0]['Dat_Ini'])) === date('Y-m-d');
$ultimaAlerta = $tentativas && (int)$tentativas[0]['pontos'] >= LIMITE_ALERTA;
$serie = array_reverse(array_slice($tentativas, 0, 10));
$maximo = max(1, pontuacao_maxima($conn));

exibir_topo('Meu painel');
?>
<h1>Olá, <?= e(explode(' ', $f['Nome'])[0]) ?></h1>
<p class="apoio"><?= e($f['empresa']) ?></p>
<?php exibir_aviso(); ?>

<section class="destaque">
  <div class="destaque-numero"><?= (int)$f['Ofensiva'] ?></div>
  <div class="destaque-texto">
    <strong>dias seguidos respondendo o questionário</strong>
    <span><?= $respondeuHoje ? 'Você já respondeu hoje.' : 'Responda hoje para manter ou iniciar sua sequência.' ?></span>
  </div>
  <?php if (!$respondeuHoje): ?>
    <a class="botao" href="questionario.php">Responder agora</a>
  <?php endif; ?>
</section>

<?php if ($ultimaAlerta): ?>
  <div class="aviso aviso-atencao">
    <h2>Sugerimos conversar com um psicólogo</h2>
    <p>Sua última resposta indicou sinais de desgaste. Isso não é um diagnóstico.</p>
    <?php if ($f['psic_nome']): ?>
      <p><strong><?= e($f['psic_nome']) ?></strong><br>
        <?= e($f['psic_email']) ?><?= $f['psic_tel'] ? ' · ' . e($f['psic_tel']) : '' ?></p>
    <?php else: ?>
      <p>Escolha um psicólogo no cartão "Meu psicólogo" abaixo.</p>
    <?php endif; ?>
  </div>
<?php endif; ?>

<section class="duas-colunas">
  <div>
    <div class="bloco">
      <h2>Seus dados</h2>
      <dl class="dados">
        <dt>Nome</dt><dd><?= e($f['Nome']) ?></dd>
        <dt>E-mail</dt><dd><?= e($f['Email']) ?></dd>
        <dt>Última pontuação</dt><dd><?= (int)$f['Pontuacao'] ?></dd>
      </dl>
      <a href="perfil.php">Editar dados e senha</a>
    </div>

    <div class="bloco">
      <h2>Meu psicólogo</h2>
      <?php if ($f['psic_nome']): ?>
        <p><strong><?= e($f['psic_nome']) ?></strong><br>
          <?= e($f['psic_email']) ?><?= $f['psic_tel'] ? ' · ' . e($f['psic_tel']) : '' ?></p>
      <?php else: ?>
        <p>Você ainda não tem um psicólogo vinculado.</p>
      <?php endif; ?>

      <?php if ($psicologos): ?>
        <form method="POST" action="painel_funcionario.php" class="form-vinculo">
          <div class="campo">
            <label for="id_psic"><?= $f['psic_nome'] ? 'Trocar por' : 'Escolher psicólogo' ?></label>
            <select id="id_psic" name="id_psic" required>
              <option value="">Selecione</option>
              <?php foreach ($psicologos as $ps): ?>
                <?php if ((int)$ps['id_Psic'] !== (int)$f['id_Psic']): ?>
                  <option value="<?= $ps['id_Psic'] ?>"><?= e($ps['Nome']) ?></option>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" name="acao" value="vincular" class="botao botao-pequeno">Vincular</button>
          <?php if ($f['psic_nome']): ?>
            <button type="submit" name="acao" value="remover" class="botao botao-pequeno botao-sec" formnovalidate>Remover psicólogo</button>
          <?php endif; ?>
        </form>
      <?php elseif (!$f['psic_nome']): ?>
        <p class="nota">Nenhum psicólogo cadastrado no sistema ainda.</p>
      <?php endif; ?>
    </div>
  </div>

  <div class="bloco">
    <h2>Histórico</h2>
    <?php if (!$tentativas): ?>
      <p>Você ainda não respondeu nenhum questionário.</p>
    <?php else: ?>
      <div class="tabela-rolagem">
        <table>
          <thead><tr><th>Data</th><th>Pontuação</th><th>Situação</th></tr></thead>
          <tbody>
          <?php foreach (array_slice($tentativas, 0, 10) as $t): ?>
            <tr>
              <td><?= fmt_data($t['Dat_Ini']) ?></td>
              <td><?= (int)$t['pontos'] ?></td>
              <td><?= selo_status($t['pontos'], $t['Dat_Ini']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="nota">Total de tentativas: <?= count($tentativas) ?></p>
    <?php endif; ?>
  </div>
</section>

<?php if ($serie): ?>
<section class="bloco">
  <h2>Evolução das últimas respostas</h2>
  <div class="grafico" role="img" aria-label="Pontuação das últimas tentativas">
    <div class="grafico-limite" style="bottom: <?= round(LIMITE_ALERTA / $maximo * 100) ?>%"><span>nível de atenção</span></div>
    <?php foreach ($serie as $t): ?>
      <div class="grafico-barra <?= (int)$t['pontos'] >= LIMITE_ALERTA ? 'barra-alerta' : '' ?>"
           style="height: <?= max(2, round($t['pontos'] / $maximo * 100)) ?>%"
           title="<?= fmt_data($t['Dat_Ini']) ?>: <?= (int)$t['pontos'] ?> pontos"></div>
    <?php endforeach; ?>
  </div>
  <div class="grafico-rotulos">
    <?php foreach ($serie as $t): ?>
      <span><?= date('d/m', strtotime($t['Dat_Ini'])) ?><br><strong><?= (int)$t['pontos'] ?></strong></span>
    <?php endforeach; ?>
  </div>
  <p class="nota">Quanto mais alta a barra, maior o desgaste indicado. Pontuação máxima: <?= $maximo ?>.</p>
</section>
<?php endif; ?>
<?php exibir_rodape(); ?>
