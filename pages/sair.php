<?php
// Fazer logout do usuário
Auth::logout();

// Redirecionar para a página inicial
$_SESSION['flash_message'] = "Você saiu com sucesso!";
$_SESSION['flash_type'] = "success";
echo "<script>window.location.href = '" . SITE_URL . "/?route=inicio';</script>";
exit;
?>
