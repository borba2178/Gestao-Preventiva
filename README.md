# 1. Nome do projeto

Gestão Preventiva

# 2. Visão geral

Site em que funcionários respondem diariamente a um questionário de bem-estar de 5 perguntas e, quando a pontuação indica desgaste, recebem a sugestão de conversar com um psicólogo. O sistema tem três perfis (funcionário, chefe da empresa e psicólogo), cada um com seu próprio painel, e foi pensado desde o início com segurança e privacidade dos dados de saúde (LGPD e Privacy by Design).

# 3. Problemas que resolve

Falta de acompanhamento preventivo da saúde mental no ambiente de trabalho. O site ajuda o funcionário a perceber sinais de desgaste cedo, aproxima-o de um psicólogo e permite que a empresa acompanhe apenas a participação, sem expor a pontuação ou as respostas de ninguém.

# 4. Público-alvo

Funcionários de empresas que querem acompanhar o próprio bem-estar, psicólogos que atendem esses funcionários e chefes de empresa interessados em incentivar o cuidado com a equipe (acessível via computador e celular).

# 5. Objetivos

Incentivar o hábito de acompanhar o bem-estar todos os dias (ofensiva de dias seguidos), indicar acompanhamento psicológico quando necessário e proteger os dados sensíveis, garantindo que cada perfil veja somente o que lhe cabe.

# 6. Funcionalidades principais

* Cadastro e login para três perfis: funcionário, chefe da empresa e psicólogo
* Questionário de bem-estar com 5 perguntas
* Painel do funcionário: ofensiva (dias seguidos respondendo), histórico das últimas tentativas e troca ou remoção do psicólogo
* Painel do psicólogo: lista dos funcionários vinculados, com última pontuação, ofensiva e última resposta
* Painel do chefe: participação dos funcionários da empresa (ofensiva), sem acesso à pontuação
* Perfil: edição de nome, e-mail, telefone e troca de senha
* Segurança e privacidade: senha com hash, consultas SQL preparadas e controle de acesso por perfil

# 7. Requisitos técnicos

PHP, MySQL, HTML5, CSS, JavaScript, GitHub e Railway (hospedagem do site)

# 8. Requisitos não funcionais

Segurança da informação (OWASP Top 10 e NIST), conformidade com a LGPD, privacidade dos dados de saúde, responsividade, usabilidade e HTTPS

# 9. Desejáveis (nice to have)

Token anti-CSRF, limite de tentativas de login, registro de eventos de segurança (logs), criptografia dos dados de saúde, termo de consentimento, exclusão de conta e recuperação ou troca de senha

# 10. Critérios de sucesso

Os três perfis funcionando com seus painéis, questionário calculando a pontuação corretamente no servidor, chefe sem acesso aos dados de saúde e formulários validados.

# 11. Link do Site

<https://gestao-preventiva.up.railway.app/>
