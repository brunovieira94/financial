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
        <td colspan="7" class="top-2">
            <div class="titulo">Local de pagamento</div>
            <div class="conteudo"><?php echo e($local_pagamento); ?></div>
        </td>
        <td width="180" class="top-2">
            <div class="titulo">Vencimento</div>
            <div class="conteudo rtl"><?php echo e($data_vencimento->format('d/m/Y')); ?></div>
        </td>
    </tr>
    <tr class="<?php if($mostrar_endereco_ficha_compensacao): ?> duas-linhas <?php endif; ?>">
        <td colspan="7">
            <div class="titulo">Beneficiário</div>
            <div class="conteudo"><?php echo e($beneficiario['nome_documento']); ?></div>
            <?php if($mostrar_endereco_ficha_compensacao): ?><div class="conteudo"><?php echo e($beneficiario['endereco_completo']); ?></div><?php endif; ?>
        </td>
        <td>
            <div class="titulo">Agência/Código beneficiário</div>
            <div class="conteudo rtl"><?php echo e($agencia_codigo_beneficiario); ?></div>
        </td>
    </tr>
    <tr>
        <td width="110" colspan="2">
            <div class="titulo">Data do documento</div>
            <div class="conteudo"><?php echo e($data_documento->format('d/m/Y')); ?></div>
        </td>
        <td width="120" colspan="2">
            <div class="titulo">Nº documento</div>
            <div class="conteudo"><?php echo e($numero_documento); ?></div>
        </td>
        <td width="60">
            <div class="titulo">Espécie doc.</div>
            <div class="conteudo"><?php echo e($especie_doc); ?></div>
        </td>
        <td>
            <div class="titulo">Aceite</div>
            <div class="conteudo"><?php echo e($aceite); ?></div>
        </td>
        <td width="110">
            <div class="titulo">Data processamento</div>
            <div class="conteudo"><?php echo e($data_processamento->format('d/m/Y')); ?></div>
        </td>
        <td>
            <div class="titulo">Nosso número</div>
            <div class="conteudo rtl"><?php echo e($nosso_numero_boleto); ?></div>
        </td>
    </tr>
    <tr>
        <?php if(!isset($esconde_uso_banco) || !$esconde_uso_banco): ?>
            <td <?php echo e((!isset($mostra_cip) || !$mostra_cip) ? 'colspan=2' : ''); ?>>
                <div class="titulo">Uso do banco</div>
                <div class="conteudo"><?php echo e($uso_banco); ?></div>
            </td>
            <?php endif; ?>
            <?php if(isset($mostra_cip) && $mostra_cip): ?>
                    <!-- Campo exclusivo do Bradesco -->
            <td width="20">
                <div class="titulo">CIP</div>
                <div class="conteudo"><?php echo e($cip); ?></div>
            </td>
        <?php endif; ?>

        <td <?php echo e(isset($esconde_uso_banco) && $esconde_uso_banco ? 'colspan=3': ''); ?>>
            <div class="titulo">Carteira</div>
            <div class="conteudo"><?php echo e($carteira_nome); ?></div>
        </td>
        <td width="35">
            <div class="titulo">Espécie</div>
            <div class="conteudo"><?php echo e($especie); ?></div>
        </td>
        <td colspan="2">
            <div class="titulo">Quantidade</div>
            <div class="conteudo"></div>
        </td>
        <td width="110">
            <div class="titulo">Valor</div>
            <div class="conteudo"></div>
        </td>
        <td>
            <div class="titulo">(=) Valor do Documento</div>
            <div class="conteudo rtl"><?php echo e($valor); ?></div>
        </td>
    </tr>
    <tr>
        <td colspan="7">
            <div class="titulo">Instruções de responsabilidade do beneficiário. Qualquer dúvida sobre este boleto, contate o beneficiário</div>
        </td>
        <td>
            <div class="titulo">(-) Descontos / Abatimentos</div>
            <div class="conteudo rtl"></div>
        </td>
    </tr>
    <tr>
        <td colspan="7" class="notopborder">
            <div class="conteudo"><?php echo e($instrucoes[0]); ?></div>
            <div class="conteudo"><?php echo e($instrucoes[1]); ?></div>
        </td>
        <td>
            <div class="titulo">(-) Outras deduções</div>
            <div class="conteudo rtl"></div>
        </td>
    </tr>
    <tr>
        <td colspan="7" class="notopborder">
            <div class="conteudo"><?php echo e($instrucoes[2]); ?></div>
            <div class="conteudo"><?php echo e($instrucoes[3]); ?></div>
        </td>
        <td>
            <div class="titulo">(+) Mora / Multa <?php echo e($codigo_banco == '104' ? '/ Juros' : ''); ?></div>
            <div class="conteudo rtl"></div>
        </td>
    </tr>
    <tr>
        <td colspan="7" class="notopborder">
            <div class="conteudo"><?php echo e($instrucoes[4]); ?></div>
            <div class="conteudo"><?php echo e($instrucoes[5]); ?></div>
        </td>
        <td>
            <div class="titulo">(+) Outros acréscimos</div>
            <div class="conteudo rtl"></div>
        </td>
    </tr>
    <tr>
        <td colspan="7" class="notopborder">
            <div class="conteudo"><?php echo e($instrucoes[6]); ?></div>
            <div class="conteudo"><?php echo e($instrucoes[7]); ?></div>
        </td>
        <td>
            <div class="titulo">(=) Valor cobrado</div>
            <div class="conteudo rtl"></div>
        </td>
    </tr>
    <tr>
        <td colspan="7">
            <div class="titulo">Pagador</div>
            <div class="conteudo"><?php echo e($pagador['nome_documento']); ?></div>
            <div class="conteudo"><?php echo e($pagador['endereco']); ?></div>
            <div class="conteudo"><?php echo e($pagador['endereco2']); ?></div>

        </td>
        <td class="noleftborder">
            <div class="titulo" style="margin-top: 50px">Cód. Baixa</div>
        </td>
    </tr>

    <tr>
        <td colspan="6" class="noleftborder">
            <div class="titulo">Sacador/Avalista
                <div class="conteudo sacador"><?php echo e($sacador_avalista ? $sacador_avalista['nome_documento'] : ''); ?></div>
            </div>
        </td>
        <td colspan="2" class="norightborder noleftborder">
            <div class="conteudo noborder rtl">Autenticação mecânica - Ficha de Compensação</div>
        </td>
    </tr>

    <tr>
        <td colspan="8" class="noborder">
            <?php echo $codigo_barras; ?>

        </td>
    </tr>

    </tbody>
</table><?php /**PATH /var/www/html/vendor/eduardokum/laravel-boleto/src/Boleto/Render/view/partials/ficha-compensacao.blade.php ENDPATH**/ ?>