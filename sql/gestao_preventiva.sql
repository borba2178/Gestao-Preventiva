CREATE DATABASE IF NOT EXISTS gestao_preventiva CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestao_preventiva;

CREATE TABLE Psicologo(
	id_Psic INT AUTO_INCREMENT PRIMARY KEY,
	Nome VARCHAR(50) NOT NULL,
	Email VARCHAR(80) UNIQUE,
	Telefone VARCHAR(11) UNIQUE,
	Senha VARCHAR(255) NOT NULL,
	Camin_Img VARCHAR(255)
);

CREATE TABLE Chefe_Emp(
	id_Chefe INT AUTO_INCREMENT PRIMARY KEY,
	Nome VARCHAR(50) NOT NULL,
	Email VARCHAR(80) UNIQUE,
	Telefone VARCHAR(11) UNIQUE,
	Senha VARCHAR(255) NOT NULL,
	Camin_Img VARCHAR(255)
);

CREATE TABLE Empresa(
	id_Emp INT AUTO_INCREMENT PRIMARY KEY,
	id_Chefe INT,
	Nome VARCHAR(50) NOT NULL,
	Descr VARCHAR(200),
	FOREIGN KEY (id_Chefe) REFERENCES Chefe_Emp(id_Chefe)
);

CREATE TABLE Funcionario(
	id_Func INT AUTO_INCREMENT PRIMARY KEY,
	id_Psic INT,
	id_Emp INT,
	Nome VARCHAR(50) NOT NULL,
	Email VARCHAR(80) UNIQUE,
	Telefone VARCHAR(11) UNIQUE,
	Senha VARCHAR(255) NOT NULL,
	Ofensiva INT DEFAULT 0,
	Pontuacao INT DEFAULT 0,
	Camin_Img VARCHAR(255),
	FOREIGN KEY (id_Emp) REFERENCES Empresa(id_Emp),
	FOREIGN KEY (id_Psic) REFERENCES Psicologo(id_Psic)
);

CREATE TABLE Tentativas(
	id_Tent INT AUTO_INCREMENT PRIMARY KEY,
	id_Func INT,
	Dat_Ini DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
	Dat_Fim DATETIME DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (id_Func) REFERENCES Funcionario(id_Func)
);

CREATE TABLE Questionario(
	id_Quest INT AUTO_INCREMENT PRIMARY KEY,
	Titulo VARCHAR(30) NOT NULL,
	Descri VARCHAR(100)
);

CREATE TABLE Perguntas(
	id_Perg INT AUTO_INCREMENT PRIMARY KEY,
	id_Quest INT,
	Texto VARCHAR(200) NOT NULL,
	Tipo VARCHAR(12),
	Numer INT NOT NULL,
	FOREIGN KEY (id_Quest) REFERENCES Questionario(id_Quest)
);

CREATE TABLE Opcoes(
	id_Op INT AUTO_INCREMENT PRIMARY KEY,
	id_Perg INT,
	Op_Texto VARCHAR(250) NOT NULL,
	Valor INT NOT NULL DEFAULT 0,
	FOREIGN KEY (id_Perg) REFERENCES Perguntas(id_Perg)
);

CREATE TABLE Resp_Quest(
	id_Res INT AUTO_INCREMENT PRIMARY KEY,
	id_Tent INT,
	id_Quest INT,
	id_Op INT,
	id_Perg INT,
	FOREIGN KEY (id_Tent) REFERENCES Tentativas(id_Tent),
	FOREIGN KEY (id_Quest) REFERENCES Questionario(id_Quest),
	FOREIGN KEY (id_Op) REFERENCES Opcoes(id_Op),
	FOREIGN KEY (id_Perg) REFERENCES Perguntas(id_Perg)
);

INSERT INTO Questionario (id_Quest, Titulo, Descri) VALUES
(1, 'Bem-estar no trabalho', 'Cinco perguntas rápidas sobre como você tem se sentido nos últimos dias.');

INSERT INTO Perguntas (id_Perg, id_Quest, Texto, Tipo, Numer) VALUES
(1, 1, 'Como você avalia seu nível de estresse nos últimos dias?', 'alternativa', 1),
(2, 1, 'Como tem sido a qualidade do seu sono?', 'alternativa', 2),
(3, 1, 'Com que frequência você se sentiu sem energia ou motivação para trabalhar?', 'alternativa', 3),
(4, 1, 'Como está sua carga de trabalho atualmente?', 'alternativa', 4),
(5, 1, 'Você tem se sentido apoiado(a) pela equipe e pela liderança?', 'alternativa', 5);

INSERT INTO Opcoes (id_Perg, Op_Texto, Valor) VALUES
(1, 'Tranquilo(a)', 0),
(1, 'Um pouco estressado(a)', 1),
(1, 'Bastante estressado(a)', 2),
(1, 'No limite', 3),
(2, 'Durmo bem todas as noites', 0),
(2, 'Tenho algumas noites ruins', 1),
(2, 'A maioria das noites é ruim', 2),
(2, 'Quase não consigo dormir', 3),
(3, 'Nunca', 0),
(3, 'Poucas vezes', 1),
(3, 'Com frequência', 2),
(3, 'Quase todos os dias', 3),
(4, 'Adequada', 0),
(4, 'Um pouco acima do que consigo', 1),
(4, 'Muito acima do que consigo', 2),
(4, 'Impossível de cumprir', 3),
(5, 'Sempre', 0),
(5, 'Na maioria das vezes', 1),
(5, 'Raramente', 2),
(5, 'Nunca', 3);
