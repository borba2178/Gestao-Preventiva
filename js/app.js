document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-toggle-senha]').forEach(botao => {
    botao.addEventListener('click', () => {
      const campo = document.getElementById(botao.dataset.toggleSenha);
      const visivel = campo.type === 'text';
      campo.type = visivel ? 'password' : 'text';
      botao.textContent = visivel ? 'Mostrar' : 'Ocultar';
    });
  });

  document.querySelectorAll('input[data-somente-numeros]').forEach(campo => {
    campo.addEventListener('input', () => {
      campo.value = campo.value.replace(/\D/g, '');
    });
  });

  const formCadastro = document.getElementById('form-cadastro');
  if (formCadastro) {
    const grupos = formCadastro.querySelectorAll('[data-tipo]');
    const aviso = document.getElementById('aviso-js');

    const atualizarGrupos = () => {
      const marcado = formCadastro.querySelector('input[name="tipo"]:checked');
      const tipo = marcado ? marcado.value : '';
      grupos.forEach(grupo => {
        const ativo = grupo.dataset.tipo === tipo;
        grupo.hidden = !ativo;
        grupo.querySelectorAll('input, select').forEach(c => { c.disabled = !ativo; });
      });
    };

    formCadastro.querySelectorAll('input[name="tipo"]').forEach(r => r.addEventListener('change', atualizarGrupos));
    atualizarGrupos();

    formCadastro.addEventListener('submit', ev => {
      const senha = formCadastro.querySelector('#senha').value;
      const confirmar = formCadastro.querySelector('#confirmar').value;
      if (senha !== confirmar) {
        ev.preventDefault();
        aviso.textContent = 'As senhas não conferem.';
        aviso.hidden = false;
        aviso.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  }

  const formQuestionario = document.getElementById('form-questionario');
  if (formQuestionario) {
    const total = formQuestionario.querySelectorAll('.pergunta').length;
    const barra = document.getElementById('progresso-barra');
    const texto = document.getElementById('progresso-texto');
    const enviar = formQuestionario.querySelector('button[type="submit"]');

    const atualizarProgresso = () => {
      const respondidas = formQuestionario.querySelectorAll('input[type="radio"]:checked').length;
      barra.style.width = (respondidas / total * 100) + '%';
      texto.textContent = respondidas + ' de ' + total + ' respondidas';
      enviar.disabled = respondidas < total;
    };

    formQuestionario.addEventListener('change', atualizarProgresso);
    atualizarProgresso();
  }
});
