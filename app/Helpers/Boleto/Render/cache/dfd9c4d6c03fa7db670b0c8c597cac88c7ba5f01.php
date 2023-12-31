<?php $__env->startSection('boleto'); ?>

    <?php $__currentLoopData = $boletos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $boleto): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php extract($boleto, EXTR_OVERWRITE); ?>
        <?php if($mostrar_instrucoes): ?>
            <div class="noprint info">
                <h2>Instruções de Impressão</h2>
                <ul>
                    <?php $__empty_1 = true; $__currentLoopData = $instrucoes_impressao; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $instrucao_impressao): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <li><?php echo e($instrucao_impressao); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                        <li>Imprima em impressora jato de tinta (ink jet) ou laser em qualidade normal ou alta (Não use
                            modo econômico).
                        </li>
                        <li>Utilize folha A4 (210 x 297 mm) ou Carta (216 x 279 mm) e margens mínimas à esquerda e à
                            direita do formulário.
                        </li>
                        <li>Corte na linha indicada. Não rasure, risque, fure ou dobre a região onde se encontra o
                            código de barras.
                        </li>
                        <li>Caso não apareça o código de barras no final, pressione F5 para atualizar esta tela.</li>
                        <li>Caso tenha problemas ao imprimir, copie a sequencia numérica abaixo e pague no caixa
                            eletrônico ou no internet banking:
                        </li>
                    <?php endif; ?>
                </ul>
                <span class="header">Linha Digitável: <?php echo e($linha_digitavel); ?></span>
                <span class="header">Número: <?php echo e($numero); ?></span>
                <?php echo $valor ? '<span class="header">Valor: R$' . $valor . '</span>' : ''; ?>

                <br>
            </div>
        <?php endif; ?>

        <div class="linha-pontilhada" style="margin-bottom: 20px;">Recibo do pagador</div>

        <div class="info-empresa">
            <?php if($logo): ?>
                <div style="display: inline-block;">
                    <img alt="logo" src="<?php echo e($logo_base64); ?>"/>
                </div>
            <?php endif; ?>
            <div style="display: inline-block; vertical-align: super;">
                <div><strong><?php echo e($beneficiario['nome']); ?></strong></div>
                <div><?php echo e($beneficiario['documento']); ?></div>
                <div><?php echo e($beneficiario['endereco']); ?></div>
                <div><?php echo e($beneficiario['endereco2']); ?></div>
            </div>
        </div>
        <br>

        <table class="table-boleto" cellpadding="0" cellspacing="0" border="0">
            <tbody>
            <tr>
                <td valign="bottom" colspan="8" class="noborder nopadding">
                    <div class="logocontainer">
                        <div class="logobanco">
                            <img src="<?php echo e(isset($logo_banco_base64) && !empty($logo_banco_base64) ? $logo_banco_base64 : 'https://dummyimage.com/150x75/fff/000000.jpg&text=+'); ?>" alt="logo do banco">
                        </div>
                        <div class="codbanco"><?php echo e($codigo_banco_com_dv); ?></div>
                    </div>
                    <div class="linha-digitavel"><?php echo e($linha_digitavel); ?></div>
                </td>
            </tr>
            <tr>
                <td colspan="2" width="250" class="top-2">
                    <div class="titulo">Beneficiário</div>
                    <div class="conteudo"><?php echo e($beneficiario['nome']); ?></div>
                </td>
                <td class="top-2">
                    <div class="titulo">CPF/CNPJ</div>
                    <div class="conteudo"><?php echo e($beneficiario['documento']); ?></div>
                </td>
                <td width="120" class="top-2">
                    <div class="titulo">Ag/Cod. Beneficiário</div>
                    <div class="conteudo rtl"><?php echo e($agencia_codigo_beneficiario); ?></div>
                </td>
                <td width="120" class="top-2">
                    <div class="titulo">Vencimento</div>
                    <div class="conteudo rtl"><?php echo e($data_vencimento->format('d/m/Y')); ?></div>
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <div class="titulo">Pagador</div>
                    <div class="conteudo"><?php echo e($pagador['nome_documento']); ?> </div>
                </td>
                <td>
                    <div class="titulo">Nº documento</div>
                    <div class="conteudo rtl"><?php echo e($numero_documento); ?></div>
                </td>
                <td>
                    <div class="titulo">Nosso número</div>
                    <div class="conteudo rtl"><?php echo e($nosso_numero_boleto); ?></div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="titulo">Espécie</div>
                    <div class="conteudo"><?php echo e($especie); ?></div>
                </td>
                <td>
                    <div class="titulo">Quantidade</div>
                    <div class="conteudo rtl"></div>
                </td>
                <td>
                    <div class="titulo">Valor</div>
                    <div class="conteudo rtl"></div>
                </td>
                <td>
                    <div class="titulo">(-) Descontos / Abatimentos</div>
                    <div class="conteudo rtl"></div>
                </td>
                <td>
                    <div class="titulo">(=) Valor Documento</div>
                    <div class="conteudo rtl"><?php echo e($valor); ?></div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="conteudo"></div>
                    <div class="titulo">Demonstrativo</div>
                </td>
                <td>
                    <div class="titulo">(-) Outras deduções</div>
                    <div class="conteudo"></div>
                </td>
                <td>
                    <div class="titulo">(+) Outros acréscimos</div>
                    <div class="conteudo rtl"></div>
                </td>
                <td>
                    <div class="titulo">(=) Valor cobrado</div>
                    <div class="conteudo rtl"></div>
                </td>
            </tr>
            <tr>
                <td colspan="4">
                    <div style="margin-top: 10px" class="conteudo"><?php echo e($demonstrativo[0]); ?></div>
                </td>
                <td class="noleftborder">
                    <div class="titulo">Autenticação mecânica</div>
                </td>
            </tr>
            <tr>
                <td colspan="5" class="notopborder">
                    <div class="conteudo"><?php echo e($demonstrativo[1]); ?></div>
                </td>
            </tr>
            <tr>
                <td colspan="5" class="notopborder">
                    <div class="conteudo"><?php echo e($demonstrativo[2]); ?></div>
                </td>
            </tr>
            <tr>
                <td colspan="5" class="notopborder">
                    <div class="conteudo"><?php echo e($demonstrativo[3]); ?></div>
                </td>
            </tr>
            <tr>
                <td colspan="5" class="notopborder bottomborder">
                    <div style="margin-bottom: 10px;" class="conteudo"><?php echo e($demonstrativo[4]); ?></div>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <div class="linha-pontilhada">Corte na linha pontilhada</div>
        <br>

        <!-- Ficha de compensação -->
        <?php echo $__env->make('BoletoHtmlRender::partials/ficha-compensacao', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <?php if(count($boletos) > 1 && count($boletos)-1 != $i): ?>
            <div style="page-break-before:always"></div>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('BoletoHtmlRender::layout', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/vendor/eduardokum/laravel-boleto/src/Boleto/Render/view/boleto.blade.php ENDPATH**/ ?>