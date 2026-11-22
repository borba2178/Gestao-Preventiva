<?php
require 'conexao.php';

if (!empty($_SESSION['tipo'])) {
    header('Location: ' . destino_do_perfil($_SESSION['tipo']));
    exit();
}

$erro = '';
$tipo = $_POST['tipo'] ?? 'funcionario';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (!isset(PERFIS[$tipo])) {
        $erro = 'Selecione o tipo de acesso.';
    } else {
        $tabela = PERFIS[$tipo]['tabela'];
        $coluna = PERFIS[$tipo]['id'];

        $stmt = $conn->prepare("SELECT $coluna, Nome, Senha FROM $tabela WHERE Email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->bind_result($id, $nome, $hash);
        $achou = $stmt->fetch();
        $stmt->close();

        if ($achou && verificar_senha($senha, $hash)) {
            // Senha antiga (SHA-1): converte para o formato seguro no primeiro login
            if (senha_legada($hash)) {
                $novoHash = password_hash($senha, PASSWORD_DEFAULT);
                $up = $conn->prepare("UPDATE $tabela SET Senha = ? WHERE $coluna = ?");
                $up->bind_param('si', $novoHash, $id);
                $up->execute();
            }
            session_regenerate_id(true);
            $_SESSION['tipo'] = $tipo;
            $_SESSION['id'] = $id;
            $_SESSION['nome'] = $nome;
            header('Location: ' . destino_do_perfil($tipo));
            exit();
        }
        $erro = 'E-mail ou senha incorretos.';
    }
}

exibir_topo('Entrar', 'pagina-auth');
?>
<section class="auth">
  <h1>Entrar</h1>
  <p class="apoio">Acesse sua conta para continuar.</p>

  <?php if (isset($_GET['cadastro'])): ?>
    <div class="aviso aviso-ok">Cadastro concluído. Faça login para entrar.</div>
  <?php endif; ?>
  <?php if ($erro): ?>
    <div class="aviso aviso-erro"><?= e($erro) ?></div>
  <?php endif; ?>

  <form method="POST" action="index.php">
    <fieldset class="segmentado">
      <legend>Tipo de acesso</legend>
      <?php foreach (PERFIS as $chave => $p): ?>
        <label>
          <input type="radio" name="tipo" value="<?= $chave ?>" <?= $tipo === $chave ? 'checked' : '' ?>>
          <span><?= e($p['rotulo']) ?></span>
        </label>
      <?php endforeach; ?>
    </fieldset>

    <div class="campo">
      <label for="email">E-mail</label>
      <input type="email" id="email" name="email" value="<?= v('email') ?>" required autofocus>
    </div>

    <div class="campo">
      <label for="senha">Senha</label>
      <div class="campo-senha">
        <input type="password" id="senha" name="senha" required>
        <button type="button" data-toggle-senha="senha">Mostrar</button>
      </div>
    </div>

    <button type="submit" class="botao botao-bloco">Entrar</button>
  </form>

  <p class="rodape-form">Ainda não tem conta? <a href="cadastro.php">Cadastre-se</a></p>
</section>
<?php exibir_rodape(); ?>
