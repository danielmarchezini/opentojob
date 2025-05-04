<!-- Modal Editar Vaga -->
<div class="modal fade" id="modalEditarVaga" tabindex="-1" role="dialog" aria-labelledby="modalEditarVagaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarVagaLabel">Editar Vaga</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <!-- Indicador de carregamento -->
            <div id="editarVagaLoading" class="modal-body" style="display: none;">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-2">Carregando dados da vaga...</p>
                </div>
            </div>
            
            <!-- Formulário de edição -->
            <form id="formEditarVaga" action="<?php echo SITE_URL; ?>/admin/processar_vaga_admin.php" method="POST" class="needs-validation" novalidate>
                <div id="editarVagaForm" class="modal-body">
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
                            <option value="<?php echo $empresa['id']; ?>"><?php echo $empresa['nome']; ?></option>
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
                                <label for="editar_tipo_contrato">Tipo de Contrato</label>
                                <select class="form-control" id="editar_tipo_contrato" name="tipo_contrato_id">
                                    <option value="">Selecione</option>
                                    <?php foreach ($tipos_contrato as $tipo): ?>
                                        <option value="<?php echo $tipo['id']; ?>"><?php echo htmlspecialchars($tipo['nome']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="editar_regime_trabalho">Regime de Trabalho</label>
                                <select class="form-control" id="editar_regime_trabalho" name="regime_trabalho_id">
                                    <option value="">Selecione</option>
                                    <?php foreach ($regimes_trabalho as $regime): ?>
                                        <option value="<?php echo $regime['id']; ?>"><?php echo htmlspecialchars($regime['nome']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="editar_nivel_experiencia">Nível de Experiência</label>
                                <select class="form-control" id="editar_nivel_experiencia" name="nivel_experiencia_id">
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
                                <label>Mostrar Salário</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="editar_mostrar_salario" name="mostrar_salario" value="1">
                                    <label class="form-check-label" for="editar_mostrar_salario">Sim</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="editar_descricao">Descrição da Vaga</label>
                        <textarea class="form-control" id="editar_descricao" name="descricao" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="editar_requisitos">Requisitos</label>
                        <textarea class="form-control" id="editar_requisitos" name="requisitos" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="editar_beneficios">Benefícios</label>
                        <textarea class="form-control" id="editar_beneficios" name="beneficios" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="editar_status">Status</label>
                        <select class="form-control" id="editar_status" name="status" required>
                            <option value="aberta">Aberta</option>
                            <option value="fechada">Fechada</option>
                            <option value="pendente">Pendente</option>
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

<!-- Modal Visualizar Vaga -->
<div class="modal fade" id="modalVisualizarVaga" tabindex="-1" role="dialog" aria-labelledby="modalVisualizarVagaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVisualizarVagaLabel">Detalhes da Vaga</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div id="vagaDetalhes" class="p-3">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p>Carregando detalhes da vaga...</p>
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

<!-- Modal Confirmação -->
<div class="modal fade" id="modalConfirmacao" tabindex="-1" role="dialog" aria-labelledby="modalConfirmacaoLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfirmacaoLabel">Confirmação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p id="mensagem_confirmacao"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="<?php echo SITE_URL; ?>/admin/processar_vaga_admin.php" method="post">
                    <input type="hidden" name="acao" id="acao_confirmacao">
                    <input type="hidden" name="vaga_id" id="vaga_id_confirmacao">
                    <button type="submit" class="btn btn-danger">Confirmar</button>
                </form>
            </div>
        </div>
    </div>
</div>
