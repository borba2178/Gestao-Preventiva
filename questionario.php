<?php
require 'conexao.php';
exigir_login('funcionario');
zerar_ofensivas($conn);

$id_func = $_SESSION['id'];
$erro = '';

$q = $conn->query("SELECT id_Quest, Titulo, Descri FROM g07_Questionario ORDER BY id_Quest LIMIT 1")->fetch_assoc();
$perguntas = [];
$opcoes = [];

if ($q) {
    $stmt = $conn->prepare("SELECT id_Perg, Texto, Numer FROM g07_Perguntas WHERE id_Quest = ? ORDER BY Numer");
    $stmt->bind_param('i', $q['id_Quest']);
    $stmt->execute();
    foreach ($stmt->get_result() as $p) {
        $perguntas[$p['id_Perg']] = $p;
    }

    $stmt = $conn->prepare("SELECT o.id_Op, o.id_Perg, o.Op_Texto, o.Valor FROM g07_Opcoes o JOIN g07_Perguntas p ON p.id_Perg = o.id_Perg WHERE p.id_Quest = ? ORDER BY o.id_Op");
    $stmt->bind_param('i', $q['id_Quest']);
    $stmt->execute();
    foreach ($stmt->get_result() as $o) {
        $opcoes[$o['id_Perg']][$o['id_Op']] = $o;
    }

    $perguntas = array_filter($perguntas, fn($p) => !empty($opcoes[$p['id_Perg']]));
}

if ($q && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $respostas = is_array($_POST['perg'] ?? null) ? $_POST['perg'] : [];
    $escolhas = [];
    $pontos = 0;
    $maximo = 0;

    foreach ($perguntas as $id_perg => $p) {
        $id_op = (int)($respostas[$id_perg] ?? 0);
        $maximo += max(array_column($opcoes[$id_perg], 'Valor'));
        if (!isset($opcoes[$id_perg][$id_op])) {
            $erro = 'Responda todas as perguntas antes de enviar.';
            break;
        }
        $escolhas[$id_perg] = $id_op;
        $pontos += (int)$opcoes[$id_perg][$id_op]['Valor'];
    }

    if ($erro === '') {
        $inicio = $_SESSION['quest_inicio'] ?? date('Y-m-d H:i:s');
        $fim = date('Y-m-d H:i:s');
        $hoje = date('Y-m-d');

        $stmt = $conn->prepare("SELECT MAX(DATE(Dat_Ini)) FROM g07_Tentativas WHERE id_Func = ?");
        $stmt->bind_param('i', $id_func);
        $stmt->execute();
        $ultima = $stmt->get_result()->fetch_row()[0];

        $stmt = $conn->prepare("SELECT Ofensiva FROM g07_Funcionario WHERE id_Func = ?");
        $stmt->bind_param('i', $id_func);
        $stmt->execute();
        $ofensiva = (int)$stmt->get_result()->fetch_row()[0];

        if ($ultima !== $hoje) {
            $ofensiva++;
        }

        try {
            $conn->begin_transaction();

            $stmt = $conn->prepare("INSERT INTO g07_Tentativas (id_Func, Dat_Ini, Dat_Fim) VALUES (?, ?, ?)");
            $stmt->bind_param('iss', $id_func, $inicio, $fim);
            $stmt->execute();
            $id_tent = $conn->insert_id;

            $stmt = $conn->prepare("INSERT INTO g07_Resp_Quest (id_Tent, id_Quest, id_Op, id_Perg) VALUES (?, ?, ?, ?)");
            foreach ($escolhas as $id_perg => $id_op) {
                $stmt->bind_param('iiii', $id_tent, $q['id_Quest'], $id_op, $id_perg);
                $stmt->execute();
            }

            $stmt = $conn->prepare("UPDATE g07_Funcionario SET Ofensiva = ?, Pontuacao = ? WHERE id_Func = ?");
            $stmt->bind_param('iii', $ofensiva, $pontos, $id_func);
            $stmt->execute();

            $conn->commit();

            $_SESSION['resultado'] = ['pontos' => $pontos, 'maximo' => $maximo, 'ofensiva' => $ofensiva];
            header('Location: questionario.php');
            exit();
        } catch (mysqli_sql_exception $ex) {
            $conn->rollback();
            $erro = 'Não foi possível salvar suas respostas. Tente novamente.';
        }
    }
}

$resultado = $_SESSION['resultado'] ?? null;
unset($_SESSION['resultado']);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$resultado) {
    $_SESSION['quest_inicio'] = date('Y-m-d H:i:s');
}

$psic = null;
if ($resultado && $resultado['pontos'] >= LIMITE_ALERTA) {
    $stmt = $conn->prepare("SELECT p.Nome, p.Email, p.Telefone FROM g07_Funcionario f JOIN g07_Psicologo p ON p.id_Psic = f.id_Psic WHERE f.id_Func = ?");
    $stmt->bind_param('i', $id_func);
    $stmt->execute();
    $psic = $stmt->get_result()->fetch_assoc();
}

exibir_topo('Questionário');
?>

<?php if ($resultado): ?>
<section class="bloco">
  <h1>Respostas enviadas</h1>
  <div class="destaque">
    <div class="destaque-numero"><?= (int)$resultado['ofensiva'] ?></div>
    <div class="destaque-texto">
      <strong>dias seguidos respondendo</strong>
      <span>Volte amanhã para manter a sequência.</span>
    </div>
  </div>

  <?php if ($resultado['pontos'] >= LIMITE_ALERTA): ?>
    <div class="aviso aviso-atencao">
      <h2>Sugerimos conversar com um psicólogo</h2>
      <p>Suas respostas indicam que você pode estar passando por um período difícil. Isso não é um diagnóstico, mas um acompanhamento profissional pode ajudar.</p>
      <?php if ($psic): ?>
        <p><strong><?= e($psic['Nome']) ?></strong><br>
          <?= e($psic['Email']) ?><?= $psic['Telefone'] ? ' · ' . e($psic['Telefone']) : '' ?></p>
      <?php else: ?>
        <p>Você ainda não tem um psicólogo vinculado. Procure o RH da sua empresa ou um serviço de saúde.</p>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="aviso aviso-ok">
      <h2>Tudo certo por aqui</h2>
      <p>Suas respostas não indicam necessidade de acompanhamento neste momento. Continue cuidando de você.</p>
    </div>
  <?php endif; ?>

  <p><a class="botao" href="painel_funcionario.php">Ir para meu painel</a></p>
</section>

<?php elseif (!$q || !$perguntas): ?>
<section class="bloco">
  <h1>Questionário indisponível</h1>
  <p>Nenhum questionário foi cadastrado no banco de dados.</p>
</section>

<?php else: ?>
<section class="bloco">
  <h1><?= e($q['Titulo']) ?></h1>
  <p class="apoio"><?= e($q['Descri']) ?></p>

  <?php if ($erro): ?>
    <div class="aviso aviso-erro"><?= e($erro) ?></div>
  <?php endif; ?>

  <div class="progresso" aria-live="polite">
    <div class="progresso-trilho"><div class="progresso-barra" id="progresso-barra"></div></div>
    <span id="progresso-texto"></span>
  </div>

  <form method="POST" action="questionario.php" id="form-questionario">
    <?php $n = 0; foreach ($perguntas as $id_perg => $p): $n++; ?>
      <fieldset class="pergunta">
        <legend><?= $n ?>. <?= e($p['Texto']) ?></legend>
        <?php foreach ($opcoes[$id_perg] as $o): ?>
          <label class="opcao">
            <input type="radio" name="perg[<?= $id_perg ?>]" value="<?= $o['id_Op'] ?>" required>
            <span><?= e($o['Op_Texto']) ?></span>
          </label>
        <?php endforeach; ?>
    </fieldset>
    <?php endforeach; ?>
    <button type="submit" class="botao">Enviar respostas</button>
  </form>
</section>
<?php endif; ?>

<?php exibir_rodape(); ?>
