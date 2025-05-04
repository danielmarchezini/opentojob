@echo off
echo ===== Script de Commit e Deploy do OpenToJob =====
echo.

REM Definir mensagem de commit
set /p COMMIT_MSG="Digite a mensagem de commit: "

REM Verificar se a mensagem de commit foi fornecida
if "%COMMIT_MSG%"=="" (
    echo Erro: Mensagem de commit não pode estar vazia.
    exit /b 1
)

echo.
echo === Adicionando alterações ao Git ===
git add .

echo.
echo === Realizando commit local ===
git commit -m "%COMMIT_MSG%"

echo.
echo === Enviando alterações para o repositório remoto ===
git push origin master

echo.
echo === Commit e push concluídos com sucesso! ===
echo.
echo Para atualizar o servidor de produção, execute o script deploy.sh no servidor.
echo Comando SSH: ssh usuario@opentojob.com.br "cd /www/wwwroot/opentojob.com.br && ./deploy.sh"
echo.
echo Pressione qualquer tecla para sair...
pause > nul
