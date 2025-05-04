<?php
// Este arquivo contém os modais para a página de gestão de vagas
?>

<!-- Modal para Adicionar Vaga -->
<div class="modal fade" id="modalAdicionarVaga" tabindex="-1" role="dialog" aria-labelledby="modalAdicionarVagaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAdicionarVagaLabel">Adicionar Nova Vaga</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?php echo SITE_URL; ?>/admin/processar_gestao_vagas.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="adicionar">
                    
                    <div class="form-group mb-3">
                        <label for="titulo">Título da Vaga</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="tipo_vaga">Tipo de Vaga</label>
                        <select class="form-control" id="tipo_vaga" name="tipo_vaga" required onchange="toggleEmpresaFields()">
                            <option value="">Selecione o tipo de vaga</option>
                            <option value="interna">Interna (empresa cadastrada)</option>
                            <option value="externa">Externa (empresa não cadastrada)</option>
                        </select>
                    </div>
                    
                    <div id="empresa_interna_div" class="form-group mb-3" style="display: none;">
                        <label for="empresa_id">Empresa Cadastrada</label>
                        <select class="form-control" id="empresa_id" name="empresa_id">
                            <option value="">Selecione uma empresa</option>
                            <?php foreach ($empresas as $empresa): ?>
                            <option value="<?php echo $empresa['id']; ?>"><?php echo htmlspecialchars($empresa['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="empresa_externa_div" class="form-group mb-3" style="display: none;">
                        <label for="empresa_externa">Nome da Empresa Externa</label>
                        <input type="text" class="form-control" id="empresa_externa" name="empresa_externa" placeholder="Nome da empresa não cadastrada">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cidade">Cidade</label>
                                <input type="text" class="form-control" id="cidade" name="cidade">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <input type="text" class="form-control" id="estado" name="estado" maxlength="2">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tipo_contrato_id">Tipo de Contrato</label>
                                <select class="form-control" id="tipo_contrato_id" name="tipo_contrato_id">
                                    <option value="">Selecione</option>
                                    <?php foreach ($tipos_contrato as $tipo): ?>
                                        <option value="<?php echo $tipo['id']; ?>"><?php echo htmlspecialchars($tipo['nome']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="regime_trabalho_id">Regime de Trabalho</label>
                                <select class="form-control" id="regime_trabalho_id" name="regime_trabalho_id">
                                    <option value="">Selecione</option>
                                    <?php foreach ($regimes_trabalho as $regime): ?>
                                        <option value="<?php echo $regime['id']; ?>"><?php echo htmlspecialchars($regime['nome']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="nivel_experiencia_id">Nível de Experiência</label>
                                <select class="form-control" id="nivel_experiencia_id" name="nivel_experiencia_id">
                                    <option value="">Selecione</option>
                                    <?php foreach ($niveis_experiencia as $nivel): ?>
                                        <option value="<?php echo $nivel['id']; ?>"><?php echo htmlspecialchars($nivel['nome']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="salario_min">Salário Mínimo</label>
                                <input type="number" class="form-control" id="salario_min" name="salario_min" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="salario_max">Salário Máximo</label>
                                <input type="number" class="form-control" id="salario_max" name="salario_max" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="mostrar_salario">Mostrar Salário</label>
                                <div class="custom-control custom-switch mt-2">
                                    <input type="checkbox" class="custom-control-input" id="mostrar_salario" name="mostrar_salario" value="1">
                                    <label class="custom-control-label" for="mostrar_salario">Mostrar salário na vaga</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="descricao">Descrição da Vaga</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="requisitos">Requisitos</label>
                        <textarea class="form-control" id="requisitos" name="requisitos" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="responsabilidades">Responsabilidades</label>
                        <textarea class="form-control" id="responsabilidades" name="responsabilidades" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="beneficios">Benefícios</label>
                        <textarea class="form-control" id="beneficios" name="beneficios" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="palavras_chave">Palavras-chave</label>
                        <input type="text" class="form-control" id="palavras_chave" name="palavras_chave" placeholder="Separadas por vírgula">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="pendente">Pendente</option>
                            <option value="aberta">Aberta</option>
                            <option value="fechada">Fechada</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Vaga</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Vaga -->
<div class="modal fade" id="modalEditarVaga" tabindex="-1" role="dialog" aria-labelledby="modalEditarVagaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarVagaLabel">Editar Vaga</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <!-- Indicador de carregamento -->
            <div id="editarVagaLoading" class="text-center p-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Carregando...</span>
                </div>
                <p class="mt-2">Carregando dados da vaga...</p>
            </div>
            <form id="editarVagaForm" action="<?php echo SITE_URL; ?>/admin/processar_gestao_vagas.php" method="post" style="display: none;">
                <div class="modal-body">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="vaga_id" id="editar_vaga_id">
                    
                    <div class="form-group mb-3">
                        <label for="editar_titulo">Título da Vaga</label>
                        <input type="text" class="form-control" id="editar_titulo" name="titulo" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="editar_tipo_vaga">Tipo de Vaga</label>
                        <select class="form-control" id="editar_tipo_vaga" name="tipo_vaga" required onchange="toggleEditarEmpresaFields()">
                            <option value="">Selecione o tipo de vaga</option>
                            <option value="interna">Interna (empresa cadastrada)</option>
                            <option value="externa">Externa (empresa não cadastrada)</option>
                        </select>
                    </div>
                    
                    <div id="editar_empresa_interna_div" class="form-group mb-3">
                        <label for="editar_empresa_id">Empresa Cadastrada</label>
                        <select class="form-control" id="editar_empresa_id" name="empresa_id">
                            <option value="">Selecione uma empresa</option>
                            <?php foreach ($empresas as $empresa): ?>
                            <option value="<?php echo $empresa['id']; ?>"><?php echo htmlspecialchars($empresa['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div id="editar_empresa_externa_div" class="form-group mb-3" style="display: none;">
                        <label for="editar_empresa_externa">Nome da Empresa Externa</label>
                        <input type="text" class="form-control" id="editar_empresa_externa" name="empresa_externa" placeholder="Nome da empresa não cadastrada">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editar_cidade">Cidade</label>
                                <input type="text" class="form-control" id="editar_cidade" name="cidade">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editar_estado">Estado</label>
                                <input type="text" class="form-control" id="editar_estado" name="estado" maxlength="2">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="editar_tipo_contrato_id">Tipo de Contrato</label>
                                <select class="form-control" id="editar_tipo_contrato_id" name="tipo_contrato_id">
                                    <option value="">Selecione</option>
                                    <?php foreach ($tipos_contrato as $tipo): ?>
                                        <option value="<?php echo $tipo['id']; ?>"><?php echo htmlspecialchars($tipo['nome']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="editar_regime_trabalho_id">Regime de Trabalho</label>
                                <select class="form-control" id="editar_regime_trabalho_id" name="regime_trabalho_id">
                                    <option value="">Selecione</option>
                                    <?php foreach ($regimes_trabalho as $regime): ?>
                                        <option value="<?php echo $regime['id']; ?>"><?php echo htmlspecialchars($regime['nome']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="editar_nivel_experiencia_id">Nível de Experiência</label>
                                <select class="form-control" id="editar_nivel_experiencia_id" name="nivel_experiencia_id">
                                    <option value="">Selecione</option>
                                    <?php foreach ($niveis_experiencia as $nivel): ?>
                                        <option value="<?php echo $nivel['id']; ?>"><?php echo htmlspecialchars($nivel['nome']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="editar_salario_min">Salário Mínimo</label>
                                <input type="number" class="form-control" id="editar_salario_min" name="salario_min" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="editar_salario_max">Salário Máximo</label>
                                <input type="number" class="form-control" id="editar_salario_max" name="salario_max" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="editar_mostrar_salario">Mostrar Salário</label>
                                <div class="custom-control custom-switch mt-2">
                                    <input type="checkbox" class="custom-control-input" id="editar_mostrar_salario" name="mostrar_salario" value="1">
                                    <label class="custom-control-label" for="editar_mostrar_salario">Mostrar salário na vaga</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="editar_descricao">Descrição da Vaga</label>
                        <textarea class="form-control" id="editar_descricao" name="descricao" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="editar_requisitos">Requisitos</label>
                        <textarea class="form-control" id="editar_requisitos" name="requisitos" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="editar_responsabilidades">Responsabilidades</label>
                        <textarea class="form-control" id="editar_responsabilidades" name="responsabilidades" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="editar_beneficios">Benefícios</label>
                        <textarea class="form-control" id="editar_beneficios" name="beneficios" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="editar_palavras_chave">Palavras-chave</label>
                        <input type="text" class="form-control" id="editar_palavras_chave" name="palavras_chave" placeholder="Separadas por vírgula">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="editar_status">Status</label>
                        <select class="form-control" id="editar_status" name="status">
                            <option value="pendente">Pendente</option>
                            <option value="aberta">Aberta</option>
                            <option value="fechada">Fechada</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Visualizar Vaga -->
<div class="modal fade" id="modalVisualizarVaga" tabindex="-1" role="dialog" aria-labelledby="modalVisualizarVagaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVisualizarVagaLabel">Detalhes da Vaga</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div id="vagaDetalhes">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Carregando...</span>
                        </div>
                        <p class="mt-2">Carregando detalhes da vaga...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="btnEditarVagaDetalhe" onclick="editarVagaDoModal()">Editar Vaga</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="modalConfirmacao" tabindex="-1" role="dialog" aria-labelledby="modalConfirmacaoLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfirmacaoLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir a vaga <strong id="vaga_titulo_confirmacao"></strong>?</p>
                <p class="text-danger">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="<?php echo SITE_URL; ?>/admin/processar_gestao_vagas.php" method="post">
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="vaga_id" id="vaga_id_confirmacao">
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>
